<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('set null');
            
            // Document identification
            $table->string('document_number')->nullable();
            $table->enum('document_type', [
                'supplier_invoice',
                'customer_invoice',
                'expense_due',
                'payroll_due',
                'overtime_due',
                'meal_due',
                'cheque_receivable',
                'cheque_payable',
                'adjustment',
                'reversal'
            ]);
            $table->enum('direction', ['receivable', 'payable']); // receivable = we expect to receive, payable = we expect to pay
            $table->enum('status', ['draft', 'posted', 'reversed', 'canceled'])->default('posted');
            
            // Party (polymorphic or direct foreign key)
            $table->foreignId('party_id')->constrained()->onDelete('restrict');
            
            // Dates
            $table->date('document_date'); // When document was issued
            $table->date('due_date'); // When payment is due
            
            // Amounts
            $table->decimal('total_amount', 15, 2); // Total document amount
            $table->decimal('paid_amount', 15, 2)->default(0); // Sum of allocations (computed, but cached for performance)
            $table->decimal('unpaid_amount', 15, 2); // total_amount - paid_amount (computed, but cached)
            
            // Reference to reversal document (if this is a reversal)
            $table->foreignId('reverses_document_id')->nullable()->constrained('documents')->onDelete('set null');
            
            // Reference to original document (if this is a reversal)
            $table->foreignId('original_document_id')->nullable()->constrained('documents')->onDelete('set null');
            
            // Category and categorization
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->onDelete('set null');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // For additional fields (invoice number, cheque number, etc.)
            
            // Audit
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'branch_id', 'party_id', 'status', 'due_date']);
            $table->index(['company_id', 'branch_id', 'document_type', 'status']);
            $table->index(['company_id', 'branch_id', 'accounting_period_id']);
            $table->index(['due_date']);
            $table->index(['document_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
    }
}
