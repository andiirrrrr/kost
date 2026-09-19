<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingPageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_uses_admin_settings_and_groups_rooms_by_category(): void
    {
        Setting::set('business_name', 'Kost Nusantara');
        Setting::set('tagline', 'Hunian pilihan dari pengelola.');
        $category = RoomCategory::factory()->create([
            'name' => 'Deluxe',
            'description' => 'Kamar luas dengan fasilitas lengkap.',
            'landing_image' => 'landing/room-categories/deluxe.webp',
            'facilities' => ['AC', 'WiFi'],
        ]);
        Room::factory()->for($category, 'roomCategory')->create([
            'room_number' => 'A-01',
            'room_name' => 'Nomor kamar tidak ditampilkan',
            'monthly_price' => 900000,
            'status' => RoomStatus::AVAILABLE,
        ]);
        Room::factory()->for($category, 'roomCategory')->create([
            'room_number' => 'B-02',
            'monthly_price' => 800000,
            'status' => RoomStatus::OCCUPIED,
        ]);
        Room::factory()->for($category, 'roomCategory')->create([
            'room_number' => 'C-03',
            'status' => RoomStatus::MAINTENANCE,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Kost Nusantara')
            ->assertSee('Hunian pilihan dari pengelola.')
            ->assertSee('Deluxe')
            ->assertSee('Sisa 1 kamar')
            ->assertSee('2 unit dalam kategori ini')
            ->assertSee('Mulai Rp800.000')
            ->assertSee('AC')
            ->assertSee('storage/landing/room-categories/deluxe.webp')
            ->assertDontSee('Nomor kamar tidak ditampilkan');
    }

    public function test_landing_page_uses_safe_defaults_when_settings_and_rooms_are_empty(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Heritage Residential')
            ->assertSee('Belum ada kategori kamar untuk ditampilkan.');
    }

    public function test_landing_page_displays_full_category_and_hides_unlisted_categories(): void
    {
        $fullCategory = RoomCategory::factory()->create([
            'name' => 'Premium',
            'description' => 'Kamar premium dengan fasilitas lengkap.',
            'facilities' => ['AC', 'Water heater'],
        ]);
        Room::factory()->for($fullCategory, 'roomCategory')->create([
            'room_number' => 'P-01',
            'status' => RoomStatus::OCCUPIED,
        ]);
        $inactiveCategory = RoomCategory::factory()->create([
            'name' => 'Kategori Nonaktif',
            'is_active' => false,
        ]);
        Room::factory()->for($inactiveCategory, 'roomCategory')->create([
            'status' => RoomStatus::AVAILABLE,
        ]);
        Room::factory()->create([
            'room_name' => 'Kamar Tanpa Kategori',
            'status' => RoomStatus::AVAILABLE,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Premium')
            ->assertSee('Penuh')
            ->assertSee('Saat Ini Penuh')
            ->assertDontSee('Kategori Nonaktif')
            ->assertDontSee('Kamar Tanpa Kategori');
    }

    public function test_landing_page_escapes_admin_managed_text(): void
    {
        Setting::set('business_name', '<script>alert("unsafe")</script>');
        $category = RoomCategory::factory()->create([
            'name' => '<script>alert("category")</script>',
            'description' => '<script>alert("description")</script>',
        ]);
        Room::factory()->for($category, 'roomCategory')->create([
            'status' => RoomStatus::AVAILABLE,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<script>alert("unsafe")</script>', false)
            ->assertDontSee('<script>alert("category")</script>', false)
            ->assertDontSee('<script>alert("description")</script>', false);
    }

    public function test_landing_page_displays_google_maps_iframe_when_configured(): void
    {
        $iframe = '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.294" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
        Setting::set('maps_iframe', $iframe);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.294')
            ->assertSee('Peta Lokasi');
    }

    public function test_landing_page_does_not_render_unsafe_iframe_source(): void
    {
        Setting::set('maps_iframe', '<iframe src="https://evil.com/phish" onload="alert(1)"></iframe>');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('https://evil.com/phish')
            ->assertDontSee('alert(1)', false)
            ->assertSee('Lokasi Heritage Residential');
    }

    public function test_landing_page_resolves_landmark_icons_matching_their_labels(): void
    {
        $landmarks = [
            ['label' => '5 menit ke transportasi umum'],
            ['label' => '10 menit ke pusat bisnis'],
            ['label' => '8 menit ke pusat kuliner'],
            ['label' => '2 menit ke rumah sakit'],
            ['label' => '3 menit ke kampus universitas'],
            ['label' => '4 menit ke stasiun MRT'],
        ];
        Setting::set('landmarks', json_encode($landmarks));

        $response = $this->get('/');
        $response->assertOk();

        $response->assertSee('directions_bus');
        $response->assertSee('business');
        $response->assertSee('restaurant');
        $response->assertSee('local_hospital');
        $response->assertSee('school');
        $response->assertSee('train');
    }
}
