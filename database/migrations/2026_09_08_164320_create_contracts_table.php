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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('contract_number')->unique();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->unsignedBigInteger('monthly_price');
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->string('deposit_status')->default('unpaid');
            $table->string('status')->default('active');
            $table->string('document')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
