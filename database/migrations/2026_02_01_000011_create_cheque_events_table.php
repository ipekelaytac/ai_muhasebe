<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cheque Events - Audit trail for cheque status changes
 * 
 * Tracks the full lifecycle of each cheque for audit purposes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained()->onDelete('cascade');
            
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->date('event_date');
            
            // Related party (for endorsements)
            $table->foreignId('related_party_id')->nullable()->constrained('parties')->nullOnDelete();
            
            // Related payment (for collections)
            $table->foreignId('related_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['cheque_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_events');
    }
};
