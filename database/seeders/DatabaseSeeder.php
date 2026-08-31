<?php

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /** @var list<string> */
    private const ALL_PERMISSIONS = [
        'rooms.view', 'rooms.create', 'rooms.update', 'rooms.delete', 'rooms.restore', 'rooms.forceDelete',
        'tenants.view', 'tenants.create', 'tenants.update', 'tenants.delete',
        'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete', 'invoices.restore', 'invoices.forceDelete', 'invoices.generate',
        'payments.view', 'payments.create', 'payments.update', 'payments.delete', 'payments.verify', 'payments.reject',
        'expenses.view', 'expenses.create', 'expenses.update', 'expenses.delete',
        'reports.view',
        'whatsapp.view', 'whatsapp.send', 'whatsapp.manage_templates',
        'announcements.view', 'announcements.create', 'announcements.update', 'announcements.delete', 'announcements.publish',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::ALL_PERMISSIONS)
            ->mapWithKeys(fn (string $name): array => [$name => Permission::findOrCreate($name, 'web')]);

        $owner = Role::findOrCreate('owner', 'web');
        $tenantRole = Role::findOrCreate('tenant', 'web');
        $public = Role::findOrCreate('public', 'web');

        $owner->syncPermissions($permissions);
        $tenantRole->syncPermissions([]);
        $public->syncPermissions([]);

        $legacyRoles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['admin', 'staff'])
            ->get();

        if ($legacyRoles->isNotEmpty()) {
            User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $legacyRoles->modelKeys()))
                ->each(fn (User $legacyManager) => $legacyManager->assignRole($owner));

            Role::query()->whereKey($legacyRoles->modelKeys())->delete();
        }

        $ownerEmail = config('demo.owner_email');
        $configuredOwnerPassword = config('demo.owner_password');
        $user = User::firstOrNew(['email' => $ownerEmail]);
        $mustSetOwnerPassword = ! $user->exists
            || filled($configuredOwnerPassword)
            || Hash::check('password', (string) $user->password);

        $user->name = 'Pemilik Kost';

        if ($mustSetOwnerPassword) {
            $ownerPassword = $configuredOwnerPassword ?: Str::password(20);
            $user->password = $ownerPassword;
        }

        $user->save();
        $user->syncRoles([$owner]);

        if (isset($ownerPassword)) {
            $this->command?->warn("Password sementara owner {$ownerEmail}: {$ownerPassword}");
        }

        foreach ([
            'business_name' => 'Kost Bahagia',
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'default_due_day' => 5,
            'whatsapp_enabled' => false,
            'automatic_invoice_enabled' => true,
            'automatic_reminder_enabled' => false,
            'reminder_before_days' => 3,
            'reminder_due_date_enabled' => true,
            'reminder_after_days' => 3,
            'bank_name' => 'Bank BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Kost Bahagia',
            'tagline' => 'Hunian nyaman, aman, dan strategis.',
            'address' => 'Alamat kost belum diatur.',
            'contact_phone' => '081234567890',
        ] as $key => $value) {
            Setting::set($key, $value);
        }

        $rooms = collect([
            ['room_number' => 'A1', 'monthly_price' => 800000, 'status' => 'occupied'],
            ['room_number' => 'A2', 'monthly_price' => 800000, 'status' => 'occupied'],
            ['room_number' => 'A3', 'monthly_price' => 800000, 'status' => 'available'],
            ['room_number' => 'B1', 'monthly_price' => 1000000, 'status' => 'occupied'],
            ['room_number' => 'B2', 'monthly_price' => 1000000, 'status' => 'occupied'],
            ['room_number' => 'B3', 'monthly_price' => 1000000, 'status' => 'available'],
            ['room_number' => 'C1', 'monthly_price' => 1200000, 'status' => 'occupied'],
            ['room_number' => 'C2', 'monthly_price' => 1200000, 'status' => 'occupied'],
            ['room_number' => 'C3', 'monthly_price' => 1200000, 'status' => 'maintenance'],
            ['room_number' => 'D1', 'monthly_price' => 1500000, 'status' => 'occupied'],
        ])->mapWithKeys(function (array $attributes): array {
            $room = Room::withTrashed()->updateOrCreate(
                ['room_number' => $attributes['room_number']],
                $attributes,
            );

            if ($room->trashed()) {
                $room->restore();
            }

            return [$room->room_number => $room];
        });

        $tenants = [
            ['room' => 'A1', 'name' => 'Budi Santoso', 'phone' => '081234567890', 'move_in_date' => '2026-01-01', 'monthly_price' => 800000],
            ['room' => 'A2', 'name' => 'Siti Rahayu', 'phone' => '081298765432', 'move_in_date' => '2026-02-01', 'monthly_price' => 800000],
            ['room' => 'B1', 'name' => 'Agus Wijaya', 'phone' => '081345678901', 'move_in_date' => '2026-01-15', 'monthly_price' => 1000000],
            ['room' => 'B2', 'name' => 'Dewi Lestari', 'phone' => '081356789012', 'move_in_date' => '2026-03-01', 'monthly_price' => 1000000],
            ['room' => 'C1', 'name' => 'Eko Prasetyo', 'phone' => '081367890123', 'move_in_date' => '2026-02-15', 'monthly_price' => 1200000],
            ['room' => 'C2', 'name' => 'Fitriani', 'phone' => '081378901234', 'move_in_date' => '2026-01-10', 'monthly_price' => 1200000],
            ['room' => 'D1', 'name' => 'Gunawan', 'phone' => '081389012345', 'move_in_date' => '2026-04-01', 'monthly_price' => 1500000],
        ];

        foreach ($tenants as $attributes) {
            $roomNumber = $attributes['room'];
            unset($attributes['room']);

            Tenant::updateOrCreate(
                ['phone' => $this->normalizePhone($attributes['phone'])],
                [...$attributes, 'room_id' => $rooms[$roomNumber]->id, 'due_day' => 5, 'status' => 'active'],
            );
        }

        foreach ([
            ['category' => ExpenseCategory::ELECTRICITY, 'description' => 'Tagihan listrik Agustus', 'amount' => 850000, 'expense_date' => '2026-08-05'],
            ['category' => ExpenseCategory::INTERNET, 'description' => 'Internet kost Agustus', 'amount' => 450000, 'expense_date' => '2026-08-10'],
            ['category' => ExpenseCategory::MAINTENANCE, 'description' => 'Perbaikan pompa air', 'amount' => 600000, 'expense_date' => '2026-08-18'],
        ] as $expense) {
            Expense::updateOrCreate(
                ['description' => $expense['description']],
                [...$expense, 'created_by' => $user->id],
            );
        }

        foreach ([
            ['template_name' => 'tagihan_bulanan', 'display_name' => 'Tagihan Bulanan', 'category' => 'utility', 'content' => 'Halo {{nama}}, tagihan kamar {{kamar}} periode {{periode}} sebesar {{total}} jatuh tempo pada {{jatuh_tempo}}.', 'variables' => ['nama', 'kamar', 'periode', 'total', 'jatuh_tempo']],
            ['template_name' => 'pengingat_jatuh_tempo', 'display_name' => 'Pengingat Jatuh Tempo', 'category' => 'utility', 'content' => 'Halo {{nama}}, pengingat tagihan kamar {{kamar}} sebesar {{total}} jatuh tempo pada {{jatuh_tempo}}.', 'variables' => ['nama', 'kamar', 'total', 'jatuh_tempo']],
            ['template_name' => 'tagihan_terlambat', 'display_name' => 'Tagihan Terlambat', 'category' => 'utility', 'content' => 'Halo {{nama}}, tagihan kamar {{kamar}} periode {{periode}} sebesar {{total}} telah melewati jatuh tempo {{jatuh_tempo}}.', 'variables' => ['nama', 'kamar', 'periode', 'total', 'jatuh_tempo']],
            ['template_name' => 'pembayaran_diterima', 'display_name' => 'Pembayaran Diterima', 'category' => 'utility', 'content' => 'Halo {{nama}}, pembayaran kamar {{kamar}} periode {{periode}} sebesar {{total}} telah kami terima.', 'variables' => ['nama', 'kamar', 'periode', 'total']],
            ['template_name' => 'pengumuman_kost', 'display_name' => 'Pengumuman', 'category' => 'marketing', 'content' => 'Halo {{nama}}, ada pengumuman terbaru untuk penghuni kamar {{kamar}}.', 'variables' => ['nama', 'kamar']],
        ] as $template) {
            WhatsAppTemplate::updateOrCreate(
                ['template_name' => $template['template_name']],
                [...$template, 'language' => 'id', 'is_active' => true],
            );
        }

        $this->call(DemoOperationalDataSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function normalizePhone(string $phone): string
    {
        return '62'.substr(preg_replace('/\D/', '', $phone), 1);
    }
}
