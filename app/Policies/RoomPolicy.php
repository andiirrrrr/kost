<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rooms.view');
    }

    public function view(User $user, Room $room): bool
    {
        return $user->can('rooms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rooms.create');
    }

    public function update(User $user, Room $room): bool
    {
        return $user->can('rooms.update');
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->can('rooms.delete') && ! $room->tenants()->exists();
    }

    public function restore(User $user, Room $room): bool
    {
        return $user->can('rooms.restore');
    }

    public function forceDelete(User $user, Room $room): bool
    {
        return $user->can('rooms.forceDelete') && ! $room->tenants()->exists();
    }
}
