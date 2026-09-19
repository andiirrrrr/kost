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
        DB::table('invoices')
            ->whereNull('tenant_room_history_id')
            ->select(['tenant_id', 'room_id'])
            ->distinct()
            ->orderBy('tenant_id')
            ->get()
            ->each(function ($group): void {
                $invoices = DB::table('invoices')
                    ->whereNull('tenant_room_history_id')
                    ->where('tenant_id', $group->tenant_id)
                    ->where('room_id', $group->room_id)
                    ->orderBy('period_year')
                    ->orderBy('period_month')
                    ->get();
                $firstInvoice = $invoices->first();
                $lastInvoice = $invoices->last();
                $startsAt = Carbon::create($firstInvoice->period_year, $firstInvoice->period_month, 1)->startOfMonth();
                $endsAt = Carbon::create($lastInvoice->period_year, $lastInvoice->period_month, 1)->endOfMonth();
                $historyId = DB::table('tenant_room_histories')->insertGetId([
                    'tenant_id' => $group->tenant_id,
                    'room_id' => $group->room_id,
                    'starts_at' => $startsAt->toDateString(),
                    'ends_at' => $endsAt->toDateString(),
                    'monthly_price' => (int) $firstInvoice->base_amount,
                    'due_day' => Carbon::parse($firstInvoice->due_date)->day,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('invoices')->whereIn('id', $invoices->pluck('id'))->update(['tenant_room_history_id' => $historyId]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
