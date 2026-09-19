<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\RoomCategories\Pages\CreateRoomCategory;
use App\Filament\Admin\Resources\RoomCategories\RoomCategoryResource;
use App\Filament\Admin\Resources\Rooms\Pages\CreateRoom;
use App\Models\RoomCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoomCategoryResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_room_photo_is_managed_on_category_form_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        Livewire::actingAs($owner)
            ->test(CreateRoomCategory::class)
            ->assertFormFieldExists('landing_image');

        Livewire::actingAs($owner)
            ->test(CreateRoom::class)
            ->assertFormFieldDoesNotExist('room_name')
            ->assertFormFieldDoesNotExist('landing_image')
            ->assertFormFieldDoesNotExist('description')
            ->assertFormFieldDoesNotExist('facilities');
    }

    public function test_owner_can_create_room_category_from_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();

        Livewire::actingAs($owner)
            ->test(CreateRoomCategory::class)
            ->fillForm([
                'name' => 'Deluxe',
                'description' => 'Kamar luas dan nyaman.',
                'base_monthly_price' => 1750000,
                'facilities' => ['AC', 'WiFi', 'Water heater'],
                'is_active' => true,
                'sort_order' => 4,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = RoomCategory::query()->where('name', 'Deluxe')->sole();

        $this->assertSame('1750000.00', $category->base_monthly_price);
        $this->assertSame(['AC', 'WiFi', 'Water heater'], $category->facilities);
        $this->assertTrue($category->is_active);
    }

    public function test_selecting_room_category_fills_room_defaults(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('email', config('demo.owner_email'))->sole();
        $category = RoomCategory::factory()->create([
            'name' => 'Deluxe',
            'description' => 'Kamar luas dan nyaman.',
            'base_monthly_price' => 1750000,
            'facilities' => ['AC', 'WiFi'],
        ]);

        Livewire::actingAs($owner)
            ->test(CreateRoom::class)
            ->fillForm(['room_category_id' => $category->id])
            ->assertFormSet([
                'type' => 'Deluxe',
                'monthly_price' => '1750000.00',
            ]);
    }

    public function test_tenant_cannot_open_room_category_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = User::factory()->create();
        $tenant->assignRole('tenant');

        $this->actingAs($tenant)
            ->get(RoomCategoryResource::getUrl('index'))
            ->assertForbidden();
    }
}
