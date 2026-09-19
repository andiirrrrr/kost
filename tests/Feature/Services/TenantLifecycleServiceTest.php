<?php

namespace Tests\Feature\Services;

use App\Enums\InvoiceStatus;
use App\Enums\RoomStatus;
use App\Enums\TenantStatus;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\TenantRoomHistory;
use App\Services\TenantLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TenantLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_move_and_check_out_keep_room_status_in_sync(): void
    {
        $firstRoom = Room::factory()->create();
        $secondRoom = Room::factory()->create();
        $service = app(TenantLifecycleService::class);

        $tenant = $service->create([...$this->tenantData($firstRoom), 'move_in_date' => now()->subDay()->toDateString()]);
        $this->assertSame(RoomStatus::OCCUPIED, $firstRoom->fresh()->status);

        $service->transferRoom($tenant, $secondRoom, now()->toDateString());
        $this->assertSame(RoomStatus::AVAILABLE, $firstRoom->fresh()->status);
        $this->assertSame(RoomStatus::OCCUPIED, $secondRoom->fresh()->status);

        $service->checkOut($tenant->fresh(), now()->toDateString());
        $this->assertSame(RoomStatus::AVAILABLE, $secondRoom->fresh()->status);
    }

    public function test_two_active_tenants_cannot_occupy_the_same_room(): void
    {
        $room = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $service->create($this->tenantData($room));

        $this->expectException(ValidationException::class);
        $service->create([...$this->tenantData($room), 'name' => 'Penghuni Kedua']);
    }

    public function test_first_check_in_sets_due_day_from_move_in_date(): void
    {
        $room = Room::factory()->create();

        $tenant = app(TenantLifecycleService::class)->create([
            ...$this->tenantData($room),
            'move_in_date' => '2026-09-07',
            'due_day' => 5,
        ]);

        $this->assertSame(7, $tenant->due_day);
    }

    public function test_profile_update_cannot_change_lifecycle_fields(): void
    {
        $room = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($room), 'move_in_date' => '2026-09-05']);

        $service->update($tenant, [...$this->tenantData($room), 'move_in_date' => '2026-09-23', 'status' => TenantStatus::INACTIVE]);

        $this->assertSame('2026-09-05', $tenant->fresh()->move_in_date->toDateString());
        $this->assertSame(TenantStatus::ACTIVE, $tenant->fresh()->status);
    }

    public function test_room_transfer_preserves_existing_invoice_room_and_records_history(): void
    {
        $firstRoom = Room::factory()->create();
        $secondRoom = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($firstRoom), 'move_in_date' => now()->subDay()->toDateString()]);
        $invoice = Invoice::factory()->for($tenant)->for($firstRoom)->create();

        $service->transferRoom($tenant, $secondRoom, now()->toDateString());

        $this->assertSame($firstRoom->id, $invoice->fresh()->room_id);
        $this->assertSame(2, TenantRoomHistory::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame($secondRoom->id, TenantRoomHistory::query()->where('tenant_id', $tenant->id)->whereNull('ends_at')->value('room_id'));
    }

    public function test_check_out_deactivates_tenant_closes_history_and_releases_room(): void
    {
        $room = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($room), 'move_in_date' => '2026-08-01']);

        $service->checkOut($tenant, '2026-09-11');

        $this->assertSame(TenantStatus::INACTIVE, $tenant->fresh()->status);
        $this->assertSame('2026-09-11', $tenant->fresh()->move_out_date->toDateString());
        $this->assertSame(RoomStatus::AVAILABLE, $room->fresh()->status);
        $this->assertSame('2026-09-11', TenantRoomHistory::query()->where('tenant_id', $tenant->id)->value('ends_at')->toDateString());
    }

    public function test_checked_out_tenant_can_check_in_again_to_the_same_room_with_new_history(): void
    {
        $room = Room::factory()->create(['monthly_price' => 1250000]);
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($room), 'move_in_date' => '2026-08-01']);
        $oldInvoice = Invoice::factory()->for($tenant)->for($room)->create();
        $service->checkOut($tenant, '2026-08-31');

        $service->checkInAgain($tenant->fresh(), $room->fresh(), '2026-09-11');

        $tenant->refresh();
        $histories = TenantRoomHistory::query()->where('tenant_id', $tenant->id)->orderBy('starts_at')->get();
        $this->assertSame(TenantStatus::ACTIVE, $tenant->status);
        $this->assertNull($tenant->move_out_date);
        $this->assertSame('2026-09-11', $tenant->move_in_date->toDateString());
        $this->assertSame('1250000.00', $tenant->monthly_price);
        $this->assertSame(11, $tenant->due_day);
        $this->assertSame(RoomStatus::OCCUPIED, $room->fresh()->status);
        $this->assertCount(2, $histories);
        $this->assertSame('2026-08-31', $histories->first()->ends_at->toDateString());
        $this->assertNull($histories->last()->ends_at);
        $this->assertSame($room->id, $oldInvoice->fresh()->room_id);
    }

    public function test_check_in_again_rejects_an_occupied_room(): void
    {
        $occupiedRoom = Room::factory()->create();
        $availableRoom = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $activeTenant = $service->create($this->tenantData($occupiedRoom));
        $inactiveTenant = $service->create([...$this->tenantData($availableRoom), 'phone' => '081234567891']);
        $service->checkOut($inactiveTenant);

        $this->expectException(ValidationException::class);

        $service->checkInAgain($inactiveTenant->fresh(), $occupiedRoom->fresh(), '2026-09-11');
    }

    public function test_future_first_check_in_is_scheduled_and_reserves_room(): void
    {
        $room = Room::factory()->create();
        $moveInDate = now()->addDay()->toDateString();

        $tenant = app(TenantLifecycleService::class)->create([...$this->tenantData($room), 'move_in_date' => $moveInDate]);

        $this->assertSame(TenantStatus::SCHEDULED, $tenant->status);
        $this->assertSame(RoomStatus::RESERVED, $room->fresh()->status);
        $this->assertSame($moveInDate, $tenant->move_in_date->toDateString());
        $this->assertFalse($tenant->canAccessPortal());
    }

    public function test_future_check_in_again_activates_on_scheduled_date(): void
    {
        $room = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($room), 'move_in_date' => now()->subMonth()->toDateString()]);
        $service->checkOut($tenant, now()->subDay()->toDateString());
        $scheduledDate = now()->addDay()->toDateString();

        $service->checkInAgain($tenant->fresh(), $room->fresh(), $scheduledDate);

        $this->assertSame(TenantStatus::SCHEDULED, $tenant->fresh()->status);
        $this->assertSame(RoomStatus::RESERVED, $room->fresh()->status);
        $this->assertSame(1, $service->activateScheduledCheckIns($scheduledDate));
        $this->assertSame(TenantStatus::ACTIVE, $tenant->fresh()->status);
        $this->assertSame(RoomStatus::OCCUPIED, $room->fresh()->status);
    }

    public function test_scheduled_check_in_can_be_cancelled_without_losing_old_history(): void
    {
        $oldRoom = Room::factory()->create();
        $reservedRoom = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($oldRoom), 'move_in_date' => now()->subMonth()->toDateString()]);
        $service->checkOut($tenant, now()->subDay()->toDateString());
        $service->checkInAgain($tenant->fresh(), $reservedRoom, now()->addDay()->toDateString());

        $service->cancelScheduledCheckIn($tenant->fresh());

        $tenant->refresh();
        $this->assertSame(TenantStatus::INACTIVE, $tenant->status);
        $this->assertSame($oldRoom->id, $tenant->room_id);
        $this->assertSame(RoomStatus::AVAILABLE, $reservedRoom->fresh()->status);
        $this->assertSame(1, TenantRoomHistory::query()->whereBelongsTo($tenant)->count());
    }

    public function test_scheduled_check_in_can_be_activated_early_without_overlapping_history(): void
    {
        $this->travelTo('2026-09-11 10:00:00');
        $oldRoom = Room::factory()->create();
        $reservedRoom = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($oldRoom), 'move_in_date' => '2026-08-01']);
        $service->checkOut($tenant, '2026-09-11');
        $service->checkInAgain($tenant->fresh(), $reservedRoom, '2026-10-01');

        $service->activateScheduledCheckInNow($tenant->fresh());

        $tenant->refresh();
        $histories = TenantRoomHistory::query()->whereBelongsTo($tenant)->orderBy('starts_at')->get();
        $this->assertSame(TenantStatus::ACTIVE, $tenant->status);
        $this->assertSame('2026-09-11', $tenant->move_in_date->toDateString());
        $this->assertSame(11, $tenant->due_day);
        $this->assertSame(RoomStatus::OCCUPIED, $reservedRoom->fresh()->status);
        $this->assertSame('2026-09-10', $histories->first()->ends_at->toDateString());
        $this->assertSame('2026-09-11', $histories->last()->starts_at->toDateString());
    }

    public function test_check_out_cancels_only_future_invoices_without_payments(): void
    {
        $room = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($room), 'move_in_date' => '2026-08-01']);
        $currentInvoice = Invoice::factory()->for($tenant)->create(['room_id' => $room->id, 'period_month' => 9, 'period_year' => 2026]);
        $futureInvoice = Invoice::factory()->for($tenant)->create(['room_id' => $room->id, 'period_month' => 10, 'period_year' => 2026]);

        $service->checkOut($tenant, '2026-09-11');

        $this->assertSame(InvoiceStatus::UNPAID, $currentInvoice->fresh()->status);
        $this->assertSame(InvoiceStatus::CANCELLED, $futureInvoice->fresh()->status);
    }

    public function test_room_transfer_on_first_day_corrects_assignment_without_empty_history(): void
    {
        $firstRoom = Room::factory()->create();
        $secondRoom = Room::factory()->create();
        $tenant = app(TenantLifecycleService::class)->create([...$this->tenantData($firstRoom), 'move_in_date' => now()->toDateString()]);

        app(TenantLifecycleService::class)->transferRoom($tenant, $secondRoom, now()->toDateString());

        $tenant->refresh();
        $this->assertSame($secondRoom->id, $tenant->room_id);
        $this->assertSame(RoomStatus::AVAILABLE, $firstRoom->fresh()->status);
        $this->assertSame(RoomStatus::OCCUPIED, $secondRoom->fresh()->status);
        $this->assertSame(1, TenantRoomHistory::query()->whereBelongsTo($tenant)->count());
        $this->assertSame($secondRoom->id, TenantRoomHistory::query()->whereBelongsTo($tenant)->value('room_id'));
    }

    public function test_room_assignment_correction_is_rejected_after_invoice_exists(): void
    {
        $firstRoom = Room::factory()->create();
        $secondRoom = Room::factory()->create();
        $tenant = app(TenantLifecycleService::class)->create([...$this->tenantData($firstRoom), 'move_in_date' => now()->toDateString()]);
        $history = TenantRoomHistory::query()->whereBelongsTo($tenant)->firstOrFail();
        Invoice::factory()->for($tenant)->for($firstRoom)->create(['tenant_room_history_id' => $history->id]);

        $this->expectException(ValidationException::class);

        app(TenantLifecycleService::class)->transferRoom($tenant, $secondRoom, now()->toDateString());
    }

    public function test_future_room_transfer_reserves_room_and_activates_on_effective_date(): void
    {
        $this->travelTo('2026-09-11 10:00:00');
        $firstRoom = Room::factory()->create();
        $secondRoom = Room::factory()->create(['monthly_price' => 1750000]);
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($firstRoom), 'move_in_date' => '2026-08-01']);

        $service->transferRoom($tenant, $secondRoom, '2026-10-01');

        $tenant->refresh();
        $this->assertSame($firstRoom->id, $tenant->room_id);
        $this->assertSame($secondRoom->id, $tenant->scheduled_room_id);
        $this->assertSame('2026-10-01', $tenant->scheduled_transfer_date->toDateString());
        $this->assertSame(RoomStatus::OCCUPIED, $firstRoom->fresh()->status);
        $this->assertSame(RoomStatus::RESERVED, $secondRoom->fresh()->status);
        $this->assertSame(1, TenantRoomHistory::query()->whereBelongsTo($tenant)->count());

        $this->assertSame(1, $service->activateScheduledRoomTransfers('2026-10-01'));

        $tenant->refresh();
        $histories = TenantRoomHistory::query()->whereBelongsTo($tenant)->orderBy('starts_at')->get();
        $this->assertSame($secondRoom->id, $tenant->room_id);
        $this->assertNull($tenant->scheduled_room_id);
        $this->assertNull($tenant->scheduled_transfer_date);
        $this->assertSame('1750000.00', $tenant->monthly_price);
        $this->assertSame(RoomStatus::AVAILABLE, $firstRoom->fresh()->status);
        $this->assertSame(RoomStatus::OCCUPIED, $secondRoom->fresh()->status);
        $this->assertSame('2026-09-30', $histories->first()->ends_at->toDateString());
        $this->assertSame('2026-10-01', $histories->last()->starts_at->toDateString());
    }

    public function test_scheduled_room_transfer_can_be_cancelled_and_releases_reservation(): void
    {
        $firstRoom = Room::factory()->create();
        $secondRoom = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($firstRoom), 'move_in_date' => now()->subMonth()->toDateString()]);
        $service->transferRoom($tenant, $secondRoom, now()->addDay()->toDateString());

        $service->cancelScheduledRoomTransfer($tenant->fresh());

        $tenant->refresh();
        $this->assertSame($firstRoom->id, $tenant->room_id);
        $this->assertNull($tenant->scheduled_room_id);
        $this->assertNull($tenant->scheduled_transfer_date);
        $this->assertSame(RoomStatus::AVAILABLE, $secondRoom->fresh()->status);
    }

    public function test_check_out_cancels_scheduled_room_transfer_and_releases_both_rooms(): void
    {
        $firstRoom = Room::factory()->create();
        $secondRoom = Room::factory()->create();
        $service = app(TenantLifecycleService::class);
        $tenant = $service->create([...$this->tenantData($firstRoom), 'move_in_date' => now()->subMonth()->toDateString()]);
        $service->transferRoom($tenant, $secondRoom, now()->addDay()->toDateString());

        $service->checkOut($tenant->fresh());

        $tenant->refresh();
        $this->assertNull($tenant->scheduled_room_id);
        $this->assertNull($tenant->scheduled_transfer_date);
        $this->assertSame(RoomStatus::AVAILABLE, $firstRoom->fresh()->status);
        $this->assertSame(RoomStatus::AVAILABLE, $secondRoom->fresh()->status);
    }

    /** @return array<string, mixed> */
    private function tenantData(Room $room): array
    {
        return ['room_id' => $room->id, 'name' => 'Budi', 'phone' => '081234567890', 'move_in_date' => now()->toDateString(), 'monthly_price' => 1000000, 'due_day' => 5, 'status' => TenantStatus::ACTIVE->value];
    }
}
