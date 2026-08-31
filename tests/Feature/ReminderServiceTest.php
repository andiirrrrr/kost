<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use App\Services\ReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_queues_enabled_reminder_once_for_the_same_invoice_rule_and_date(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        Setting::set('automatic_reminder_enabled', true);
        Setting::set('reminder_before_days', 3);
        Setting::set('reminder_due_date_enabled', false);
        Setting::set('reminder_after_days', 0);
        WhatsAppTemplate::factory()->create([
            'template_name' => 'pengingat_jatuh_tempo',
            'variables' => ['nama', 'kamar', 'total', 'jatuh_tempo'],
            'is_active' => true,
        ]);
        $invoice = Invoice::factory()->create(['due_date' => '2026-09-02']);

        $firstResult = app(ReminderService::class)->dispatchForDate(CarbonImmutable::parse('2026-08-30'));
        $secondResult = app(ReminderService::class)->dispatchForDate(CarbonImmutable::parse('2026-08-30'));

        $this->assertSame(['created' => 1, 'skipped' => 0], $firstResult);
        $this->assertSame(['created' => 0, 'skipped' => 1], $secondResult);
        $this->assertSame(1, WhatsAppLog::whereBelongsTo($invoice)->count());
        Queue::assertPushed(SendWhatsAppMessage::class, 1);
    }

    public function test_does_not_queue_reminders_when_automation_is_disabled(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        Setting::set('automatic_reminder_enabled', false);
        Invoice::factory()->create(['due_date' => '2026-09-02']);

        $result = app(ReminderService::class)->dispatchForDate(CarbonImmutable::parse('2026-08-30'));

        $this->assertSame(['created' => 0, 'skipped' => 0], $result);
        $this->assertSame(0, WhatsAppLog::query()->count());
        Queue::assertNothingPushed();
    }
}
