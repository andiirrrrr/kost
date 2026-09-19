<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updated(function (Payment $payment): void {
            if ($payment->wasChanged('status')) {
                ActivityLog::record('payment.status_changed', $payment, ['from' => $payment->getOriginal('status'), 'to' => $payment->status->value, 'verified_by' => $payment->verified_by]);
            }
        });
    }

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'payment_number',
        'amount',
        'payment_method',
        'payment_method_id',
        'paid_at',
        'proof',
        'status',
        'rejection_reason',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'amount' => 'integer',
    ];

    // Relasi
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id')->withTrashed();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scope
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', PaymentStatus::VERIFIED);
    }

    // Generate payment number (concurrency-safe)
    public static function generatePaymentNumber(): string
    {
        return DB::transaction(function (): string {
            $year = now()->format('Y');
            $month = now()->format('m');
            $last = self::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();
            $sequence = $last ? intval(substr($last->payment_number, -4)) + 1 : 1;

            return sprintf('PAY-%04d%02d-%04d', $year, $month, $sequence);
        });
    }
}
