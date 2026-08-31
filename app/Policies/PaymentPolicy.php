<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('payments.update') && $payment->status === PaymentStatus::PENDING;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->can('payments.delete') && $payment->status !== PaymentStatus::VERIFIED;
    }

    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify') && $payment->status === PaymentStatus::PENDING;
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $user->can('payments.reject') && $payment->status === PaymentStatus::PENDING;
    }
}
