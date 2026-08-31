<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'payment_number',
        'amount',
        'payment_method',
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
        'payment_method' => PaymentMethod::class,
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'amount' => 'integer',
    ];

    // Relasi
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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

    // Generate payment number
    public static function generatePaymentNumber()
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $last = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        $sequence = $last ? intval(substr($last->payment_number, -4)) + 1 : 1;

        return sprintf('PAY-%04d%02d-%04d', $year, $month, $sequence);
    }
}
