<?php

namespace Database\Seeders;

use App\Models\RoomCategory;
use Illuminate\Database\Seeder;

class RoomCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Standard', 'description' => 'Kamar nyaman untuk kebutuhan harian.', 'base_monthly_price' => 800000, 'facilities' => ['WiFi', 'Kasur', 'Lemari'], 'sort_order' => 1],
            ['name' => 'Premium', 'description' => 'Kamar lebih luas dengan fasilitas tambahan.', 'base_monthly_price' => 1200000, 'facilities' => ['AC', 'WiFi', 'Kamar mandi dalam', 'Kasur', 'Lemari'], 'sort_order' => 2],
            ['name' => 'Eksklusif', 'description' => 'Kamar dengan fasilitas paling lengkap.', 'base_monthly_price' => 1500000, 'facilities' => ['AC', 'WiFi', 'Kamar mandi dalam', 'Water heater', 'Kasur', 'Lemari'], 'sort_order' => 3],
        ] as $category) {
            RoomCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                [...$category, 'is_active' => true],
            );
        }
    }
}
