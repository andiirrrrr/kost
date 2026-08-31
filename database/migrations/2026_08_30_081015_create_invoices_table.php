<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict');
            $table->foreignId('room_id')->constrained()->onDelete('restrict');
            $table->string('invoice_number')->unique();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->bigInteger('base_amount')->default(0);
            $table->bigInteger('electricity_amount')->default(0);
            $table->bigInteger('water_amount')->default(0);
            $table->bigInteger('other_amount')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('total_amount');
            $table->date('due_date');
            $table->enum('status', ['unpaid', 'pending', 'paid', 'overdue', 'cancelled'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate invoices per tenant per month/year
            $table->unique(['tenant_id', 'period_month', 'period_year']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
