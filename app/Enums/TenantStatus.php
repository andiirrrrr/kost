<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TenantStatus: string implements HasColor, HasLabel
{
    case ACTIVE = 'active';
    case SCHEDULED = 'scheduled';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif',
            self::SCHEDULED => 'Terjadwal',
            self::INACTIVE => 'Tidak Aktif',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::SCHEDULED => 'warning',
            self::INACTIVE => 'gray',
        };
    }
}
