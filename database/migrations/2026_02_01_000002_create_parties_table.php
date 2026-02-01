<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parties - Unified table for all counterparties (customers, suppliers, employees, others)
 * 
 * This provides a single reference point for all obligations and payments.
 * Existing customers, employees tables remain but are linked here for accounting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            // Party type: customer, supplier, employee, other, tax_authority, bank
            $table->enum('type', [
                'customer',
                'supplier', 
                'employee',
                'other',
                'tax_authority',
                'bank'
            ]);
            
            // Link to existing tables (polymorphic link)
            $table->string('linkable_type', 100)->nullable(); // App\Models\Customer, App\Models\Employee
            $table->unsignedBigInteger('linkable_id')->nullable();
            
            // Party details (can be synced from linked entity or standalone)
            $table->string('code', 50)->nullable(); // Unique code per company
            $table->string('name', 255);
            $table->string('tax_number', 50)->nullable();
            $table->string('tax_office', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->default('Türkiye');
            
            // Credit terms
            $table->unsignedInteger('payment_terms_days')->default(0); // Default due date offset
            $table->decimal('credit_limit', 15, 2)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['company_id', 'code'], 'unique_party_code');
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'branch_id', 'type']);
            $table->index(['linkable_type', 'linkable_id']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
