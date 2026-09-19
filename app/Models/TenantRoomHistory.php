<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRoomHistory extends Model
{
    protected $fillable = ['tenant_id', 'room_id', 'starts_at', 'ends_at', 'monthly_price', 'due_day', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'monthly_price' => 'integer', 'due_day' => 'integer'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
