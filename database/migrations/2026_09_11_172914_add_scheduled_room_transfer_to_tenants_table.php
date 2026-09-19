<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('scheduled_room_id')->nullable()->after('room_id')->constrained('rooms')->restrictOnDelete();
            $table->date('scheduled_transfer_date')->nullable()->after('move_out_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scheduled_room_id');
            $table->dropColumn('scheduled_transfer_date');
        });
    }
};
