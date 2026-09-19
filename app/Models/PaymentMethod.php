<?php

namespace App\Models;

use App\Enums\PaymentMethodCategory;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::created(fn (PaymentMethod $method) => ActivityLog::record('payment_method.created', $method));
        static::updated(fn (PaymentMethod $method) => ActivityLog::record('payment_method.updated', $method, ['changes' => array_keys($method->getChanges())]));
        static::deleted(fn (PaymentMethod $method) => ActivityLog::record('payment_method.deleted', $method));
    }

    protected $fillable = [
        'name',
        'code',
        'category',
        'icon',
        'instructions',
        'account_number',
        'account_holder',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'category' => PaymentMethodCategory::class,
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
