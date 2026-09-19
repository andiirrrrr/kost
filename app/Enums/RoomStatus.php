<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RoomStatus: string implements HasColor, HasLabel
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case OCCUPIED = 'occupied';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::RESERVED => 'Dipesan',
            self::OCCUPIED => 'Terisi',
            self::MAINTENANCE => 'Perawatan',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::RESERVED => 'warning',
            self::OCCUPIED => 'info',
            self::MAINTENANCE => 'warning',
        };
    }
}
