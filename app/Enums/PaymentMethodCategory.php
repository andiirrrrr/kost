<?php

namespace App\Enums;

enum PaymentMethodCategory: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case E_WALLET = 'e_wallet';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank Transfer',
            self::E_WALLET => 'E-Wallet',
            self::CASH => 'Tunai',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'account_balance',
            self::E_WALLET => 'account_balance_wallet',
            self::CASH => 'payments',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $category): array => [
            $category->value => $category->label(),
        ])->all();
    }
}
