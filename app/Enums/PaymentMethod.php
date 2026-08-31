<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case QRIS = 'qris';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Transfer Bank',
            self::CASH => 'Tunai',
            self::QRIS => 'QRIS',
            self::OTHER => 'Lainnya',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
