<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use Livewire\WithPagination;

class Notifications extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public function markAsRead(string $notificationId): void
    {
        auth()->user()->notifications()->findOrFail($notificationId)->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $notifications = auth()->user()->notifications()
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(12);

        return view('livewire.tenant.notifications', compact('notifications'));
    }
}
