<?php

namespace App\Filament\Admin\Resources\PaymentMethods\Schemas;

use App\Enums\PaymentMethodCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Metode')
                    ->description('Atur identitas, tujuan pembayaran, dan visibilitas metode kepada penghuni.')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('name')->label('Nama Metode')->required()->maxLength(100),
                        Select::make('category')->label('Kategori')->options(PaymentMethodCategory::options())->required()->native(false)->helperText('Pilih salah satu dari tiga kategori pembayaran.'),
                        TextInput::make('account_number')->label('Nomor Rekening / Tujuan')->maxLength(255),
                        TextInput::make('account_holder')->label('Atas Nama')->maxLength(255),
                        Textarea::make('instructions')->label('Petunjuk Pembayaran')->rows(4)->maxLength(1000)->columnSpanFull(),
                        Toggle::make('is_active')->label('Tampilkan kepada penghuni')->default(true),
                    ]),
            ]);
    }
}
