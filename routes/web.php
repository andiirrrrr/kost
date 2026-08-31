<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\TenantLoginController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\Tenant\AnnouncementController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\PaymentProofController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');

Route::get('/login', [TenantLoginController::class, 'create'])->name('login');
Route::post('/login', [TenantLoginController::class, 'store'])->middleware(['guest', 'throttle:tenant-login']);

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');
Route::get('/pembayaran/{payment}/bukti', PaymentProofController::class)->middleware('auth')->name('tenant.payments.proof');

Route::middleware(['auth', 'tenant'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('tenant.dashboard');
    Route::get('/tagihan', [InvoiceController::class, 'index'])->name('tenant.invoices.index');
    Route::get('/tagihan/{invoice}', [InvoiceController::class, 'show'])->name('tenant.invoices.show');
    Route::get('/tagihan/{invoice}/bayar', [PaymentController::class, 'create'])->name('tenant.payments.create');
    Route::get('/pembayaran', [PaymentController::class, 'index'])->name('tenant.payments.index');
    Route::view('/notifikasi', 'tenant.notifications')->name('tenant.notifications');
    Route::get('/pengumuman', [AnnouncementController::class, 'index'])->name('tenant.announcements.index');
    Route::view('/profil', 'tenant.profile')->name('tenant.profile');
});

Route::match(['get', 'post'], '/webhooks/whatsapp', WhatsAppWebhookController::class)
    ->name('webhooks.whatsapp');
