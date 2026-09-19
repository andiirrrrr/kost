<?php

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Isi Pengumuman')
                    ->description('Susun informasi yang akan dibaca oleh penghuni.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(255),
                        Forms\Components\DateTimePicker::make('expires_at')->label('Berakhir Pada')->after('now'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Publikasikan Sekarang')
                            ->default(true)
                            ->helperText('Jika diaktifkan, pengumuman akan langsung dikirimkan ke notifikasi seluruh penghuni.')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('content')->label('Isi Pengumuman')->required()->rows(8)->columnSpanFull(),
                    ]),
            ]);
    }
}
