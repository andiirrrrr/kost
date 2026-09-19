<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\LandingPageService;
use App\Services\TenantNotificationPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, LandingPageService $landingPage, TenantNotificationPresenter $notificationPresenter): View
    {
        $tenant = $request->user()->tenant()->with('room')->firstOrFail();
        $invoices = $tenant->invoices()->latest('period_year')->latest('period_month')->latest('id');
        $currentInvoice = (clone $invoices)->with('payments')->first();

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'currentInvoice' => $currentInvoice,
            'recentInvoices' => $invoices->limit(3)->get(),
            'recentPayments' => $tenant->payments()->with('invoice')->latest('id')->limit(3)->get(),
            'recentNotifications' => $notificationPresenter->forDisplay($request->user()->notifications()->latest()->limit(3)->get()),
            'announcements' => Announcement::query()
                ->where('is_active', true)
                ->where('published_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'hasPendingPayment' => $currentInvoice?->payments->contains('status', PaymentStatus::PENDING) ?? false,
            'contactPhone' => $landingPage->get()['contact_phone'],
        ]);
    }
}
