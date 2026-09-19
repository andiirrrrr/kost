<?php

namespace Database\Seeders;

use App\Enums\BroadcastAudience;
use App\Enums\BroadcastStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\WhatsAppStatus;
use App\Models\Announcement;
use App\Models\Broadcast;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use App\Notifications\TenantActivityNotification;
use App\Services\TenantAccountService;
use Illuminate\Database\Seeder;

class DemoOperationalDataSeeder extends Seeder
{
    public function run(TenantAccountService $tenantAccounts): void
    {
        $owner = User::query()->where('email', config('demo.owner_email'))->sole();
        $tenant = Tenant::query()->where('phone', '6281234567890')->sole();
        $tenantUser = $this->createTenantAccount($tenantAccounts, $tenant);

        $invoices = $this->seedInvoices($owner);
        $this->seedPayments($owner, $invoices);
        $this->seedAnnouncements($owner);
        $this->seedWhatsAppHistory($owner, $invoices);
        $this->seedNotifications($owner, $tenantUser, $invoices['budi_august']);
    }

    private function createTenantAccount(TenantAccountService $tenantAccounts, Tenant $tenant): User
    {
        $configuredPassword = config('demo.tenant_password');

        if ($tenant->user_id) {
            $tenantUser = $tenant->user()->firstOrFail();

            if (filled($configuredPassword)) {
                $tenantUser->update(['password' => $configuredPassword]);
            }

            $tenantUser->syncRoles(['tenant']);

            return $tenantUser;
        }

        $account = $tenantAccounts->create(
            $tenant,
            config('demo.tenant_email'),
            $configuredPassword,
        );

        $this->command?->warn('Password sementara tenant '.config('demo.tenant_email').': '.$account['temporary_password']);

        return $account['user'];
    }

    /** @return array<string, Invoice> */
    private function seedInvoices(User $owner): array
    {
        $budi = Tenant::query()->where('phone', '6281234567890')->sole();
        $siti = Tenant::query()->where('phone', '6281298765432')->sole();
        $agus = Tenant::query()->where('phone', '6281345678901')->sole();
        $dewi = Tenant::query()->where('phone', '6281356789012')->sole();

        return [
            'budi_july' => $this->updateInvoice($owner, $budi, [
                'invoice_number' => 'INV-202607-0001', 'period_month' => 7, 'period_year' => 2026,
                'electricity_amount' => 75000, 'water_amount' => 50000, 'discount_amount' => 25000,
                'total_amount' => 900000, 'due_date' => '2026-07-05', 'status' => InvoiceStatus::PAID,
                'paid_at' => '2026-07-04 10:30:00',
            ]),
            'budi_august' => $this->updateInvoice($owner, $budi, [
                'invoice_number' => 'INV-202608-0001', 'period_month' => 8, 'period_year' => 2026,
                'electricity_amount' => 90000, 'water_amount' => 50000, 'discount_amount' => 0,
                'total_amount' => 940000, 'due_date' => '2026-08-05', 'status' => InvoiceStatus::OVERDUE,
                'paid_at' => null,
            ]),
            'siti_august' => $this->updateInvoice($owner, $siti, [
                'invoice_number' => 'INV-202608-0002', 'period_month' => 8, 'period_year' => 2026,
                'electricity_amount' => 80000, 'water_amount' => 50000, 'discount_amount' => 0,
                'total_amount' => 930000, 'due_date' => '2026-08-05', 'status' => InvoiceStatus::PENDING,
                'paid_at' => null,
            ]),
            'agus_august' => $this->updateInvoice($owner, $agus, [
                'invoice_number' => 'INV-202608-0003', 'period_month' => 8, 'period_year' => 2026,
                'electricity_amount' => 100000, 'water_amount' => 50000, 'discount_amount' => 0,
                'total_amount' => 1150000, 'due_date' => '2026-08-05', 'status' => InvoiceStatus::PAID,
                'paid_at' => '2026-08-03 09:15:00',
            ]),
            'dewi_august' => $this->updateInvoice($owner, $dewi, [
                'invoice_number' => 'INV-202608-0004', 'period_month' => 8, 'period_year' => 2026,
                'electricity_amount' => 110000, 'water_amount' => 50000, 'discount_amount' => 0,
                'total_amount' => 1160000, 'due_date' => '2026-08-05', 'status' => InvoiceStatus::OVERDUE,
                'paid_at' => null,
            ]),
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function updateInvoice(User $owner, Tenant $tenant, array $attributes): Invoice
    {
        return Invoice::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'period_month' => $attributes['period_month'], 'period_year' => $attributes['period_year']],
            [
                ...$attributes,
                'room_id' => $tenant->room_id,
                'base_amount' => (int) $tenant->monthly_price,
                'other_amount' => 0,
                'notes' => 'Data demo untuk pengujian portal penghuni.',
                'created_by' => $owner->id,
            ],
        );
    }

    /** @param array<string, Invoice> $invoices */
    private function seedPayments(User $owner, array $invoices): void
    {
        $paymentMethod = PaymentMethod::query()->where('code', 'bank_transfer')->sole();

        Payment::query()->updateOrCreate(['payment_number' => 'PAY-202607-0001'], [
            'invoice_id' => $invoices['budi_july']->id,
            'tenant_id' => $invoices['budi_july']->tenant_id,
            'amount' => $invoices['budi_july']->total_amount,
            'payment_method' => $paymentMethod->code,
            'payment_method_id' => $paymentMethod->id,
            'paid_at' => '2026-07-04 10:00:00',
            'status' => PaymentStatus::VERIFIED,
            'notes' => 'Pembayaran demo terverifikasi.',
            'verified_by' => $owner->id,
            'verified_at' => '2026-07-04 10:30:00',
        ]);

        Payment::query()->updateOrCreate(['payment_number' => 'PAY-202608-0001'], [
            'invoice_id' => $invoices['siti_august']->id,
            'tenant_id' => $invoices['siti_august']->tenant_id,
            'amount' => $invoices['siti_august']->total_amount,
            'payment_method' => $paymentMethod->code,
            'payment_method_id' => $paymentMethod->id,
            'paid_at' => '2026-08-04 14:20:00',
            'status' => PaymentStatus::PENDING,
            'notes' => 'Menunggu verifikasi pemilik.',
            'verified_by' => null,
            'verified_at' => null,
        ]);

        Payment::query()->updateOrCreate(['payment_number' => 'PAY-202608-0002'], [
            'invoice_id' => $invoices['dewi_august']->id,
            'tenant_id' => $invoices['dewi_august']->tenant_id,
            'amount' => $invoices['dewi_august']->total_amount,
            'payment_method' => $paymentMethod->code,
            'payment_method_id' => $paymentMethod->id,
            'paid_at' => '2026-08-06 16:45:00',
            'status' => PaymentStatus::REJECTED,
            'rejection_reason' => 'Bukti transfer tidak terbaca.',
            'notes' => 'Data demo pembayaran ditolak.',
            'verified_by' => $owner->id,
            'verified_at' => '2026-08-06 17:00:00',
        ]);
    }

    private function seedAnnouncements(User $owner): void
    {
        foreach ([
            ['title' => 'Jadwal Pembersihan Area Bersama', 'content' => 'Pembersihan area dapur dan lorong dilakukan setiap hari Sabtu pukul 08.00.', 'published_at' => now()->subDays(5), 'expires_at' => null, 'is_active' => true],
            ['title' => 'Pemeliharaan Jaringan Internet', 'content' => 'Akan ada pemeliharaan jaringan internet pada Minggu pukul 01.00–03.00.', 'published_at' => now()->subDays(2), 'expires_at' => now()->addDays(7), 'is_active' => true],
            ['title' => 'Pengingat Keamanan Kost', 'content' => 'Pastikan pintu gerbang kembali terkunci setelah keluar atau masuk area kost.', 'published_at' => now()->subDay(), 'expires_at' => null, 'is_active' => true],
        ] as $announcement) {
            Announcement::query()->updateOrCreate(
                ['title' => $announcement['title']],
                [...$announcement, 'created_by' => $owner->id],
            );
        }
    }

    /** @param array<string, Invoice> $invoices */
    private function seedWhatsAppHistory(User $owner, array $invoices): void
    {
        $template = WhatsAppTemplate::query()->where('template_name', 'tagihan_bulanan')->sole();
        $broadcast = Broadcast::query()->updateOrCreate(['title' => 'Broadcast Tagihan Agustus 2026'], [
            'whatsapp_template_id' => $template->id,
            'template_name' => $template->template_name,
            'audience_type' => BroadcastAudience::ALL,
            'total_recipient' => 3,
            'total_sent' => 2,
            'total_failed' => 1,
            'status' => BroadcastStatus::COMPLETED,
            'created_by' => $owner->id,
            'started_at' => '2026-08-01 08:00:00',
            'finished_at' => '2026-08-01 08:02:00',
        ]);

        foreach ([
            ['tenant_id' => $invoices['budi_august']->tenant_id, 'invoice_id' => $invoices['budi_august']->id, 'phone' => '6281234567890', 'message_id' => 'demo-message-budi-august', 'deduplication_key' => 'demo-broadcast-august-budi', 'status' => WhatsAppStatus::READ, 'attempts' => 1, 'sent_at' => '2026-08-01 08:00:10', 'delivered_at' => '2026-08-01 08:00:20', 'read_at' => '2026-08-01 08:05:00'],
            ['tenant_id' => $invoices['siti_august']->tenant_id, 'invoice_id' => $invoices['siti_august']->id, 'phone' => '6281298765432', 'message_id' => 'demo-message-siti-august', 'deduplication_key' => 'demo-broadcast-august-siti', 'status' => WhatsAppStatus::DELIVERED, 'attempts' => 1, 'sent_at' => '2026-08-01 08:00:30', 'delivered_at' => '2026-08-01 08:00:45', 'read_at' => null],
            ['tenant_id' => $invoices['dewi_august']->tenant_id, 'invoice_id' => $invoices['dewi_august']->id, 'phone' => '6281356789012', 'message_id' => null, 'deduplication_key' => 'demo-broadcast-august-dewi', 'status' => WhatsAppStatus::FAILED, 'attempts' => 3, 'error_message' => 'Nomor tujuan tidak dapat menerima pesan.', 'failed_at' => '2026-08-01 08:02:00'],
        ] as $log) {
            WhatsAppLog::query()->updateOrCreate(
                ['deduplication_key' => $log['deduplication_key']],
                [...$log, 'broadcast_id' => $broadcast->id, 'template_name' => $template->template_name, 'parameters' => ['periode' => 'Agustus 2026']],
            );
        }
    }

    private function seedNotifications(User $owner, User $tenantUser, Invoice $invoice): void
    {
        $tenantUser->notifications()->firstOrCreate(['id' => '00000000-0000-4000-8000-000000000001'], [
            'type' => TenantActivityNotification::class,
            'data' => ['type' => 'invoice', 'title' => 'Tagihan Agustus tersedia', 'message' => 'Tagihan kamar A1 periode Agustus 2026 telah tersedia.', 'url' => '/tagihan/'.$invoice->id],
            'read_at' => null,
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

        $owner->notifications()->firstOrCreate(['id' => '00000000-0000-4000-8000-000000000002'], [
            'type' => TenantActivityNotification::class,
            'data' => ['type' => 'payment_submitted', 'title' => 'Pembayaran baru', 'message' => 'Ada pembayaran demo yang menunggu verifikasi.', 'url' => '/admin/payments'],
            'read_at' => null,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
    }
}
