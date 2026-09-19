<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\Tenant;
use App\Notifications\TenantActivityNotification;
use App\Services\TenantNotificationService;
use Illuminate\Foundation\Queue\Queueable;

class NotifyTenantsOfAnnouncement
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $announcementId) {}

    /**
     * Execute the job.
     */
    public function handle(TenantNotificationService $notifications): void
    {
        $announcement = Announcement::query()->find($this->announcementId);

        if (! $announcement?->is_active) {
            return;
        }

        Tenant::query()
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->with('user')
            ->chunkById(200, function ($tenants) use ($announcement, $notifications): void {
                foreach ($tenants as $tenant) {
                    $notifications->notifyTenant(
                        $tenant,
                        new TenantActivityNotification('announcement', 'Pengumuman baru', $announcement->title, route('tenant.announcements.index')),
                    );
                }
            });
    }
}
