<?php

namespace App\Livewire\Tenant;

use App\Services\TenantNotificationPresenter;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Notifications extends Component
{
    use WithPagination;

    public string $filter = 'all';

    private TenantNotificationPresenter $presenter;

    public function boot(TenantNotificationPresenter $presenter): void
    {
        $this->presenter = $presenter;
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
            $this->dispatch('notification-updated');
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->dispatch('notification-updated');
    }

    public function openNotification(string $notificationId): mixed
    {
        $notification = auth()->user()->notifications()->find($notificationId);
        if (! $notification) {
            return null;
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
            $this->dispatch('notification-updated');
        }

        $url = $notification->data['url'] ?? null;
        if ($url) {
            return redirect()->to($url);
        }

        return null;
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    #[On('notification-updated')]
    public function refreshList(): void
    {
        // Re-render component on notification update
    }

    public function render()
    {
        $notifications = auth()->user()->notifications()
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(12);

        $notifications->setCollection($this->presenter->forDisplay($notifications->getCollection()));

        return view('livewire.tenant.notifications', compact('notifications'));
    }
}
