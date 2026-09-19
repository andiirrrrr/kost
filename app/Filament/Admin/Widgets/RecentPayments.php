<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentPayments extends TableWidget
{
    protected int|string|array $columnSpan = [
        'md' => 'full',
        'xl' => 7,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pembayaran Terbaru')
            ->description('Transaksi terbaru yang masuk dari penghuni.')
            ->query(fn (): Builder => Payment::query()->with(['tenant.room', 'invoice'])->latest('paid_at'))
            ->columns([
                TextColumn::make('tenant.name')->label('Penghuni'),
                TextColumn::make('invoice.invoice_number')->label('Invoice')->placeholder('—'),
                TextColumn::make('tenant.room.room_number')->label('Kamar')->placeholder('—'),
                TextColumn::make('amount')->label('Nominal')->money('IDR')->alignEnd(),
                TextColumn::make('paid_at')->label('Tanggal')->dateTime('d M Y'),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->stackedOnMobile()
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }
}
