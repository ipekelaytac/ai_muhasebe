<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->decimal('base_net_salary', 12, 2);
            $table->decimal('meal_allowance', 12, 2)->default(0);

            $table->decimal('bonus_total', 12, 2)->default(0);
            $table->decimal('deduction_total', 12, 2)->default(0);
            $table->decimal('advances_deducted_total', 12, 2)->default(0);

            $table->decimal('net_payable', 12, 2);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id'], 'uniq_payroll_item');
            $table->index(['employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_items');
    }
}
