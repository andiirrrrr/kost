<?php

namespace Tests\Feature\Filament;

use App\Enums\BroadcastStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoomStatus;
use App\Filament\Admin\Resources\Rooms\RoomResource;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use Tests\TestCase;

class IndonesianLocalizationTest extends TestCase
{
    public function test_application_and_filament_login_use_indonesian(): void
    {
        $this->assertSame('id', app()->getLocale());

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Masuk')
            ->assertSee('Alamat Email')
            ->assertDontSee('Sign in');
    }

    public function test_admin_resources_and_statuses_have_indonesian_labels(): void
    {
        $this->assertSame('Kamar', RoomResource::getNavigationLabel());
        $this->assertSame('Penghuni', TenantResource::getNavigationLabel());
        $this->assertSame('Tersedia', RoomStatus::AVAILABLE->getLabel());
        $this->assertSame('Belum Dibayar', InvoiceStatus::UNPAID->getLabel());
        $this->assertSame('Terverifikasi', PaymentStatus::VERIFIED->getLabel());
        $this->assertSame('Konsep', BroadcastStatus::DRAFT->getLabel());
    }
}
