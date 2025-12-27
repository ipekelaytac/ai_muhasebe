<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollPaymentAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_payment_id')->constrained('payroll_payments')->cascadeOnDelete();
            $table->foreignId('payroll_installment_id')->constrained('payroll_installments')->cascadeOnDelete();

            $table->decimal('allocated_amount', 12, 2);

            $table->timestamps();

            $table->unique(['payroll_payment_id', 'payroll_installment_id'], 'uniq_payment_installment');
            $table->index(['payroll_installment_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_payment_allocations');
    }
}
