<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = $request->user()->tenant->payments()
            ->with('invoice')
            ->latest('id')
            ->paginate(10);

        return view('tenant.payments.index', compact('payments'));
    }

    public function create(Request $request, int $invoice): View
    {
        $invoiceRecord = $request->user()->tenant->invoices()->with('payments')->findOrFail($invoice);

        return view('tenant.payments.create', ['invoice' => $invoiceRecord]);
    }
}
