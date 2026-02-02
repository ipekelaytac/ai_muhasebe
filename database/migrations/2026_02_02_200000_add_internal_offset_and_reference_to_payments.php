<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add internal_offset payment type and reference fields for advance tracking
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add reference fields for linking payments to advance documents
            $table->string('reference_type', 255)->nullable()->after('party_id');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->index(['reference_type', 'reference_id'], 'idx_payment_reference');
        });
        
        // Modify enum to add internal_offset
        // Note: MySQL doesn't support ALTER ENUM directly, so we need to recreate the column
        DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM(
            'cash_in',
            'cash_out',
            'bank_in',
            'bank_out',
            'bank_transfer',
            'pos_in',
            'cheque_in',
            'cheque_out',
            'transfer',
            'internal_offset'
        ) NOT NULL");
        
        // Modify direction enum to add 'internal'
        DB::statement("ALTER TABLE payments MODIFY COLUMN direction ENUM('in', 'out', 'internal') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payment_reference');
            $table->dropColumn(['reference_type', 'reference_id']);
        });
        
        // Remove internal_offset from enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM(
            'cash_in',
            'cash_out',
            'bank_in',
            'bank_out',
            'bank_transfer',
            'pos_in',
            'cheque_in',
            'cheque_out',
            'transfer'
        ) NOT NULL");
        
        // Remove 'internal' from direction enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN direction ENUM('in', 'out') NOT NULL");
    }
};
