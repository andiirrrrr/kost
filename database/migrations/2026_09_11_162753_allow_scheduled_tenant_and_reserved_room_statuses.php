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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->enum('status', ['active', 'scheduled', 'inactive'])->default('active')->change();
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->enum('status', ['available', 'reserved', 'occupied', 'maintenance'])->default('available')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tenants')->where('status', 'scheduled')->update(['status' => 'inactive']);
        DB::table('rooms')->where('status', 'reserved')->update(['status' => 'available']);

        Schema::table('tenants', function (Blueprint $table): void {
            $table->enum('status', ['active', 'inactive'])->default('active')->change();
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available')->change();
        });
    }
};
