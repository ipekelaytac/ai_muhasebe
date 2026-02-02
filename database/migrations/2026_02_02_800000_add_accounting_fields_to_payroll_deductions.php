<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds optional accounting links to PayrollDeduction for audit trail.
     * Deductions are modeled as offset payments + allocations, but we store
     * the allocation_id here for traceability.
     */
    public function up(): void
    {
        Schema::table('payroll_deductions', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_allocation_id')->nullable()->after('payroll_installment_id');
            $table->foreign('payment_allocation_id')->references('id')->on('payment_allocations')->onDelete('set null');
            $table->index('payment_allocation_id', 'idx_deduction_allocation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_deductions', function (Blueprint $table) {
            $table->dropForeign(['payment_allocation_id']);
            $table->dropIndex('idx_deduction_allocation');
            $table->dropColumn('payment_allocation_id');
        });
    }
};
