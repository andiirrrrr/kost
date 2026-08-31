<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Notifications;
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

    private function tenantUser(): User
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create();
        Tenant::factory()->for($user)->create();
        $user->assignRole('tenant');

        return $user;
    }
}
