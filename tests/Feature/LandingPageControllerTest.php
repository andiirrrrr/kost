<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingPageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_uses_admin_settings_and_room_data(): void
    {
        Setting::set('business_name', 'Kost Nusantara');
        Setting::set('tagline', 'Hunian pilihan dari pengelola.');
        Room::factory()->create([
            'room_number' => 'A-01',
            'room_name' => 'Kamar Deluxe',
            'status' => RoomStatus::AVAILABLE,
            'facilities' => ['AC', 'WiFi'],
        ]);
        Room::factory()->create([
            'room_number' => 'B-02',
            'room_name' => 'Kamar Terisi',
            'status' => RoomStatus::OCCUPIED,
        ]);
        Room::factory()->create([
            'room_number' => 'C-03',
            'room_name' => 'Sedang Dirawat',
            'status' => RoomStatus::MAINTENANCE,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Kost Nusantara')
            ->assertSee('Hunian pilihan dari pengelola.')
            ->assertSee('Kamar Deluxe')
            ->assertSee('Kamar Terisi')
            ->assertSee('AC')
            ->assertDontSee('Sedang Dirawat');
    }

    public function test_landing_page_uses_safe_defaults_when_settings_and_rooms_are_empty(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Heritage Residential')
            ->assertSee('Belum ada data kamar untuk ditampilkan.');
    }

    public function test_landing_page_escapes_admin_managed_text(): void
    {
        Setting::set('business_name', '<script>alert("unsafe")</script>');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }
}
