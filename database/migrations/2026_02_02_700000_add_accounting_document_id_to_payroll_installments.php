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
        Schema::table('payroll_installments', function (Blueprint $table) {
            $table->unsignedBigInteger('accounting_document_id')->nullable()->after('payroll_item_id');
            $table->foreign('accounting_document_id')->references('id')->on('documents')->onDelete('restrict');
            $table->index('accounting_document_id', 'idx_installment_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_installments', function (Blueprint $table) {
            $table->dropForeign(['accounting_document_id']);
            $table->dropIndex('idx_installment_document');
            $table->dropColumn('accounting_document_id');
        });
    }
};
