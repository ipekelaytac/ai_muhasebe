<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop legacy accounting tables that have been replaced by the new accounting system.
 * 
 * Legacy tables being dropped:
 * - customers -> replaced by parties
 * - customer_transactions -> replaced by documents + payments + payment_allocations
 * - finance_transactions -> replaced by documents
 * - checks -> replaced by cheques
 * - advances -> replaced by documents (type: advance_given/advance_received)
 * - advance_settlements -> replaced by payment_allocations
 * - transaction_attachments -> replaced by document_attachments
 * 
 * IMPORTANT: This migration assumes all legacy tables are empty.
 * Run 'php artisan accounting:verify-legacy-empty' before running this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop in order: child tables first (due to foreign key constraints)
        
        // 1. Drop advance_settlements (FK to advances)
        if (Schema::hasTable('advance_settlements')) {
            Schema::dropIfExists('advance_settlements');
        }
        
        // 2. Drop transaction_attachments (FK to finance_transactions)
        if (Schema::hasTable('transaction_attachments')) {
            Schema::dropIfExists('transaction_attachments');
        }
        
        // 3. Drop customer_transactions (FK to customers)
        if (Schema::hasTable('customer_transactions')) {
            Schema::dropIfExists('customer_transactions');
        }
        
        // 4. Drop parent tables
        if (Schema::hasTable('advances')) {
            Schema::dropIfExists('advances');
        }
        
        if (Schema::hasTable('finance_transactions')) {
            Schema::dropIfExists('finance_transactions');
        }
        
        if (Schema::hasTable('checks')) {
            Schema::dropIfExists('checks');
        }
        
        if (Schema::hasTable('customers')) {
            Schema::dropIfExists('customers');
        }
    }

    public function down(): void
    {
        // Intentionally empty - we do not want to recreate legacy tables
        // If rollback is needed, restore from backup or re-run original migrations
    }
};
