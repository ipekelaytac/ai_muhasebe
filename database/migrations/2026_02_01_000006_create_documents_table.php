<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents - Core obligation/accrual records
 * 
 * This is the heart of the accounting system. Each document represents
 * an obligation (payable or receivable) that may or may not be settled.
 * 
 * Document creates an expectation of cash flow:
 * - Payable (borç): We owe someone money
 * - Receivable (alacak): Someone owes us money
 * 
 * CRITICAL: Balance = total_amount - sum(allocations)
 * Never store remaining balance as source of truth!
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            // Document identification
            $table->string('document_number', 50); // Auto-generated or manual
            $table->string('reference_number', 100)->nullable(); // External reference (invoice #, etc.)
            
            // Document type determines the nature of obligation
            $table->enum('type', [
                'supplier_invoice',    // Alım faturası (payable)
                'customer_invoice',    // Satış faturası (receivable)
                'expense_due',         // Gider tahakkuku (payable)
                'income_due',          // Gelir tahakkuku (receivable)
                'payroll_due',         // Maaş tahakkuku (payable to employee)
                'overtime_due',        // Mesai tahakkuku (payable to employee)
                'meal_due',            // Yemek parası tahakkuku (payable to employee)
                'advance_given',       // Verilen avans (receivable from employee)
                'advance_received',    // Alınan avans (payable to customer/supplier)
                'cheque_receivable',   // Alınan çek (receivable)
                'cheque_payable',      // Verilen çek (payable)
                'adjustment_debit',    // Borç düzeltme
                'adjustment_credit',   // Alacak düzeltme
                'opening_balance',     // Açılış bakiyesi
            ]);
            
            // Direction: determines if this creates a payable or receivable
            // payable = we owe (borç), receivable = we are owed (alacak)
            $table->enum('direction', ['payable', 'receivable']);
            
            // Party (counterparty) for this document
            $table->foreignId('party_id')->constrained('parties')->onDelete('restrict');
            
            // Dates
            $table->date('document_date'); // Date of the document
            $table->date('due_date')->nullable(); // When payment is expected
            
            // Amounts (always positive)
            $table->decimal('total_amount', 15, 2);
            $table->char('currency', 3)->default('TRY');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            
            // Tax breakdown (optional)
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            
            // Categorization
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->json('tags')->nullable(); // Flexible tagging
            
            // Status tracking
            $table->enum('status', [
                'draft',      // Not yet finalized
                'pending',    // Active, awaiting settlement
                'partial',    // Partially settled
                'settled',    // Fully settled
                'cancelled',  // Cancelled (should have reversal document)
                'reversed',   // This is a reversal document
            ])->default('pending');
            
            // For reversals
            $table->foreignId('reversed_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('reversal_document_id')->nullable()->constrained('documents')->nullOnDelete();
            
            // Link to related records (polymorphic - for legacy integration)
            $table->string('source_type', 100)->nullable(); // e.g., PayrollItem, Overtime
            $table->unsignedBigInteger('source_id')->nullable();
            
            // Link to cheque if this is a cheque document
            $table->unsignedBigInteger('cheque_id')->nullable(); // Will add FK after cheques table
            
            // Period reference
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for common queries
            $table->unique(['company_id', 'document_number'], 'unique_document_number');
            $table->index(['company_id', 'branch_id', 'party_id', 'status', 'due_date'], 'idx_doc_party_status');
            $table->index(['company_id', 'type', 'status'], 'idx_doc_type_status');
            $table->index(['company_id', 'direction', 'status', 'due_date'], 'idx_doc_direction_due');
            $table->index(['company_id', 'period_year', 'period_month'], 'idx_doc_period');
            $table->index(['company_id', 'document_date'], 'idx_doc_date');
            $table->index(['company_id', 'category_id'], 'idx_doc_category');
            $table->index(['source_type', 'source_id'], 'idx_doc_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
