<?php

namespace App\Models;

use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::updated(function (Room $room): void {
            $trackedChanges = array_intersect(array_keys($room->getChanges()), ['monthly_price', 'status', 'room_category_id']);

            if ($trackedChanges !== []) {
                ActivityLog::record('room.updated', $room, ['changes' => $trackedChanges]);
            }
        });
    }

    protected $fillable = [
        'room_number', 'room_name', 'room_category_id', 'type', 'monthly_price',
        'status', 'description', 'landing_image', 'facilities',
    ];

    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class);
    }

    /** @return list<string> */
    public function availableFacilities(): array
    {
        return array_values(array_unique([
            ...($this->roomCategory?->facilities ?? []),
            ...($this->facilities ?? []),
        ]));
    }

    public function categoryName(): ?string
    {
        return $this->roomCategory?->name ?? $this->type;
    }

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'status' => RoomStatus::class,
        'facilities' => 'array',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
