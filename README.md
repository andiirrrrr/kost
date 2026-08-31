# Kost Management System

Aplikasi manajemen kost berbasis Laravel dan Filament untuk mengelola kamar, penghuni, tagihan bulanan, serta pembayaran dari satu panel admin.

## Status Pengembangan

- Phase 1 — Foundation: selesai.
- Phase 2 — Finance Core: invoices, payments, expenses, dashboard keuangan, dan laporan dasar selesai.
- Phase 2.5 — Stabilization & Hardening: authorization, integritas pembayaran, test, dan keamanan seeder diterapkan.
- Phase 3 — WhatsApp Integration: WhatsApp Center, template, broadcast queue, dan delivery log selesai.
- Phase 4 — Automation & Webhook: invoice bulanan, status terlambat, reminder terjadwal, serta webhook status WhatsApp selesai.
- Phase 4.5 — Tenant Portal: landing page, autentikasi penghuni, tagihan, pembayaran, notifikasi, pengumuman, dan profil selesai.
- Phase 5 dan seterusnya: belum dikerjakan.

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

- Owner: `admin@kost.test`, masuk melalui `/admin`.
- Tenant: `tenant@kost.test`, terhubung ke Budi Santoso kamar A1 dan masuk melalui `/login`.
- Password acak yang aman ditampilkan satu kali ketika akun pertama kali dibuat oleh seeder.
- Untuk kredensial tetap di lingkungan lokal, isi `DEMO_OWNER_PASSWORD` dan `DEMO_TENANT_PASSWORD` pada `.env` sebelum menjalankan seeder.

Menjalankan seeder kembali tidak akan mereset password acak akun yang sudah ada. Jangan gunakan data atau kredensial demo pada production.

## Role dan Permission

- `owner`: dapat mengakses panel `/admin` dan seluruh fitur pengelolaan.
- `tenant`: hanya dapat mengakses portal penghuni dan data yang terhubung dengan profil tenant miliknya.
- `public`: role kompatibilitas tanpa akses panel admin maupun portal penghuni.

Authorization diterapkan melalui Laravel policies. Pembatasan tidak hanya dilakukan dengan menyembunyikan menu Filament.

## Tenant Portal

- Landing page publik: `/`
- Login penghuni: `/login`
- Dashboard penghuni: `/dashboard`
- Admin membuat akun login melalui action **Buat Akun Penghuni** pada resource Penghuni.
- Bukti pembayaran disimpan pada disk private dan hanya dapat diunduh melalui endpoint yang memeriksa kepemilikan atau permission admin.
- Nominal pembayaran selalu diambil dari total invoice oleh backend; tenant tidak dapat mengubahnya.

## Integritas Keuangan

- Generate invoice menggunakan database transaction dan mencegah invoice ganda per penghuni/periode.
- Total invoice manual dihitung ulang di backend.
- Due date yang melebihi jumlah hari bulan dijepit ke hari terakhir bulan.
- Invoice hanya menjadi `paid` setelah payment terverifikasi dibuat atau diverifikasi.
- Verifikasi payment memvalidasi invoice, penghuni, status, dan nominal dalam database transaction.
- Invoice dan payment yang memiliki riwayat penting tidak dapat dihapus sembarangan.
- Pengeluaran menggunakan soft delete agar histori finansial tetap dapat dipulihkan.
- Bukti pengeluaran disimpan melalui Laravel Storage dengan nama file acak berbasis UUID.

## Dashboard Keuangan

Dashboard admin menampilkan okupansi kamar, total tagihan, pembayaran terverifikasi, tagihan outstanding dan terlambat, pengeluaran, serta estimasi bersih bulan berjalan. Chart membandingkan pemasukan, pengeluaran, dan estimasi bersih selama 12 bulan serta distribusi status invoice bulan ini.

Estimasi Bersih adalah pemasukan terverifikasi dikurangi pengeluaran tercatat dan bukan profit accounting resmi.

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

Pada Windows development, gunakan `php artisan schedule:work`.

Scheduler menjalankan:

- pembuatan invoice bulanan setiap tanggal 1 pukul 00:05;
- pembaruan status invoice terlambat setiap hari pukul 00:10;
- reminder WhatsApp setiap hari pukul 08:00.

Semua waktu mengikuti `APP_TIMEZONE`. Pembuatan invoice otomatis aktif secara default. Reminder otomatis dinonaktifkan secara default dan menggunakan pengaturan `automatic_reminder_enabled`, `reminder_before_days`, `reminder_due_date_enabled`, serta `reminder_after_days` pada tabel `settings`. Task dilindungi dari eksekusi tumpang tindih dan hanya dijalankan oleh satu scheduler server.

## WhatsApp

Integrasi menggunakan WhatsApp Business Cloud API resmi Meta. Isi konfigurasi berikut pada `.env`:

```dotenv
WHATSAPP_ENABLED=true
WHATSAPP_API_URL=https://graph.facebook.com
WHATSAPP_API_VERSION=v23.0
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_BUSINESS_ACCOUNT_ID=
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_APP_SECRET=
WHATSAPP_VERIFY_TOKEN=
WHATSAPP_TIMEOUT=10
WHATSAPP_CONNECT_TIMEOUT=5
```

Nama template di menu **Template WhatsApp** harus sama dengan template yang telah disetujui di WhatsApp Manager Meta. Seeder menyediakan lima struktur template awal untuk tagihan, reminder, keterlambatan, pembayaran, dan pengumuman; approval template tetap dilakukan melalui Meta.

Broadcast tidak dikirim dalam request browser. Sistem membuat satu log dan satu queued job untuk setiap nomor valid. Jalankan worker:

```bash
php artisan queue:work --tries=3 --timeout=60
```

Daftarkan URL callback `/webhooks/whatsapp` di Meta WhatsApp Manager menggunakan nilai `WHATSAPP_VERIFY_TOKEN` yang sama. Request event diverifikasi menggunakan signature `X-Hub-Signature-256` dan `WHATSAPP_APP_SECRET`. Webhook memperbarui status menjadi `sent`, `delivered`, `read`, atau `failed` berdasarkan `message_id`, serta mengabaikan event lama yang dapat menurunkan status pesan.

Access token hanya dibaca dari konfigurasi, tidak ditampilkan di panel, dan tidak dicatat dalam log.

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
