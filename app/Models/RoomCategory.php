<?php

namespace App\Models;

use Database\Factories\RoomCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomCategory extends Model
{
    /** @use HasFactory<RoomCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'landing_image',
        'base_monthly_price',
        'facilities',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_monthly_price' => 'decimal:2',
            'facilities' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
