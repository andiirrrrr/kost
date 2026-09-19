<?php

use App\Http\Controllers\AdminExportController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TenantLoginController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\Tenant\AnnouncementController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\HouseRulesController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\MaintenanceRequestController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\PaymentProofController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');

Route::get('/login', [TenantLoginController::class, 'create'])->name('login');
Route::post('/login', [TenantLoginController::class, 'store'])->middleware(['guest', 'throttle:tenant-login']);
Route::middleware('guest')->group(function (): void {
    Route::get('/lupa-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/lupa-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');
Route::get('/pembayaran/{payment}/bukti', PaymentProofController::class)->middleware('auth')->name('tenant.payments.proof');
Route::get('/pembayaran/{payment}/kwitansi', PaymentReceiptController::class)->name('payments.receipt');

Route::middleware('auth')->prefix('admin/data')->name('admin.data.')->group(function (): void {
    Route::get('/ekspor/{type?}', [AdminExportController::class, 'export'])->name('export');
    Route::get('/template-impor', [AdminExportController::class, 'template'])->name('template');
    Route::get('/backup', [AdminExportController::class, 'backup'])->name('backup');
});

Route::middleware(['auth', 'tenant'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('tenant.dashboard');
    Route::get('/tagihan', [InvoiceController::class, 'index'])->name('tenant.invoices.index');
    Route::get('/tagihan/{invoice}', [InvoiceController::class, 'show'])->name('tenant.invoices.show');
    Route::get('/tagihan/{invoice}/bayar', [PaymentController::class, 'create'])->name('tenant.payments.create');
    Route::get('/pembayaran', [PaymentController::class, 'index'])->name('tenant.payments.index');
    Route::get('/perbaikan', [MaintenanceRequestController::class, 'index'])->name('tenant.maintenance.index');
    Route::post('/perbaikan', [MaintenanceRequestController::class, 'store'])->name('tenant.maintenance.store');
    Route::view('/notifikasi', 'tenant.notifications')->name('tenant.notifications');
    Route::get('/pengumuman', [AnnouncementController::class, 'index'])->name('tenant.announcements.index');
    Route::get('/tata-tertib', HouseRulesController::class)->name('tenant.rules');
    Route::view('/profil', 'tenant.profile')->name('tenant.profile');
});

Route::match(['get', 'post'], '/webhooks/whatsapp', WhatsAppWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.whatsapp');
