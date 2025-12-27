<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollInstallmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained('payroll_items')->cascadeOnDelete();

            $table->unsignedTinyInteger('installment_no');
            $table->date('due_date');
            $table->decimal('planned_amount', 12, 2);
            $table->string('title', 100)->nullable();

            $table->timestamps();

            $table->unique(['payroll_item_id', 'installment_no'], 'uniq_installment_no');
            $table->index(['payroll_item_id', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_installments');
    }
}
