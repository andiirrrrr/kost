<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExpenseCategory: string implements HasLabel
{
    case ELECTRICITY = 'electricity';
    case WATER = 'water';
    case INTERNET = 'internet';
    case MAINTENANCE = 'maintenance';
    case SALARY = 'salary';
    case CLEANING = 'cleaning';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ELECTRICITY => 'Listrik',
            self::WATER => 'Air',
            self::INTERNET => 'Internet',
            self::MAINTENANCE => 'Perawatan',
            self::SALARY => 'Gaji',
            self::CLEANING => 'Kebersihan',
            self::OTHER => 'Lainnya',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
