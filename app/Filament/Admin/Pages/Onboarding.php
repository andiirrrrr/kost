<?php

namespace App\Filament\Admin\Pages;

use App\Enums\RoomStatus;
use App\Imports\OnboardingSpreadsheetImport;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Services\TenantLifecycleService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

class Onboarding extends Page
{
    use WithFileUploads;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected string $view = 'filament.admin.pages.onboarding';

    protected static ?string $navigationLabel = 'Mulai & Impor Data';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem & Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Mulai & Impor Data';

    public ?TemporaryUploadedFile $spreadsheet = null;

    /** @var array<int, array<string, mixed>> */
    public array $previewRows = [];

    /** @var list<string> */
    public array $previewErrors = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-rocket-launch';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public function preview(): void
    {
        $this->validateSpreadsheet();
        [$rows, $errors] = $this->readSpreadsheet();
        $this->previewRows = array_slice($rows, 0, 20);
        $this->previewErrors = $errors;

        if ($errors === []) {
            Notification::make()->success()->title(count($rows).' baris siap diimpor')->send();
        }
    }

    public function import(TenantLifecycleService $tenantLifecycle): void
    {
        $this->validateSpreadsheet();
        [$rows, $errors] = $this->readSpreadsheet();

        if ($errors !== []) {
            $this->previewRows = array_slice($rows, 0, 20);
            $this->previewErrors = $errors;
            throw ValidationException::withMessages(['spreadsheet' => 'Perbaiki data Excel yang ditandai sebelum mengimpor.']);
        }

        $imported = DB::transaction(function () use ($rows, $tenantLifecycle): int {
            foreach ($rows as $row) {
                $category = RoomCategory::query()->firstOrCreate(
                    ['name' => $row['kategori']],
                    ['base_monthly_price' => $row['harga_bulanan'], 'is_active' => true],
                );
                $room = Room::query()->updateOrCreate(
                    ['room_number' => $row['nomor_kamar']],
                    ['room_category_id' => $category->id, 'monthly_price' => $row['harga_bulanan'], 'status' => RoomStatus::AVAILABLE],
                );

                if ($row['nama_penghuni'] !== '') {
                    $tenantLifecycle->create([
                        'room_id' => $room->id,
                        'name' => $row['nama_penghuni'],
                        'phone' => $row['telepon'],
                        'email' => $row['email'] ?: null,
                        'move_in_date' => $row['tanggal_masuk'] ?: now()->toDateString(),
                        'monthly_price' => $row['harga_bulanan'],
                        'due_day' => 5,
                        'status' => 'active',
                    ]);
                }
            }

            return count($rows);
        });

        $this->reset(['spreadsheet', 'previewRows', 'previewErrors']);
        Notification::make()->success()->title("{$imported} kamar berhasil diimpor")->send();
    }

    private function validateSpreadsheet(): void
    {
        $this->validate(['spreadsheet' => ['required', 'file', 'mimes:xlsx', 'max:5120']], [
            'spreadsheet.mimes' => 'Gunakan file Excel dengan format .xlsx.',
            'spreadsheet.max' => 'Ukuran file maksimal 5 MB.',
        ]);
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: list<string>} */
    private function readSpreadsheet(): array
    {
        try {
            $rows = Excel::toArray(new OnboardingSpreadsheetImport, $this->spreadsheet)[0] ?? [];
        } catch (Throwable) {
            return [[], ['File tidak dapat dibaca. Pastikan file Excel tidak rusak.']];
        }

        $required = ['nomor_kamar', 'kategori', 'harga_bulanan'];
        if (array_diff($required, array_keys($rows[0] ?? [])) !== []) {
            return [[], ['Kolom wajib: nomor_kamar, kategori, harga_bulanan. Gunakan template yang tersedia.']];
        }

        $normalizedRows = [];
        $errors = [];
        $roomNumbers = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $normalized = $this->normalizeRow($row);
            $normalizedRows[] = $normalized;

            if ($normalized['nomor_kamar'] === '' || $normalized['kategori'] === '') {
                $errors[] = "Baris {$line}: nomor kamar dan kategori wajib diisi.";
            }
            if (! is_numeric($row['harga_bulanan'] ?? null) || $normalized['harga_bulanan'] <= 0) {
                $errors[] = "Baris {$line}: harga bulanan harus berupa angka lebih dari 0.";
            }
            if (in_array($normalized['nomor_kamar'], $roomNumbers, true)) {
                $errors[] = "Baris {$line}: nomor kamar duplikat dalam file.";
            }
            if ($normalized['email'] !== '' && filter_var($normalized['email'], FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = "Baris {$line}: format email tidak valid.";
            }
            if ($normalized['nama_penghuni'] !== '' && $normalized['telepon'] === '') {
                $errors[] = "Baris {$line}: telepon wajib diisi jika nama penghuni diisi.";
            }
            if ($normalized['tanggal_masuk'] !== '' && ! $normalized['tanggal_masuk_valid']) {
                $errors[] = "Baris {$line}: tanggal masuk tidak valid. Gunakan format YYYY-MM-DD.";
            }
            $roomNumbers[] = $normalized['nomor_kamar'];
        }

        if ($normalizedRows === []) {
            $errors[] = 'File Excel tidak memiliki baris data.';
        }

        return [$normalizedRows, $errors];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function normalizeRow(array $row): array
    {
        $date = $row['tanggal_masuk'] ?? '';
        $dateIsValid = true;
        if (is_numeric($date) && (float) $date > 0) {
            $date = Carbon::instance(Date::excelToDateTimeObject((float) $date))->toDateString();
        } elseif ($date !== '') {
            try {
                $date = Carbon::parse((string) $date)->toDateString();
            } catch (Throwable) {
                $date = (string) $date;
                $dateIsValid = false;
            }
        }

        return [
            'nomor_kamar' => trim((string) ($row['nomor_kamar'] ?? '')),
            'kategori' => trim((string) ($row['kategori'] ?? '')),
            'harga_bulanan' => (int) ($row['harga_bulanan'] ?? 0),
            'nama_penghuni' => trim((string) ($row['nama_penghuni'] ?? '')),
            'telepon' => trim((string) ($row['telepon'] ?? '')),
            'email' => trim((string) ($row['email'] ?? '')),
            'tanggal_masuk' => $date,
            'tanggal_masuk_valid' => $dateIsValid,
        ];
    }
}
