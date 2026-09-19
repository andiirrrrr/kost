<?php

namespace App\Livewire\Tenant;

use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBadge extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->updateCount();
    }

    #[On('notification-updated')]
    public function updateCount(): void
    {
        $this->unreadCount = (int) (auth()->user()?->unreadNotifications()->count() ?? 0);
    }

    public function render()
    {
        return view('livewire.tenant.notification-badge');
    }
}
