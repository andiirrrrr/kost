# Kost Management System

Aplikasi manajemen kost berbasis Laravel dan Filament untuk mengelola kamar, penghuni, tagihan bulanan, serta pembayaran dari satu panel admin.

## Status Pengembangan

- Phase 1 — Foundation: selesai.
- Phase 2 — Finance Core: invoices dan payments tersedia; expenses dan laporan keuangan belum dikerjakan.
- Phase 2.5 — Stabilization & Hardening: authorization, integritas pembayaran, test, dan keamanan seeder diterapkan.
- Phase 3 dan seterusnya: belum dikerjakan.

## Stack dan Persyaratan

- PHP 8.3 atau lebih baru beserta extension MySQL dan SQLite
- Composer
- MySQL 8
- Node.js dan npm
- Laravel 13, Filament 5, Livewire 4, dan Tailwind CSS 4

## Instalasi Lokal

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run build
php artisan storage:link
```

Sesuaikan koneksi database pada `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_kost
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi dan development seeder:

```bash
php artisan migrate
php artisan db:seed
```

Seeder bersifat idempotent sehingga aman dijalankan kembali. Seeder tidak menghapus data dan tidak menggunakan `migrate:fresh`.

## Akun Development

- URL: `/admin`
- Email: `admin@kost.test`
- Password awal: `password`

Ganti password sebelum aplikasi dapat diakses di luar lingkungan lokal. Menjalankan seeder kembali tidak akan mereset password akun yang sudah ada.

## Role dan Permission

- `admin`: satu akun pengelola dengan seluruh akses modul Rooms, Tenants, Invoices, dan Payments.
- `public`: role untuk banyak akun pengguna pada pengembangan berikutnya dan tidak memiliki akses ke panel `/admin`.

Authorization diterapkan melalui Laravel policies. Pembatasan tidak hanya dilakukan dengan menyembunyikan menu Filament.

## Integritas Keuangan

- Generate invoice menggunakan database transaction dan mencegah invoice ganda per penghuni/periode.
- Total invoice manual dihitung ulang di backend.
- Due date yang melebihi jumlah hari bulan dijepit ke hari terakhir bulan.
- Invoice hanya menjadi `paid` setelah payment terverifikasi dibuat atau diverifikasi.
- Verifikasi payment memvalidasi invoice, penghuni, status, dan nominal dalam database transaction.
- Invoice dan payment yang memiliki riwayat penting tidak dapat dihapus sembarangan.

## Development

Jalankan server aplikasi, queue worker, log viewer, dan Vite melalui:

```bash
composer run dev
```

Atau jalankan komponennya secara terpisah:

```bash
php artisan serve
php artisan queue:work --tries=3
npm run dev
```

Queue menggunakan driver database secara default dan dapat dipindahkan ke Redis melalui konfigurasi environment.

## Scheduler

Untuk server production, tambahkan satu cron entry Laravel:

```cron
* * * * * cd /path/to/kost && php artisan schedule:run >> /dev/null 2>&1
```

Pada Windows development, gunakan `php artisan schedule:work`. Otomasi invoice/reminder WhatsApp belum diaktifkan pada phase saat ini.

## WhatsApp

Integrasi WhatsApp Cloud API, broadcast, queue pengiriman, webhook, dan delivery log merupakan scope Phase 3–4 dan belum diimplementasikan. Jangan menambahkan token WhatsApp ke source code atau repository.

## Testing dan Quality Check

Test menggunakan SQLite in-memory agar tidak menyentuh `db_kost`:

```bash
php artisan test
vendor/bin/pint --test
npm run build
php artisan migrate:status
```

Pastikan extension `pdo_sqlite` dan `sqlite3` aktif pada PHP CLI.

## Deployment Production

Sebelum deploy:

1. Gunakan kredensial database dan password admin yang kuat.
2. Atur `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` HTTPS.
3. Jalankan `composer install --no-dev --optimize-autoloader` dan `npm ci && npm run build`.
4. Jalankan `php artisan migrate --force`, `php artisan storage:link`, dan `php artisan optimize`.
5. Jalankan queue worker melalui process manager dan scheduler melalui cron.
6. Pastikan web server hanya mengekspos direktori `public`.
7. Jangan deploy `.env`, token, dump database, atau kredensial ke repository publik.

## Catatan Keamanan

- `.env` diabaikan Git dan tidak boleh dikomit.
- Password menggunakan hashed cast Laravel.
- Form Filament memakai CSRF protection dan server-side authorization.
- Bukti pembayaran dibatasi tipe dan ukuran file melalui form Filament.
- Data finansial harus dipulihkan melalui status/cancel, bukan hard delete, bila sudah memiliki relasi pembayaran.
