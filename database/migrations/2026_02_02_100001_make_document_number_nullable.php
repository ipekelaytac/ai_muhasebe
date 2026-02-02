<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Make document_number nullable and remove unique constraint
 * 
 * This allows documents to be created without document numbers,
 * simplifying the system and avoiding sequence generation issues.
 */
return new class extends Migration
{
    public function up(): void
    {
        // This migration is no longer needed - we'll keep document_number NOT NULL
        // and fix the number generation instead
        // Migration kept for backward compatibility but does nothing
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Drop regular index
            $table->dropIndex('idx_doc_number');
        });
        
        // Make document_number NOT NULL (this might fail if there are NULL values)
        DB::statement('ALTER TABLE documents MODIFY document_number VARCHAR(50) NOT NULL');
        
        Schema::table('documents', function (Blueprint $table) {
            // Re-add unique constraint
            $table->unique(['company_id', 'document_number'], 'unique_document_number');
        });
    }
    
    /**
     * Check if a constraint exists
     */
    private function constraintExists(string $table, string $constraint): bool
    {
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME 
             FROM information_schema.TABLE_CONSTRAINTS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = ? 
             AND CONSTRAINT_NAME = ?",
            [$table, $constraint]
        );
        
        return count($constraints) > 0;
    }
};
