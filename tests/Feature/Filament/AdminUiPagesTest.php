<?php

namespace Tests\Feature\Filament;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Announcement;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminUiPagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('adminPageProvider')]
    public function test_owner_can_render_each_primary_admin_page(string $path): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();

        $this->actingAs($owner)->get($path)->assertOk();
    }

    /** @param class-string<Model> $modelClass */
    #[DataProvider('adminEditPageProvider')]
    public function test_owner_can_render_each_admin_edit_page(string $modelClass, string $routeName): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();
        $record = match ($modelClass) {
            Invoice::class => Invoice::query()->where('status', InvoiceStatus::UNPAID)->first()
                ?? Invoice::query()->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::CANCELLED])->first()
                ?? Invoice::factory()->create(),
            Payment::class => Payment::query()->where('status', PaymentStatus::PENDING)->first()
                ?? Payment::factory()->create(),
            default => $modelClass::query()->first() ?? $modelClass::factory()->create(),
        };

        $this->actingAs($owner)->get(route($routeName, $record))->assertOk();
    }

    /** @return array<string, array{string}> */
    public static function adminPageProvider(): array
    {
        return [
            'dashboard' => ['/admin'],
            'rooms list' => ['/admin/rooms'],
            'rooms create' => ['/admin/rooms/create'],
            'room categories list' => ['/admin/room-categories'],
            'room categories create' => ['/admin/room-categories/create'],
            'tenants list' => ['/admin/tenants'],
            'tenants create' => ['/admin/tenants/create'],
            'invoices list' => ['/admin/invoices'],
            'invoices create' => ['/admin/invoices/create'],
            'invoices generate' => ['/admin/invoices/generate'],
            'payments list' => ['/admin/payments'],
            'payments create' => ['/admin/payments/create'],
            'expenses list' => ['/admin/expenses'],
            'expenses create' => ['/admin/expenses/create'],
            'payment methods list' => ['/admin/payment-methods'],
            'payment methods create' => ['/admin/payment-methods/create'],
            'reports' => ['/admin/reports'],
            'maintenance list' => ['/admin/maintenance-requests'],
            'maintenance create' => ['/admin/maintenance-requests/create'],
            'announcements list' => ['/admin/announcements'],
            'announcements create' => ['/admin/announcements/create'],
            'WhatsApp center' => ['/admin/whatsapp-center'],
            'WhatsApp logs' => ['/admin/whats-app-logs'],
            'WhatsApp templates' => ['/admin/whats-app-templates'],
            'payment settings' => ['/admin/payment-settings'],
            'landing page settings' => ['/admin/landing-page'],
            'onboarding' => ['/admin/onboarding'],
            'data tools' => ['/admin/data-tools'],
        ];
    }

    /** @return array<string, array{class-string<Model>, string}> */
    public static function adminEditPageProvider(): array
    {
        return [
            'room edit' => [Room::class, 'filament.admin.resources.rooms.edit'],
            'room category edit' => [RoomCategory::class, 'filament.admin.resources.room-categories.edit'],
            'tenant edit' => [Tenant::class, 'filament.admin.resources.tenants.edit'],
            'invoice edit' => [Invoice::class, 'filament.admin.resources.invoices.edit'],
            'payment edit' => [Payment::class, 'filament.admin.resources.payments.edit'],
            'expense edit' => [Expense::class, 'filament.admin.resources.expenses.edit'],
            'payment method edit' => [PaymentMethod::class, 'filament.admin.resources.payment-methods.edit'],
            'maintenance edit' => [MaintenanceRequest::class, 'filament.admin.resources.maintenance-requests.edit'],
            'announcement edit' => [Announcement::class, 'filament.admin.resources.announcements.edit'],
            'WhatsApp template edit' => [WhatsAppTemplate::class, 'filament.admin.resources.whats-app-templates.edit'],
        ];
    }
}
