<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeDebtPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_debt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->nullable()->constrained()->nullOnDelete();
            
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            $table->index('employee_debt_id');
            $table->index('payroll_item_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_debt_payments');
    }
}
