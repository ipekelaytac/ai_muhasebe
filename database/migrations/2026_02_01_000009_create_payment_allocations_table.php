<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment Allocations - Links payments to documents
 * 
 * This is the settlement mechanism. One payment can settle multiple documents,
 * and one document can be settled by multiple payments (partial payments).
 * 
 * Rules:
 * - sum(allocations for document) <= document.total_amount
 * - sum(allocations for payment) <= payment.amount
 * - If overpayment, create advance_credit document for the excess
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('restrict');
            $table->foreignId('document_id')->constrained()->onDelete('restrict');
            
            $table->decimal('amount', 15, 2); // Allocated amount
            
            // Allocation date (usually same as payment date, but can differ)
            $table->date('allocation_date');
            
            $table->text('notes')->nullable();
            
            // Status
            $table->enum('status', ['active', 'cancelled'])->default('active');
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes
            $table->index(['document_id', 'status'], 'idx_alloc_document');
            $table->index(['payment_id', 'status'], 'idx_alloc_payment');
            
            // Prevent duplicate allocations (same payment to same document)
            // Note: Multiple allocations allowed for partial reversals, so no unique constraint
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
