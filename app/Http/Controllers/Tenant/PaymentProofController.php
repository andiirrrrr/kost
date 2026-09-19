<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, int $payment): StreamedResponse
    {
        $query = Payment::query();

        if (! $request->user()->can('payments.view')) {
            abort_unless($request->user()->hasRole('tenant') && $request->user()->tenant, 403);
            $query->where('tenant_id', $request->user()->tenant->id);
        }

        $paymentRecord = $query->findOrFail($payment);
        abort_unless($paymentRecord->proof && Storage::disk('local')->exists($paymentRecord->proof), 404);

        $extension = strtolower(pathinfo($paymentRecord->proof, PATHINFO_EXTENSION));
        $contentTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];
        abort_unless(isset($contentTypes[$extension]), 404);

        $fileName = $paymentRecord->payment_number.'.'.$extension;

        if ($request->boolean('download')) {
            return Storage::disk('local')->download($paymentRecord->proof, $fileName, [
                'Content-Type' => $contentTypes[$extension],
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return Storage::disk('local')->response($paymentRecord->proof, $fileName, [
            'Content-Type' => $contentTypes[$extension],
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
