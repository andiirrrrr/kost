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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('icon', 50)->default('payments');
            $table->text('instructions')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $bankAccountNumber = DB::table('settings')->where('key', 'bank_account_number')->value('value');
        $bankAccountHolder = DB::table('settings')->where('key', 'bank_account_holder')->value('value');

        DB::table('payment_methods')->insert([
            ['name' => 'Transfer Bank', 'code' => 'bank_transfer', 'icon' => 'account_balance', 'instructions' => 'Transfer sesuai nominal tagihan, lalu unggah bukti pembayaran.', 'account_number' => $bankAccountNumber, 'account_holder' => $bankAccountHolder, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tunai', 'code' => 'cash', 'icon' => 'payments', 'instructions' => null, 'account_number' => null, 'account_holder' => null, 'is_active' => false, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'QRIS', 'code' => 'qris', 'icon' => 'qr_code_2', 'instructions' => null, 'account_number' => null, 'account_holder' => null, 'is_active' => false, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lainnya', 'code' => 'other', 'icon' => 'more_horiz', 'instructions' => null, 'account_number' => null, 'account_holder' => null, 'is_active' => false, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
