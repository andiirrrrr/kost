<?php

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(255),
                Forms\Components\Textarea::make('content')->label('Isi Pengumuman')->required()->rows(8)->columnSpanFull(),
                Forms\Components\DateTimePicker::make('expires_at')->label('Berakhir Pada')->after('now'),
            ]);
    }
}
