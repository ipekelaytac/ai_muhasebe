<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('line_number')->default(1);
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->onDelete('set null');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 3)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('amount', 15, 2); // quantity * unit_price or direct amount
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->json('metadata')->nullable(); // For additional fields
            $table->timestamps();
            
            $table->index(['document_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_lines');
    }
}
