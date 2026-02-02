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
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('payroll_installment_id')->nullable()->after('document_id');
            $table->foreign('payroll_installment_id')->references('id')->on('payroll_installments')->onDelete('set null');
            $table->index('payroll_installment_id', 'idx_alloc_installment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropForeign(['payroll_installment_id']);
            $table->dropIndex('idx_alloc_installment');
            $table->dropColumn('payroll_installment_id');
        });
    }
};
