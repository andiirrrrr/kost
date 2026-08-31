<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('restrict');
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict');
            $table->string('payment_number')->unique();
            $table->bigInteger('amount');
            $table->enum('payment_method', ['bank_transfer', 'cash', 'qris', 'other']);
            $table->timestamp('paid_at')->nullable();
            $table->string('proof')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('payment_number');
            $table->index('status');
            $table->index('invoice_id');
            $table->index('tenant_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
