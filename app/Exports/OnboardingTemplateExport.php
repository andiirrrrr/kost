<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OnboardingTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            ['A01', 'Ekonomis', 750000, 'Budi Santoso', '081234567890', 'budi@example.com', now()->format('Y-m-d')],
            ['A02', 'Ekonomis', 750000, '', '', '', ''],
        ];
    }

    public function headings(): array
    {
        return ['nomor_kamar', 'kategori', 'harga_bulanan', 'nama_penghuni', 'telepon', 'email', 'tanggal_masuk'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '17233C']],
            ],
        ];
    }
}
