<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document Attachments - Files attached to documents
 * 
 * Polymorphic design to support documents and payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation
            $table->string('attachable_type', 100); // Document, Payment, Cheque
            $table->unsignedBigInteger('attachable_id');
            
            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('disk', 50)->default('local'); // storage disk
            $table->string('path', 500); // storage path
            
            $table->string('description', 255)->nullable();
            
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['attachable_type', 'attachable_id'], 'idx_attachment_attachable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
    }
};
