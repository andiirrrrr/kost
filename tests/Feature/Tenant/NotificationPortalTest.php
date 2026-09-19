<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\NotificationBadge;
use App\Livewire\Tenant\Notifications;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantActivityNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_tenant_only_lists_own_notifications_and_can_mark_one_read(): void
    {
        $user = $this->tenantUser();
        $otherUser = $this->tenantUser();
        $user->notify(new TenantActivityNotification('invoice', 'Tagihan September tersedia', 'Silakan periksa tagihan Anda.'));
        $otherUser->notify(new TenantActivityNotification('invoice', 'Rahasia penghuni lain', 'Tidak boleh terlihat.'));
        $notification = $user->notifications()->sole();

        Livewire::actingAs($user)->test(Notifications::class)
            ->assertSee('Tagihan September tersedia')
            ->assertDontSee('Rahasia penghuni lain')
            ->call('markAsRead', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_deleted_invoice_notification_remains_without_broken_link(): void
    {
        $user = $this->tenantUser();
        $tenant = $user->tenant;
        $invoice = Invoice::factory()->for($tenant)->create(['room_id' => $tenant->room_id]);
        $invoiceUrl = route('tenant.invoices.show', $invoice);
        $user->notify(new TenantActivityNotification('invoice_created', 'Tagihan baru tersedia', 'Silakan periksa tagihan Anda.', $invoiceUrl, $invoice->id));

        $invoice->delete();

        Livewire::actingAs($user)->test(Notifications::class)
            ->assertSee('Tagihan ini telah dihapus oleh admin')
            ->assertDontSee($invoiceUrl, false);
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_legacy_invoice_notification_link_returns_when_invoice_is_restored(): void
    {
        $user = $this->tenantUser();
        $tenant = $user->tenant;
        $invoice = Invoice::factory()->for($tenant)->create(['room_id' => $tenant->room_id]);
        $invoiceUrl = route('tenant.invoices.show', $invoice);
        $user->notify(new TenantActivityNotification('invoice_created', 'Tagihan lama', 'Notifikasi tanpa invoice ID.', $invoiceUrl));
        $invoice->delete();

        Livewire::actingAs($user)->test(Notifications::class)
            ->assertSee('Tagihan ini telah dihapus oleh admin')
            ->assertDontSee($invoiceUrl, false);

        $invoice->restore();

        Livewire::actingAs($user)->test(Notifications::class)
            ->assertDontSee('Tagihan ini telah dihapus oleh admin')
            ->assertSee($invoiceUrl, false);
    }

    public function test_notifications_pagination_renders_translated_labels_without_raw_keys(): void
    {
        $user = $this->tenantUser();

        // Create 15 notifications so pagination triggers (perPage is 12)
        for ($i = 1; $i <= 15; $i++) {
            $user->notify(new TenantActivityNotification('info', "Notifikasi {$i}", "Pesan ke-{$i}"));
        }

        Livewire::actingAs($user)->test(Notifications::class)
            ->assertSee('Sebelumnya')
            ->assertSee('Berikutnya')
            ->assertDontSee('pagination.previous')
            ->assertDontSee('pagination.next');
    }

    public function test_opening_notification_marks_it_as_read_and_redirects(): void
    {
        $user = $this->tenantUser();
        $targetUrl = route('tenant.invoices.index');
        $user->notify(new TenantActivityNotification('invoice', 'Tagihan Baru', 'Ada tagihan.', $targetUrl));
        $notification = $user->notifications()->sole();

        $this->assertNull($notification->read_at);

        Livewire::actingAs($user)->test(Notifications::class)
            ->call('openNotification', $notification->id)
            ->assertDispatched('notification-updated')
            ->assertRedirect($targetUrl);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_badge_component_shows_unread_count_and_updates_on_event(): void
    {
        $user = $this->tenantUser();
        $user->notify(new TenantActivityNotification('info', 'Notif 1', 'Pesan 1'));
        $user->notify(new TenantActivityNotification('info', 'Notif 2', 'Pesan 2'));

        $component = Livewire::actingAs($user)->test(NotificationBadge::class)
            ->assertSee('2');

        // Mark one notification as read
        $user->unreadNotifications()->first()->markAsRead();

        // Dispatch event
        $component->dispatch('notification-updated')
            ->assertSee('1');
    }

    public function test_maintenance_status_change_notifies_tenant(): void
    {
        $user = $this->tenantUser();
        $tenant = $user->tenant;
        $maintenance = MaintenanceRequest::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'reported',
            'title' => 'Keran Bocor',
        ]);

        $maintenance->update(['status' => 'in_progress']);

        $latestNotification = $user->notifications()->latest()->first();
        $this->assertNotNull($latestNotification);
        $this->assertSame('Status perbaikan diperbarui', $latestNotification->data['title']);
        $this->assertStringContainsString('Keran Bocor', $latestNotification->data['message']);
        $this->assertStringContainsString('sedang diproses', $latestNotification->data['message']);
    }

    private function tenantUser(): User
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create();
        Tenant::factory()->for($user)->create();
        $user->assignRole('tenant');

        return $user;
    }
}
