<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $filter = $request->string('status')->toString();
        $allowedStatuses = collect(InvoiceStatus::cases())->pluck('value')->all();

        $invoices = $tenant->invoices()
            ->when(in_array($filter, $allowedStatuses, true), fn ($query) => $query->where('status', $filter))
            ->latest('period_year')
            ->latest('period_month')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('tenant.invoices.index', compact('invoices', 'filter'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $invoice): View
    {
        $invoiceRecord = $request->user()->tenant->invoices()->with('payments')->findOrFail($invoice);
        $verifiedPayment = $invoiceRecord->payments->first(
            fn ($payment): bool => $payment->status === PaymentStatus::VERIFIED,
        );

        return view('tenant.invoices.show', [
            'invoice' => $invoiceRecord,
            'verifiedPayment' => $verifiedPayment,
        ]);
    }
}
