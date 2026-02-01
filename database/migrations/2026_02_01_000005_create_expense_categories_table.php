<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expense Categories - For categorizing documents
 * 
 * Extends existing finance_categories with accounting-specific fields.
 * This is a new table to avoid breaking existing functionality.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Hierarchy
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            
            $table->string('code', 50);
            $table->string('name', 100);
            
            // Type: income, expense, both (for P&L classification)
            $table->enum('type', ['income', 'expense', 'both'])->default('expense');
            
            // For P&L grouping
            $table->string('group', 50)->nullable(); // e.g., 'cost_of_goods', 'operating_expense', 'revenue'
            
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System categories cannot be deleted
            $table->unsignedInteger('sort_order')->default(0);
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['company_id', 'code'], 'unique_expense_category_code');
            $table->index(['company_id', 'type', 'is_active']);
            $table->index(['company_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
