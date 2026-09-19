<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tenant = DB::table('tenants')->where('name', 'Budi Santoso')->first();

        if (! $tenant) {
            return;
        }

        $scheduledEvent = DB::table('activity_logs')
            ->where('subject_type', 'App\\Models\\Tenant')
            ->where('subject_id', $tenant->id)
            ->where('action', 'tenant.checked_in_again')
            ->latest('id')
            ->first();
        $scheduledProperties = $this->properties($scheduledEvent?->properties ?? null);
        $scheduledDate = isset($scheduledProperties['move_in_date']) ? Carbon::parse($scheduledProperties['move_in_date']) : null;

        if (! $scheduledEvent || ! $scheduledDate?->isFuture()) {
            return;
        }

        $checkoutEvent = DB::table('activity_logs')
            ->where('subject_type', 'App\\Models\\Tenant')
            ->where('subject_id', $tenant->id)
            ->where('action', 'tenant.checked_out')
            ->where('id', '<', $scheduledEvent->id)
            ->latest('id')
            ->first();
        $checkoutProperties = $this->properties($checkoutEvent?->properties ?? null);
        $checkoutDate = isset($checkoutProperties['move_out_date']) ? Carbon::parse($checkoutProperties['move_out_date']) : null;
        $scheduledRoom = DB::table('rooms')->where('room_number', $scheduledProperties['room_number'] ?? null)->first();
        $previousRoom = DB::table('rooms')->where('room_number', $checkoutProperties['room_number'] ?? null)->first();

        if (! $checkoutDate || ! $scheduledRoom || ! $previousRoom) {
            return;
        }

        $roomIsOccupied = DB::table('tenants')
            ->where('room_id', $scheduledRoom->id)
            ->where('id', '!=', $tenant->id)
            ->where('status', 'active')
            ->whereNull('move_out_date')
            ->exists();

        if ($roomIsOccupied) {
            return;
        }

        DB::transaction(function () use ($tenant, $scheduledRoom, $previousRoom, $checkoutDate, $scheduledDate): void {
            DB::table('tenant_room_histories')
                ->where('tenant_id', $tenant->id)
                ->where('room_id', $previousRoom->id)
                ->whereDate('starts_at', '<=', $checkoutDate)
                ->whereDate('ends_at', '>=', $checkoutDate)
                ->update(['ends_at' => $checkoutDate->toDateString(), 'updated_at' => now()]);

            DB::table('tenant_room_histories')
                ->where('tenant_id', $tenant->id)
                ->whereDate('starts_at', '>', today())
                ->delete();

            DB::table('tenant_room_histories')->insert([
                'tenant_id' => $tenant->id,
                'room_id' => $scheduledRoom->id,
                'starts_at' => $scheduledDate->toDateString(),
                'ends_at' => null,
                'monthly_price' => (int) $scheduledRoom->monthly_price,
                'due_day' => $scheduledDate->day,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('tenants')->where('id', $tenant->id)->update([
                'room_id' => $scheduledRoom->id,
                'move_in_date' => $scheduledDate->toDateString(),
                'move_out_date' => null,
                'monthly_price' => $scheduledRoom->monthly_price,
                'due_day' => $scheduledDate->day,
                'status' => 'scheduled',
                'updated_at' => now(),
            ]);
            DB::table('rooms')->where('id', $scheduledRoom->id)->update(['status' => 'reserved', 'updated_at' => now()]);

            $previousRoomIsOccupied = DB::table('tenants')
                ->where('room_id', $previousRoom->id)
                ->where('status', 'active')
                ->whereNull('move_out_date')
                ->exists();

            if (! $previousRoomIsOccupied && $previousRoom->status !== 'maintenance') {
                DB::table('rooms')->where('id', $previousRoom->id)->update(['status' => 'available', 'updated_at' => now()]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}

    /** @return array<string, mixed> */
    private function properties(?string $properties): array
    {
        if (! $properties) {
            return [];
        }

        $decoded = json_decode($properties, true);

        return is_array($decoded) ? $decoded : [];
    }
};
