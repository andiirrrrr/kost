<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.update') && $invoice->payments()->doesntExist();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.delete') && $invoice->payments()->doesntExist();
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.restore');
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.forceDelete') && $invoice->payments()->doesntExist();
    }

    public function generate(User $user): bool
    {
        return $user->can('invoices.generate');
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->can('payments.create')
            && in_array($invoice->status, [InvoiceStatus::UNPAID, InvoiceStatus::OVERDUE], true)
            && ! $invoice->payments()->where('status', PaymentStatus::PENDING)->exists();
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.update') && ! $invoice->status->isTerminal();
    }
}
