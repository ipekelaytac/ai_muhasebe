<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Production Safety: Add missing indexes for report performance
 * 
 * This migration ensures all critical query paths have proper indexes
 * for production performance.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Documents: Add composite index for party statement queries (if not exists)
        if (!$this->indexExists('documents', 'idx_doc_party_date')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->index(['party_id', 'document_date', 'status'], 'idx_doc_party_date');
            });
        }
        
        // Documents: Add index for type + direction + status queries
        if (!$this->indexExists('documents', 'idx_doc_type_dir_status')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->index(['type', 'direction', 'status', 'due_date'], 'idx_doc_type_dir_status');
            });
        }
        
        // Payments: Add composite index for party statement queries (if not exists)
        if (!$this->indexExists('payments', 'idx_payment_party_date')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['party_id', 'payment_date', 'status'], 'idx_payment_party_date');
            });
        }
        
        // Payments: Add index for type + direction queries
        if (!$this->indexExists('payments', 'idx_payment_type_dir')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['type', 'direction', 'status'], 'idx_payment_type_dir');
            });
        }
        
        // Cheques: Ensure forecast index exists
        if (!$this->indexExists('cheques', 'idx_cheque_forecast')) {
            Schema::table('cheques', function (Blueprint $table) {
                $table->index(['company_id', 'status', 'due_date'], 'idx_cheque_forecast');
            });
        }
        
        // Payment allocations: Ensure document + status index exists
        if (!$this->indexExists('payment_allocations', 'idx_alloc_document')) {
            Schema::table('payment_allocations', function (Blueprint $table) {
                $table->index(['document_id', 'status'], 'idx_alloc_document');
            });
        }
        
        // Payment allocations: Ensure payment + status index exists
        if (!$this->indexExists('payment_allocations', 'idx_alloc_payment')) {
            Schema::table('payment_allocations', function (Blueprint $table) {
                $table->index(['payment_id', 'status'], 'idx_alloc_payment');
            });
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_doc_party_date');
            $table->dropIndex('idx_doc_type_dir_status');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payment_party_date');
            $table->dropIndex('idx_payment_type_dir');
        });
        
        Schema::table('cheques', function (Blueprint $table) {
            $table->dropIndex('idx_cheque_forecast');
        });
        
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropIndex('idx_alloc_document');
            $table->dropIndex('idx_alloc_payment');
        });
    }
    
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );
        
        return $result[0]->count > 0;
    }
};
