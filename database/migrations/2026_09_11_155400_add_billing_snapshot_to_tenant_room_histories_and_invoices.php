<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_room_histories', function (Blueprint $table): void {
            $table->unsignedTinyInteger('due_day')->default(5)->after('monthly_price');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('tenant_room_history_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
        });

        DB::table('tenant_room_histories')->orderBy('id')->chunkById(200, function ($histories): void {
            foreach ($histories as $history) {
                DB::table('tenant_room_histories')->where('id', $history->id)->update(['due_day' => Carbon::parse($history->starts_at)->day]);
            }
        });

        DB::table('tenants')->orderBy('id')->chunkById(200, function ($tenants): void {
            foreach ($tenants as $tenant) {
                $hasHistory = DB::table('tenant_room_histories')->where('tenant_id', $tenant->id)->exists();

                if (! $hasHistory) {
                    DB::table('tenant_room_histories')->insert([
                        'tenant_id' => $tenant->id,
                        'room_id' => $tenant->room_id,
                        'starts_at' => $tenant->move_in_date,
                        'ends_at' => $tenant->move_out_date,
                        'monthly_price' => (int) $tenant->monthly_price,
                        'due_day' => Carbon::parse($tenant->move_in_date)->day,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        DB::table('tenants')
            ->where('status', 'active')
            ->whereDate('move_in_date', '>', today())
            ->orderBy('id')
            ->chunkById(200, function ($tenants): void {
                foreach ($tenants as $tenant) {
                    DB::table('tenant_room_histories')
                        ->where('tenant_id', $tenant->id)
                        ->whereDate('starts_at', '>', today())
                        ->delete();
                    DB::table('tenants')->where('id', $tenant->id)->update([
                        'status' => 'inactive',
                        'move_out_date' => DB::table('tenant_room_histories')->where('tenant_id', $tenant->id)->max('ends_at'),
                    ]);
                }
            });

        DB::table('rooms')->where('status', 'occupied')->orderBy('id')->chunkById(200, function ($rooms): void {
            foreach ($rooms as $room) {
                $isOccupied = DB::table('tenants')
                    ->where('room_id', $room->id)
                    ->where('status', 'active')
                    ->whereNull('move_out_date')
                    ->whereDate('move_in_date', '<=', today())
                    ->exists();

                if (! $isOccupied) {
                    DB::table('rooms')->where('id', $room->id)->update(['status' => 'available']);
                }
            }
        });

        DB::table('invoices')->orderBy('id')->chunkById(200, function ($invoices): void {
            foreach ($invoices as $invoice) {
                $periodStart = Carbon::create($invoice->period_year, $invoice->period_month, 1)->startOfMonth()->toDateString();
                $periodEnd = Carbon::create($invoice->period_year, $invoice->period_month, 1)->endOfMonth()->toDateString();
                $historyId = DB::table('tenant_room_histories')
                    ->where('tenant_id', $invoice->tenant_id)
                    ->where('room_id', $invoice->room_id)
                    ->whereDate('starts_at', '<=', $periodEnd)
                    ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $periodStart))
                    ->orderByDesc('starts_at')
                    ->value('id');

                if ($historyId) {
                    DB::table('invoices')->where('id', $invoice->id)->update(['tenant_room_history_id' => $historyId]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_room_history_id');
        });

        Schema::table('tenant_room_histories', function (Blueprint $table): void {
            $table->dropColumn('due_day');
        });
    }
};
