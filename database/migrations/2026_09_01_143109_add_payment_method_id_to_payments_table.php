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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method', 50)->change();
            $table->foreignId('payment_method_id')->nullable()->after('payment_method')->constrained()->nullOnDelete();
        });

        DB::table('payment_methods')->get(['id', 'code'])->each(function (object $method): void {
            DB::table('payments')
                ->where('payment_method', $method->code)
                ->update(['payment_method_id' => $method->id]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
        });
    }
};
