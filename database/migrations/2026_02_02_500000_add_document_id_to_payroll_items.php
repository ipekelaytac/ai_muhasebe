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
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable()->after('employee_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('restrict');
            $table->index('document_id', 'idx_payroll_item_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropIndex('idx_payroll_item_document');
            $table->dropColumn('document_id');
        });
    }
};
