<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentReceiptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Payment $payment): View
    {
        $hasValidSignature = $request->hasValidSignature();
        $user = $request->user();
        $canView = $hasValidSignature
            || ($user && ($user->can('payments.view') || $user->tenant?->is($payment->tenant)));

        abort_unless($canView && $payment->status === PaymentStatus::VERIFIED, 403);

        return view('payments.receipt', [
            'payment' => $payment->load(['tenant.room', 'invoice', 'method']),
        ]);
    }
}
