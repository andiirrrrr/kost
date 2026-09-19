<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'room_id', 'scheduled_room_id', 'name', 'phone', 'email', 'identity_number', 'identity_document',
        'address', 'emergency_contact', 'move_in_date', 'move_out_date',
        'scheduled_transfer_date', 'monthly_price', 'due_day', 'status', 'notes',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'status' => TenantStatus::class,
        'move_in_date' => 'date',
        'move_out_date' => 'date',
        'scheduled_transfer_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scheduledRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'scheduled_room_id');
    }

    // Auto-normalisasi nomor HP
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = $this->normalizePhone($value);
    }

    private function normalizePhone($phone)
    {
        // Bersihkan karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Jika diawali 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62'.substr($phone, 1);
        }
        // Jika diawali +62, hilangkan +
        if (substr($phone, 0, 2) === '62') {
            // sudah benar
        }

        return $phone;
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function roomHistories(): HasMany
    {
        return $this->hasMany(TenantRoomHistory::class);
    }

    public function canAccessPortal(): bool
    {
        return $this->status === TenantStatus::ACTIVE
            && $this->move_out_date === null
            && $this->move_in_date->lte(today());
    }
}
