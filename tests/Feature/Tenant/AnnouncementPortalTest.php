<?php

namespace Tests\Feature\Tenant;

use App\Jobs\NotifyTenantsOfAnnouncement;
use App\Models\Announcement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantNotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnnouncementPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_active_published_announcements_are_visible(): void
    {
        $user = $this->tenantUser();
        Announcement::factory()->create(['title' => 'Pengumuman Aktif']);
        Announcement::factory()->create(['title' => 'Draft Rahasia', 'is_active' => false, 'published_at' => null]);

        $this->actingAs($user)->get('/pengumuman')
            ->assertOk()
            ->assertSee('Pengumuman Aktif')
            ->assertDontSee('Draft Rahasia');
    }

    public function test_publish_job_notifies_active_tenants_only(): void
    {
        $activeUser = $this->tenantUser();
        $inactiveUser = $this->tenantUser('inactive');
        $announcement = Announcement::factory()->create(['title' => 'Air Mati Sementara']);

        (new NotifyTenantsOfAnnouncement($announcement->id))->handle(app(TenantNotificationService::class));

        $this->assertSame(1, $activeUser->notifications()->count());
        $this->assertSame(0, $inactiveUser->notifications()->count());
    }

    private function tenantUser(string $status = 'active'): User
    {
        Role::findOrCreate('tenant', 'web');
        $user = User::factory()->create();
        Tenant::factory()->for($user)->create(['status' => $status]);
        $user->assignRole('tenant');

        return $user;
    }
}
