<?php

namespace App\Models;

use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    protected $fillable = ['tenant_id', 'contract_number', 'starts_at', 'ends_at', 'monthly_price', 'deposit_amount', 'deposit_status', 'status', 'document', 'notes'];

    protected static function booted(): void
    {
        static::created(fn (Contract $contract) => ActivityLog::record('contract.created', $contract));
        static::updated(fn (Contract $contract) => ActivityLog::record('contract.updated', $contract, ['changes' => array_keys($contract->getChanges())]));
    }

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'monthly_price' => 'integer', 'deposit_amount' => 'integer'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
