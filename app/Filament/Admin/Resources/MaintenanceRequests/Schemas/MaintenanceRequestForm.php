<?php

namespace App\Filament\Admin\Resources\MaintenanceRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Laporan Kerusakan')
                    ->description('Lengkapi laporan, status penanganan, biaya, dan hasil penyelesaian.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Select::make('tenant_id')->label('Penghuni')->relationship('tenant', 'name')->searchable()->preload(),
                        Select::make('room_id')->label('Kamar')->relationship('room', 'room_number')->searchable()->preload()->required(),
                        TextInput::make('title')->label('Masalah')->required()->maxLength(150)->columnSpanFull(),
                        Textarea::make('description')->label('Detail')->required()->columnSpanFull(),
                        Select::make('priority')->label('Prioritas')->options(['low' => 'Rendah', 'normal' => 'Normal', 'urgent' => 'Mendesak'])->required()->default('normal'),
                        Select::make('status')->label('Status')->options(['reported' => 'Baru', 'in_progress' => 'Diproses', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'])->required()->default('reported'),
                        FileUpload::make('photo')->label('Foto')->image()->disk('local')->directory('maintenance')->maxSize(4096)->columnSpanFull(),
                        TextInput::make('cost')->label('Biaya Perbaikan')->prefix('Rp')->numeric()->default(0)->minValue(0)->helperText('Setelah status Selesai, gunakan aksi “Catat sebagai pengeluaran” agar tidak tercatat dua kali.'),
                        DateTimePicker::make('completed_at')->label('Selesai Pada'),
                        Textarea::make('resolution_notes')->label('Catatan Penyelesaian')->columnSpanFull(),
                    ]),
            ]);
    }
}
