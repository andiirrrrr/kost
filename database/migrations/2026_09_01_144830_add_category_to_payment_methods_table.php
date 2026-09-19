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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('category', 30)->default('bank_transfer')->after('code')->index();
        });

        DB::table('payment_methods')->where('code', 'cash')->update(['category' => 'cash']);
        DB::table('payment_methods')->whereIn('code', ['qris', 'gopay', 'ovo', 'dana', 'shopeepay'])->update(['category' => 'e_wallet']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
