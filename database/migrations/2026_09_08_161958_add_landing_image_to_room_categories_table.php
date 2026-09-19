<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('room_categories', function (Blueprint $table) {
            $table->string('landing_image')->nullable()->after('description');
        });

        DB::table('room_categories')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $categoryId): void {
                $landingImage = DB::table('rooms')
                    ->where('room_category_id', $categoryId)
                    ->whereNotNull('landing_image')
                    ->where('landing_image', '!=', '')
                    ->orderBy('id')
                    ->value('landing_image');

                if ($landingImage) {
                    DB::table('room_categories')
                        ->where('id', $categoryId)
                        ->update(['landing_image' => $landingImage]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_categories', function (Blueprint $table) {
            $table->dropColumn('landing_image');
        });
    }
};
