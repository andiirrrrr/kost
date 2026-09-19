<?php

namespace Tests\Feature\Filament;

use App\Enums\RoomStatus;
use App\Enums\TenantStatus;
use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TenantFormTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_selects_use_custom_dropdowns_by_default(): void
    {
        $this->assertFalse(Select::make('form_status')->isNative());
        $this->assertFalse(SelectFilter::make('filter_status')->isNative());
        $this->assertFalse(SelectColumn::make('column_status')->isNative());
    }

    public function test_selecting_category_and_room_fills_monthly_price(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', config('demo.owner_email'))->sole();
        $category = RoomCategory::factory()->create(['base_monthly_price' => 1500000]);
        $room = Room::factory()->for($category, 'roomCategory')->create([
            'monthly_price' => 1650000,
            'status' => RoomStatus::AVAILABLE,
        ]);

        Livewire::actingAs($owner)
            ->test(CreateTenant::class)
            ->fillForm(['room_category_id' => $category->id])
            ->assertFormSet(['monthly_price' => '1500000.00'])
            ->fillForm(['room_id' => $room->id])
            ->assertFormSet(['monthly_price' => '1650000.00']);
    }

    public function test_editing_tenant_prefills_existing_room_category_and_room(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', config('demo.owner_email'))->sole();
        $tenant = Tenant::query()->with('room')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(EditTenant::class, ['record' => $tenant->getRouteKey()])
            ->assertFormSet([
                'room_category_id' => $tenant->room?->room_category_id,
                'room_id' => $tenant->room_id,
            ]);
    }

    public function test_owner_can_store_private_identity_document_when_creating_tenant(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', config('demo.owner_email'))->sole();
        $category = RoomCategory::factory()->create();
        $room = Room::factory()->for($category, 'roomCategory')->create(['status' => RoomStatus::AVAILABLE]);

        Livewire::actingAs($owner)
            ->test(CreateTenant::class)
            ->fillForm([
                'room_category_id' => $category->id,
                'room_id' => $room->id,
                'monthly_price' => $room->monthly_price,
                'status' => TenantStatus::ACTIVE,
                'name' => 'Siti Rahma',
                'phone' => '089876543210',
                'identity_number' => '3273012345678901',
                'identity_document' => UploadedFile::fake()->image('ktp.jpg'),
                'move_in_date' => '2026-09-08',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tenant = Tenant::query()->where('name', 'Siti Rahma')->sole();

        $this->assertNotNull($tenant->identity_document);
        Storage::disk('local')->assertExists($tenant->identity_document);
    }
}
