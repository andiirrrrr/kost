<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AnnouncementResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_announcement_list_and_create_form(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@kost.test')->sole();

        $this->actingAs($admin)->get('/admin/announcements')->assertOk();
        $this->actingAs($admin)->get('/admin/announcements/create')->assertOk();
    }
}
