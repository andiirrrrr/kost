<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'room_id',
        'invoice_number',
        'period_month',
        'period_year',
        'base_amount',
        'electricity_amount',
        'water_amount',
        'other_amount',
        'discount_amount',
        'total_amount',
        'due_date',
        'status',
        'notes',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'base_amount' => 'integer',
        'electricity_amount' => 'integer',
        'water_amount' => 'integer',
        'other_amount' => 'integer',
        'discount_amount' => 'integer',
        'total_amount' => 'integer',
    ];

    // Relasi
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scope
    public function scopeUnpaid($query)
    {
        return $query->where('status', InvoiceStatus::UNPAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatus::OVERDUE);
    }

    // Generate invoice number
    public static function generateInvoiceNumber($month, $year)
    {
        $last = self::withTrashed()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->orderBy('id', 'desc')
            ->first();
        $sequence = $last ? intval(substr($last->invoice_number, -4)) + 1 : 1;

        return sprintf('INV-%04d%02d-%04d', $year, $month, $sequence);
    }
}
