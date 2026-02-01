<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document Lines - Line items for documents
 * 
 * Optional but recommended for:
 * - Multi-category invoices
 * - Tax splits
 * - Detailed reporting
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description', 255);
            
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit', 20)->nullable(); // adet, kg, m, etc.
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2); // quantity * unit_price - discounts
            
            // Tax
            $table->decimal('tax_rate', 5, 2)->default(0); // e.g., 20 for 20%
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2); // subtotal + tax
            
            // Categorization
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->json('tags')->nullable();
            
            $table->timestamps();
            
            $table->index(['document_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_lines');
    }
};
