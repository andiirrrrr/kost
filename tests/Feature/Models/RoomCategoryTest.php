<?php

namespace Tests\Feature\Models;

use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RoomCategoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_room_uses_category_name_and_combines_category_facilities(): void
    {
        $category = RoomCategory::factory()->create([
            'name' => 'Premium',
            'facilities' => ['AC', 'WiFi'],
        ]);
        $room = Room::factory()->for($category, 'roomCategory')->create([
            'type' => null,
            'facilities' => ['WiFi', 'Meja kerja'],
        ]);

        $this->assertSame('Premium', $room->categoryName());
        $this->assertSame(['AC', 'WiFi', 'Meja kerja'], $room->availableFacilities());
        $this->assertTrue($category->rooms->contains($room));
    }
}
