<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->decimal('monthly_net_salary', 12, 2);

            $table->unsignedTinyInteger('pay_day_1')->default(5);
            $table->decimal('pay_amount_1', 12, 2);

            $table->unsignedTinyInteger('pay_day_2')->default(20);
            $table->decimal('pay_amount_2', 12, 2);

            $table->decimal('meal_allowance', 12, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_contracts');
    }
}
