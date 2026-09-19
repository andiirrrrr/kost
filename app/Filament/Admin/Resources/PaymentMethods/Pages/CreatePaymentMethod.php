<?php

namespace App\Filament\Admin\Resources\PaymentMethods\Pages;

use App\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;
use App\Models\PaymentMethod;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $baseCode = Str::slug($data['name'], '_') ?: 'metode_pembayaran';
        $code = $baseCode;
        $sequence = 2;

        while (PaymentMethod::withTrashed()->where('code', $code)->exists()) {
            $code = $baseCode.'_'.$sequence++;
        }

        $data['code'] = $code;
        $data['sort_order'] = (int) PaymentMethod::withTrashed()->max('sort_order') + 1;

        return $data;
    }
}
