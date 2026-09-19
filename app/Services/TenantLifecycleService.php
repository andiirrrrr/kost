<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\RoomStatus;
use App\Enums\TenantStatus;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\TenantRoomHistory;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantLifecycleService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Tenant
    {
        $moveInDate = CarbonImmutable::parse($data['move_in_date']);

        $data['move_in_date'] = $moveInDate->toDateString();
        $data['move_out_date'] = null;
        $data['due_day'] = $moveInDate->day;
        $data['status'] = $moveInDate->isFuture() ? TenantStatus::SCHEDULED : TenantStatus::ACTIVE;

        return DB::transaction(function () use ($data): Tenant {
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);
            $this->ensureRoomCanBeAssigned($room, null, $data);
            $tenant = Tenant::query()->create($data);
            TenantRoomHistory::query()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'starts_at' => $tenant->move_in_date, 'monthly_price' => $tenant->monthly_price, 'due_day' => $tenant->move_in_date->day, 'created_by' => auth()->id()]);
            $this->syncRoomStatus($room);
            ActivityLog::record($tenant->status === TenantStatus::SCHEDULED ? 'tenant.check_in_scheduled' : 'tenant.checked_in', $tenant, [
                'room_number' => $room->room_number,
                'move_in_date' => $tenant->move_in_date->toDateString(),
            ]);

            return $tenant;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data): Tenant {
            $lockedTenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $lockedTenant->update(collect($data)->only([
                'name', 'phone', 'email', 'identity_number', 'identity_document', 'address',
                'emergency_contact', 'notes',
            ])->all());
            ActivityLog::record('tenant.updated', $lockedTenant, ['changes' => array_keys($lockedTenant->getChanges())]);

            return $lockedTenant;
        });
    }

    public function transferRoom(Tenant $tenant, Room $room, CarbonInterface|string $effectiveDate): Tenant
    {
        $date = CarbonImmutable::parse($effectiveDate);

        return DB::transaction(function () use ($tenant, $room, $date): Tenant {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $oldRoom = Room::query()->lockForUpdate()->findOrFail($tenant->room_id);
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            $activeHistory = TenantRoomHistory::query()->whereBelongsTo($tenant)->whereNull('ends_at')->lockForUpdate()->latest('starts_at')->firstOrFail();

            if ($tenant->status !== TenantStatus::ACTIVE || $tenant->move_out_date !== null) {
                throw ValidationException::withMessages(['tenant' => 'Hanya penghuni aktif yang dapat dipindahkan.']);
            }

            if ($room->is($oldRoom)) {
                throw ValidationException::withMessages(['room_id' => 'Pilih kamar yang berbeda dari kamar saat ini.']);
            }

            if ($date->lt($activeHistory->starts_at)) {
                throw ValidationException::withMessages(['effective_date' => 'Tanggal pindah tidak boleh sebelum periode sewa aktif.']);
            }

            if ($tenant->scheduled_room_id !== null) {
                throw ValidationException::withMessages(['effective_date' => 'Penghuni sudah memiliki jadwal pindah kamar. Batalkan jadwal lama terlebih dahulu.']);
            }

            $this->ensureRoomCanBeAssigned($room, $tenant, ['status' => TenantStatus::ACTIVE->value, 'move_out_date' => null]);

            if ($date->isFuture()) {
                $tenant->update([
                    'scheduled_room_id' => $room->id,
                    'scheduled_transfer_date' => $date->toDateString(),
                ]);
                $room->update(['status' => RoomStatus::RESERVED]);
                ActivityLog::record('tenant.room_transfer_scheduled', $tenant, [
                    'from_room' => $oldRoom->room_number,
                    'to_room' => $room->room_number,
                    'effective_date' => $date->toDateString(),
                ]);

                return $tenant;
            }

            if ($date->isSameDay($activeHistory->starts_at)) {
                if (Invoice::query()->where('tenant_room_history_id', $activeHistory->id)->exists()) {
                    throw ValidationException::withMessages(['effective_date' => 'Penempatan hari pertama tidak dapat dikoreksi karena sudah memiliki tagihan. Pilih tanggal setelah hari pertama.']);
                }

                $monthlyPrice = (int) $room->monthly_price;
                $activeHistory->update(['room_id' => $room->id, 'monthly_price' => $monthlyPrice]);
                $tenant->update(['room_id' => $room->id, 'monthly_price' => $monthlyPrice]);
                $this->syncRoomStatus($oldRoom);
                $this->syncRoomStatus($room);
                ActivityLog::record('tenant.room_assignment_corrected', $tenant, [
                    'from_room' => $oldRoom->room_number,
                    'to_room' => $room->room_number,
                    'effective_date' => $date->toDateString(),
                ]);

                return $tenant;
            }

            $activeHistory->update(['ends_at' => $date->subDay()->toDateString()]);
            $monthlyPrice = (int) $room->monthly_price;
            TenantRoomHistory::query()->create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'starts_at' => $date->toDateString(),
                'monthly_price' => $monthlyPrice,
                'due_day' => $tenant->due_day,
                'created_by' => auth()->id(),
            ]);
            $tenant->update(['room_id' => $room->id, 'monthly_price' => $monthlyPrice]);
            $this->syncRoomStatus($oldRoom);
            $this->syncRoomStatus($room);
            ActivityLog::record('tenant.room_transferred', $tenant, ['from_room' => $oldRoom->room_number, 'to_room' => $room->room_number, 'effective_date' => $date->toDateString()]);

            return $tenant;
        });
    }

    public function checkOut(Tenant $tenant, CarbonInterface|string|null $moveOutDate = null): Tenant
    {
        $date = $moveOutDate ? now()->parse($moveOutDate) : now();

        return DB::transaction(function () use ($tenant, $date): Tenant {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $room = Room::query()->lockForUpdate()->findOrFail($tenant->room_id);
            $scheduledRoom = $tenant->scheduled_room_id
                ? Room::query()->lockForUpdate()->findOrFail($tenant->scheduled_room_id)
                : null;
            $activeHistory = TenantRoomHistory::query()->whereBelongsTo($tenant)->whereNull('ends_at')->lockForUpdate()->latest('starts_at')->firstOrFail();

            if ($tenant->status !== TenantStatus::ACTIVE || $tenant->move_out_date !== null) {
                throw ValidationException::withMessages(['tenant' => 'Penghuni ini sudah tidak aktif.']);
            }

            if ($date->isFuture() || $date->lt($activeHistory->starts_at)) {
                throw ValidationException::withMessages(['move_out_date' => 'Tanggal keluar harus berada dalam periode sewa aktif dan tidak boleh di masa depan.']);
            }

            $tenant->update([
                'status' => TenantStatus::INACTIVE,
                'move_out_date' => $date->toDateString(),
                'scheduled_room_id' => null,
                'scheduled_transfer_date' => null,
            ]);
            $activeHistory->update(['ends_at' => $date->toDateString()]);
            $cancelledInvoices = Invoice::query()
                ->whereBelongsTo($tenant)
                ->whereIn('status', [InvoiceStatus::UNPAID, InvoiceStatus::OVERDUE])
                ->whereDoesntHave('payments')
                ->where(function ($query) use ($date): void {
                    $query->where('period_year', '>', $date->year)
                        ->orWhere(fn ($query) => $query->where('period_year', $date->year)->where('period_month', '>', $date->month));
                })
                ->update(['status' => InvoiceStatus::CANCELLED]);
            $this->syncRoomStatus($room);

            if ($scheduledRoom) {
                $this->syncRoomStatus($scheduledRoom);
            }
            ActivityLog::record('tenant.checked_out', $tenant, ['room_number' => $room->room_number, 'move_out_date' => $date->toDateString(), 'cancelled_future_invoices' => $cancelledInvoices]);

            return $tenant;
        });
    }

    public function checkInAgain(Tenant $tenant, Room $room, CarbonInterface|string $moveInDate): Tenant
    {
        $date = now()->parse($moveInDate);

        return DB::transaction(function () use ($tenant, $room, $date): Tenant {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);

            if ($tenant->status === TenantStatus::ACTIVE && $tenant->move_out_date === null) {
                throw ValidationException::withMessages(['tenant' => 'Penghuni ini masih aktif dan tidak perlu check-in kembali.']);
            }

            $lastMoveOutDate = TenantRoomHistory::query()->whereBelongsTo($tenant)->whereNotNull('ends_at')->max('ends_at');

            if ($lastMoveOutDate && $date->lte(CarbonImmutable::parse($lastMoveOutDate))) {
                throw ValidationException::withMessages(['move_in_date' => 'Tanggal masuk baru harus setelah tanggal keluar terakhir.']);
            }

            $data = [
                ...$tenant->only(['name', 'phone']),
                'room_id' => $room->id,
                'status' => $date->isFuture() ? TenantStatus::SCHEDULED->value : TenantStatus::ACTIVE->value,
                'move_out_date' => null,
            ];
            $this->ensureRoomCanBeAssigned($room, $tenant, $data);

            if (TenantRoomHistory::query()->whereBelongsTo($tenant)->whereNull('ends_at')->exists()) {
                throw ValidationException::withMessages(['tenant' => 'Masih ada periode sewa aktif. Selesaikan histori tersebut sebelum check-in kembali.']);
            }

            $oldRoomId = $tenant->room_id;
            $tenant->update([
                'room_id' => $room->id,
                'move_in_date' => $date->toDateString(),
                'move_out_date' => null,
                'monthly_price' => (int) $room->monthly_price,
                'due_day' => $date->day,
                'status' => $date->isFuture() ? TenantStatus::SCHEDULED : TenantStatus::ACTIVE,
            ]);

            TenantRoomHistory::query()->create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'starts_at' => $date->toDateString(),
                'monthly_price' => (int) $room->monthly_price,
                'due_day' => $date->day,
                'created_by' => auth()->id(),
            ]);

            $this->syncRoomStatus($room);

            if ($oldRoomId !== $room->id) {
                $this->syncRoomStatus(Room::query()->lockForUpdate()->findOrFail($oldRoomId));
            }

            ActivityLog::record($date->isFuture() ? 'tenant.check_in_scheduled' : 'tenant.checked_in_again', $tenant, [
                'room_number' => $room->room_number,
                'move_in_date' => $date->toDateString(),
            ]);

            return $tenant;
        });
    }

    public function activateScheduledRoomTransfers(CarbonInterface|string|null $date = null): int
    {
        $activationDate = CarbonImmutable::parse($date ?? today());
        $activated = 0;

        Tenant::query()
            ->where('status', TenantStatus::ACTIVE)
            ->whereNotNull('scheduled_room_id')
            ->whereDate('scheduled_transfer_date', '<=', $activationDate)
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $tenantId) use (&$activated, $activationDate): void {
                DB::transaction(function () use ($tenantId, &$activated, $activationDate): void {
                    $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenantId);

                    if ($tenant->status !== TenantStatus::ACTIVE
                        || $tenant->scheduled_room_id === null
                        || $tenant->scheduled_transfer_date?->gt($activationDate)) {
                        return;
                    }

                    $oldRoom = Room::query()->lockForUpdate()->findOrFail($tenant->room_id);
                    $newRoom = Room::query()->lockForUpdate()->findOrFail($tenant->scheduled_room_id);
                    $activeHistory = TenantRoomHistory::query()->whereBelongsTo($tenant)->whereNull('ends_at')->lockForUpdate()->latest('starts_at')->firstOrFail();
                    $assignedToAnotherTenant = Tenant::query()
                        ->whereKeyNot($tenant->id)
                        ->whereNull('move_out_date')
                        ->where(function ($query) use ($newRoom): void {
                            $query->where('room_id', $newRoom->id)->where('status', TenantStatus::ACTIVE)
                                ->orWhere('scheduled_room_id', $newRoom->id);
                        })
                        ->exists();

                    if ($assignedToAnotherTenant || $newRoom->status === RoomStatus::MAINTENANCE) {
                        ActivityLog::record('tenant.scheduled_room_transfer_failed', $tenant, [
                            'from_room' => $oldRoom->room_number,
                            'to_room' => $newRoom->room_number,
                            'effective_date' => $tenant->scheduled_transfer_date->toDateString(),
                        ]);

                        return;
                    }

                    $effectiveDate = CarbonImmutable::parse($tenant->scheduled_transfer_date);
                    $activeHistory->update(['ends_at' => $effectiveDate->subDay()->toDateString()]);
                    $monthlyPrice = (int) $newRoom->monthly_price;
                    TenantRoomHistory::query()->create([
                        'tenant_id' => $tenant->id,
                        'room_id' => $newRoom->id,
                        'starts_at' => $effectiveDate->toDateString(),
                        'monthly_price' => $monthlyPrice,
                        'due_day' => $tenant->due_day,
                        'created_by' => auth()->id(),
                    ]);
                    $tenant->update([
                        'room_id' => $newRoom->id,
                        'scheduled_room_id' => null,
                        'scheduled_transfer_date' => null,
                        'monthly_price' => $monthlyPrice,
                    ]);
                    $this->syncRoomStatus($oldRoom);
                    $this->syncRoomStatus($newRoom);
                    ActivityLog::record('tenant.scheduled_room_transfer_activated', $tenant, [
                        'from_room' => $oldRoom->room_number,
                        'to_room' => $newRoom->room_number,
                        'effective_date' => $effectiveDate->toDateString(),
                    ]);
                    $activated++;
                });
            });

        return $activated;
    }

    public function cancelScheduledRoomTransfer(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant): Tenant {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);

            if ($tenant->scheduled_room_id === null || $tenant->scheduled_transfer_date === null) {
                throw ValidationException::withMessages(['tenant' => 'Penghuni ini tidak memiliki jadwal pindah kamar.']);
            }

            $scheduledRoom = Room::query()->lockForUpdate()->findOrFail($tenant->scheduled_room_id);
            $properties = [
                'from_room' => $tenant->room->room_number,
                'to_room' => $scheduledRoom->room_number,
                'effective_date' => $tenant->scheduled_transfer_date->toDateString(),
            ];
            $tenant->update(['scheduled_room_id' => null, 'scheduled_transfer_date' => null]);
            $this->syncRoomStatus($scheduledRoom);
            ActivityLog::record('tenant.room_transfer_schedule_cancelled', $tenant, $properties);

            return $tenant;
        });
    }

    public function activateScheduledCheckIns(CarbonInterface|string|null $date = null): int
    {
        $activationDate = CarbonImmutable::parse($date ?? today());
        $activated = 0;

        Tenant::query()
            ->where('status', TenantStatus::SCHEDULED)
            ->whereDate('move_in_date', '<=', $activationDate)
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $tenantId) use (&$activated, $activationDate): void {
                DB::transaction(function () use ($tenantId, &$activated, $activationDate): void {
                    $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenantId);

                    if ($tenant->status !== TenantStatus::SCHEDULED || $tenant->move_in_date->gt($activationDate)) {
                        return;
                    }

                    $room = Room::query()->lockForUpdate()->findOrFail($tenant->room_id);
                    $occupiedByAnotherTenant = Tenant::query()
                        ->whereBelongsTo($room)
                        ->whereKeyNot($tenant->id)
                        ->where('status', TenantStatus::ACTIVE)
                        ->whereNull('move_out_date')
                        ->exists();

                    if ($occupiedByAnotherTenant || $room->status === RoomStatus::MAINTENANCE) {
                        ActivityLog::record('tenant.scheduled_check_in_failed', $tenant, [
                            'room_number' => $room->room_number,
                            'move_in_date' => $tenant->move_in_date->toDateString(),
                        ]);

                        return;
                    }

                    $tenant->update(['status' => TenantStatus::ACTIVE]);
                    $this->syncRoomStatus($room);
                    ActivityLog::record('tenant.scheduled_check_in_activated', $tenant, [
                        'room_number' => $room->room_number,
                        'move_in_date' => $tenant->move_in_date->toDateString(),
                    ]);
                    $activated++;
                });
            });

        return $activated;
    }

    public function activateScheduledCheckInNow(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant): Tenant {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);

            if ($tenant->status !== TenantStatus::SCHEDULED) {
                throw ValidationException::withMessages(['tenant' => 'Penghuni ini tidak memiliki jadwal check-in aktif.']);
            }

            $room = Room::query()->lockForUpdate()->findOrFail($tenant->room_id);
            $scheduledHistory = TenantRoomHistory::query()
                ->whereBelongsTo($tenant)
                ->whereNull('ends_at')
                ->lockForUpdate()
                ->latest('starts_at')
                ->firstOrFail();
            $assignedToAnotherTenant = Tenant::query()
                ->whereBelongsTo($room)
                ->whereKeyNot($tenant->id)
                ->whereIn('status', [TenantStatus::ACTIVE, TenantStatus::SCHEDULED])
                ->whereNull('move_out_date')
                ->exists();

            if ($assignedToAnotherTenant || $room->status === RoomStatus::MAINTENANCE) {
                throw ValidationException::withMessages(['room_id' => 'Kamar tidak dapat dipakai untuk check-in sekarang.']);
            }

            $today = CarbonImmutable::today();
            $previousHistory = TenantRoomHistory::query()
                ->whereBelongsTo($tenant)
                ->whereKeyNot($scheduledHistory->id)
                ->latest('ends_at')
                ->lockForUpdate()
                ->first();

            if ($previousHistory?->ends_at?->gte($today)) {
                $previousHistory->update(['ends_at' => $today->subDay()->toDateString()]);
            }

            $scheduledHistory->update([
                'starts_at' => $today->toDateString(),
                'monthly_price' => (int) $room->monthly_price,
                'due_day' => $today->day,
            ]);
            $tenant->update([
                'move_in_date' => $today->toDateString(),
                'move_out_date' => null,
                'monthly_price' => (int) $room->monthly_price,
                'due_day' => $today->day,
                'status' => TenantStatus::ACTIVE,
            ]);
            $this->syncRoomStatus($room);
            ActivityLog::record('tenant.scheduled_check_in_activated_early', $tenant, [
                'room_number' => $room->room_number,
                'move_in_date' => $today->toDateString(),
            ]);

            return $tenant;
        });
    }

    public function cancelScheduledCheckIn(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant): Tenant {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);

            if ($tenant->status !== TenantStatus::SCHEDULED) {
                throw ValidationException::withMessages(['tenant' => 'Penghuni ini tidak memiliki jadwal check-in aktif.']);
            }

            $reservedRoom = Room::query()->lockForUpdate()->findOrFail($tenant->room_id);
            $scheduledHistory = TenantRoomHistory::query()
                ->whereBelongsTo($tenant)
                ->whereNull('ends_at')
                ->whereDate('starts_at', '>', today())
                ->lockForUpdate()
                ->latest('starts_at')
                ->firstOrFail();
            $scheduledDate = $scheduledHistory->starts_at->toDateString();
            $scheduledHistory->delete();
            $lastHistory = TenantRoomHistory::query()->whereBelongsTo($tenant)->latest('ends_at')->first();

            $tenant->update([
                'room_id' => $lastHistory?->room_id ?? $tenant->room_id,
                'move_in_date' => $lastHistory?->starts_at ?? $tenant->move_in_date,
                'move_out_date' => $lastHistory?->ends_at,
                'monthly_price' => $lastHistory?->monthly_price ?? $tenant->monthly_price,
                'due_day' => $lastHistory?->due_day ?? $tenant->due_day,
                'status' => TenantStatus::INACTIVE,
            ]);
            $this->syncRoomStatus($reservedRoom);
            ActivityLog::record('tenant.scheduled_check_in_cancelled', $tenant, [
                'room_number' => $reservedRoom->room_number,
                'move_in_date' => $scheduledDate,
            ]);

            return $tenant;
        });
    }

    /** @param array<string, mixed> $data */
    private function ensureRoomCanBeAssigned(Room $room, ?Tenant $tenant, array $data): void
    {
        if (! $this->isActiveOrScheduled($data)) {
            return;
        }

        $assignedToAnotherTenant = Tenant::query()
            ->where('room_id', $room->id)
            ->whereIn('status', [TenantStatus::ACTIVE, TenantStatus::SCHEDULED])
            ->whereNull('move_out_date')
            ->when($tenant, fn ($query) => $query->whereKeyNot($tenant->id))
            ->exists();
        $reservedByAnotherTenant = Tenant::query()
            ->where('scheduled_room_id', $room->id)
            ->when($tenant, fn ($query) => $query->whereKeyNot($tenant->id))
            ->exists();

        if ($assignedToAnotherTenant || $reservedByAnotherTenant || $room->status !== RoomStatus::AVAILABLE) {
            throw ValidationException::withMessages(['room_id' => 'Kamar tidak tersedia atau sudah dipesan penghuni lain.']);
        }
    }

    private function syncRoomStatus(Room $room): void
    {
        if ($room->status === RoomStatus::MAINTENANCE) {
            return;
        }

        $isOccupied = Tenant::query()->where('room_id', $room->id)->where('status', TenantStatus::ACTIVE)->whereNull('move_out_date')->exists();
        $isReserved = Tenant::query()
            ->whereNull('move_out_date')
            ->where(function ($query) use ($room): void {
                $query->where(fn ($query) => $query->where('room_id', $room->id)->where('status', TenantStatus::SCHEDULED))
                    ->orWhere('scheduled_room_id', $room->id);
            })
            ->exists();
        $room->update(['status' => match (true) {
            $isOccupied => RoomStatus::OCCUPIED,
            $isReserved => RoomStatus::RESERVED,
            default => RoomStatus::AVAILABLE,
        }]);
    }

    /** @param array<string, mixed> $data */
    private function isActiveOrScheduled(array $data): bool
    {
        $status = $data['status'] instanceof TenantStatus ? $data['status']->value : $data['status'];

        return in_array($status, [TenantStatus::ACTIVE->value, TenantStatus::SCHEDULED->value], true)
            && blank($data['move_out_date'] ?? null);
    }
}
