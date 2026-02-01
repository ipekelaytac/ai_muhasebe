<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Number Sequences - Auto-numbering for documents and payments
 * 
 * Ensures unique sequential numbers per company/branch/type/year.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('type', 50); // document, payment, cheque
            $table->string('subtype', 50)->nullable(); // specific document type
            $table->unsignedSmallInteger('year');
            
            $table->string('prefix', 20)->nullable();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->string('suffix', 20)->nullable();
            
            $table->timestamps();
            
            $table->unique(['company_id', 'branch_id', 'type', 'subtype', 'year'], 'unique_sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
