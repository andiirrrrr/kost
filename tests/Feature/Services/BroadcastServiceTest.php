<?php

namespace Tests\Feature\Services;

use App\Enums\BroadcastAudience;
use App\Enums\BroadcastStatus;
use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Enums\WhatsAppStatus;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use App\Services\BroadcastService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BroadcastServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_selects_latest_unique_valid_recipient_for_unpaid_audience(): void
    {
        $tenant = Tenant::factory()->create(['phone' => '081234567890', 'status' => TenantStatus::ACTIVE]);
        Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'period_month' => 7,
            'status' => InvoiceStatus::UNPAID,
        ]);
        $latestInvoice = Invoice::factory()->for($tenant)->create([
            'room_id' => $tenant->room_id,
            'period_month' => 8,
            'status' => InvoiceStatus::OVERDUE,
        ]);
        $invalidTenant = Tenant::factory()->create(['phone' => '123', 'status' => TenantStatus::ACTIVE]);
        Invoice::factory()->for($invalidTenant)->create([
            'room_id' => $invalidTenant->room_id,
            'status' => InvoiceStatus::UNPAID,
        ]);

        $recipients = app(BroadcastService::class)->recipients(BroadcastAudience::UNPAID);

        $this->assertCount(1, $recipients);
        $this->assertTrue($recipients->first()['tenant']->is($tenant));
        $this->assertTrue($recipients->first()['invoice']->is($latestInvoice));
        $this->assertSame('6281234567890', $recipients->first()['phone']);
    }

    public function test_creates_logs_and_dispatches_one_job_per_recipient(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        $admin = User::factory()->create();
        Tenant::factory()->create(['phone' => '081234567890', 'status' => TenantStatus::ACTIVE]);
        Tenant::factory()->create(['phone' => '081298765432', 'status' => TenantStatus::ACTIVE]);
        $template = WhatsAppTemplate::factory()->create(['variables' => ['nama', 'kamar']]);

        $broadcast = app(BroadcastService::class)->createBroadcast(
            'Pengumuman penghuni',
            $template,
            BroadcastAudience::ALL,
            $admin->id,
        );

        $this->assertSame(BroadcastStatus::QUEUED, $broadcast->status);
        $this->assertSame(2, $broadcast->total_recipient);
        $this->assertSame(2, $broadcast->logs()->count());
        $this->assertSame(2, WhatsAppLog::where('status', WhatsAppStatus::QUEUED)->count());
        Queue::assertPushed(SendWhatsAppMessage::class, 2);
    }

    public function test_manual_audience_only_includes_selected_active_tenants(): void
    {
        $selected = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        $inactive = Tenant::factory()->create(['status' => TenantStatus::INACTIVE]);

        $recipients = app(BroadcastService::class)->recipients(
            BroadcastAudience::MANUAL,
            [$selected->id, $inactive->id],
        );

        $this->assertCount(1, $recipients);
        $this->assertTrue($recipients->first()['tenant']->is($selected));
    }
}
