<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChequesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            // Link to document (cheque_receivable or cheque_payable)
            $table->foreignId('document_id')->nullable()->constrained()->onDelete('set null');
            
            // Party (who gave/received the cheque)
            $table->foreignId('party_id')->constrained()->onDelete('restrict');
            
            // Cheque details
            $table->enum('type', ['received', 'issued']); // received = we received it, issued = we issued it
            $table->string('cheque_number');
            $table->string('bank_name');
            $table->string('account_number')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('issue_date'); // When cheque was issued
            $table->date('due_date'); // When cheque matures
            
            // Status tracking
            $table->enum('status', [
                'in_portfolio',      // Received/issued, in our portfolio
                'endorsed',          // Endorsed to someone else
                'bank_submitted',    // Submitted to bank for collection
                'paid',              // Cheque was paid
                'bounced',           // Cheque bounced
                'canceled'           // Cheque was canceled
            ])->default('in_portfolio');
            
            // Dates
            $table->date('cashed_date')->nullable(); // When cheque was cashed
            $table->date('bounced_date')->nullable(); // When cheque bounced
            
            // Description
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'branch_id', 'party_id']);
            $table->index(['company_id', 'branch_id', 'type', 'status']);
            $table->index(['due_date']);
            $table->index(['cheque_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cheques');
    }
}
