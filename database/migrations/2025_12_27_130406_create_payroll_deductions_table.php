<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_item_id')
                ->constrained('payroll_items')
                ->cascadeOnDelete();

            // (opsiyonel) Kesinti hangi taksite yazılacak? (5 mi 20 mi)
            $table->foreignId('payroll_installment_id')
                ->nullable()
                ->constrained('payroll_installments')
                ->nullOnDelete();

            $table->foreignId('deduction_type_id')
                ->constrained('payroll_deduction_types')
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('description', 255)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['payroll_item_id']);
            $table->index(['payroll_installment_id']);
            $table->index(['deduction_type_id']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_deductions');
    }
}
