<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing indexes for production performance
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            // Ensure document_id index exists (should already exist, but verify)
            if (!$this->indexExists('payment_allocations', 'idx_alloc_document')) {
                $table->index(['document_id', 'status'], 'idx_alloc_document');
            }
            
            // Ensure payment_id index exists (should already exist, but verify)
            if (!$this->indexExists('payment_allocations', 'idx_alloc_payment')) {
                $table->index(['payment_id', 'status'], 'idx_alloc_payment');
            }
        });
        
        Schema::table('documents', function (Blueprint $table) {
            // Add index for party statement queries
            if (!$this->indexExists('documents', 'idx_doc_party_date')) {
                $table->index(['party_id', 'document_date', 'status'], 'idx_doc_party_date');
            }
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Add index for party statement queries
            if (!$this->indexExists('payments', 'idx_payment_party_date')) {
                $table->index(['party_id', 'payment_date', 'status'], 'idx_payment_party_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropIndex('idx_alloc_document');
            $table->dropIndex('idx_alloc_payment');
        });
        
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_doc_party_date');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payment_party_date');
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
