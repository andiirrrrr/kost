<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BroadcastAudience: string implements HasLabel
{
    case ALL = 'all';
    case UNPAID = 'unpaid';
    case OVERDUE = 'overdue';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Semua Penghuni',
            self::UNPAID => 'Belum Bayar',
            self::OVERDUE => 'Terlambat',
            self::MANUAL => 'Pilih Manual',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
