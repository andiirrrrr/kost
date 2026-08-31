<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantActivityNotification;
use Illuminate\Support\Facades\Notification;

class TenantNotificationService
{
    public function notifyTenant(Tenant $tenant, TenantActivityNotification $notification): void
    {
        $tenant->user?->notify($notification);
    }

    public function notifyAdministrators(TenantActivityNotification $notification): void
    {
        $administrators = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'owner'))
            ->get();

        Notification::send($administrators, $notification);
    }
}
