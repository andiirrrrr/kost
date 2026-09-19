<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tenants')
            ->select(['id', 'move_in_date'])
            ->orderBy('id')
            ->chunkById(200, function ($tenants): void {
                foreach ($tenants as $tenant) {
                    DB::table('tenants')->where('id', $tenant->id)->update([
                        'due_day' => CarbonImmutable::parse($tenant->move_in_date)->day,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nilai due_day sebelumnya tidak dapat dipulihkan dengan aman.
    }
};
