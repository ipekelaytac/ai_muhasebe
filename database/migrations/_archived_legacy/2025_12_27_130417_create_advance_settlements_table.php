<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvanceSettlementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advance_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_installment_id')->nullable()->constrained('payroll_installments')->nullOnDelete();

            $table->decimal('settled_amount', 12, 2);
            $table->date('settled_date');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['advance_id', 'payroll_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('advance_settlements');
    }
}
