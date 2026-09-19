<?php

namespace Tests\Feature\Tenant;

use App\Livewire\Tenant\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_tenant_sees_a_success_notification_after_updating_contact(): void
    {
        [$user, $tenant] = $this->tenantIdentity();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('phone', '081234567891')
            ->set('email', 'penghuni.baru@kost.test')
            ->call('updateContact')
            ->assertHasNoErrors()
            ->assertDispatched('tenant-toast', type: 'success', message: 'Kontak berhasil diperbarui.');

        $this->assertSame('6281234567891', $tenant->refresh()->phone);
        $this->assertSame('penghuni.baru@kost.test', $user->refresh()->email);
    }

    public function test_tenant_sees_an_error_notification_when_contact_update_is_invalid(): void
    {
        [$user] = $this->tenantIdentity();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('phone', '')
            ->set('email', 'bukan-email')
            ->call('updateContact')
            ->assertHasErrors(['phone', 'email'])
            ->assertDispatched('tenant-toast', type: 'error', message: 'Nomor HP wajib diisi.');
    }

    public function test_tenant_cannot_save_letters_as_a_phone_number(): void
    {
        [$user, $tenant] = $this->tenantIdentity();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('phone', '0812abc3456')
            ->set('email', $user->email)
            ->call('updateContact')
            ->assertHasErrors('phone')
            ->assertDispatched('tenant-toast', type: 'error', message: 'Nomor HP hanya boleh berisi angka dan harus diawali 0 atau 62.');

        $this->assertSame($tenant->phone, $tenant->refresh()->phone);
    }

    /** @return array{User, Tenant} */
    private function tenantIdentity(): array
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create();
        $tenant = Tenant::factory()->for($user)->create();
        $user->assignRole('tenant');

        return [$user, $tenant];
    }
}
