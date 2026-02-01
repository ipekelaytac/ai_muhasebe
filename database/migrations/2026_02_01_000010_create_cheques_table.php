<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cheques - Received and Issued cheques (Alınan/Verilen Çekler)
 * 
 * Cheques have their own lifecycle and affect cashflow forecasting.
 * When a cheque is received/issued, a document is created.
 * When a cheque is cashed, a payment is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            // Cheque identification
            $table->string('cheque_number', 50);
            $table->string('serial_number', 50)->nullable(); // Bank serial
            
            // Type: received (müşteriden alınan) or issued (biz verdik)
            $table->enum('type', ['received', 'issued']);
            
            // Party (who gave/received the cheque)
            $table->foreignId('party_id')->constrained('parties')->onDelete('restrict');
            
            // Drawer details (for received cheques - kimin çeki)
            $table->string('drawer_name', 255)->nullable();
            $table->string('drawer_tax_number', 50)->nullable();
            
            // Bank details
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            
            // Our bank account (where cheque will be deposited/drawn from)
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            
            // Dates
            $table->date('issue_date'); // When cheque was issued
            $table->date('due_date'); // Maturity date (vade tarihi)
            $table->date('receive_date')->nullable(); // When we received it
            
            // Amount
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('TRY');
            
            // Status lifecycle
            $table->enum('status', [
                'in_portfolio',     // Portföyde (we have it)
                'endorsed',         // Ciro edildi (transferred to someone)
                'deposited',        // Bankaya verildi (submitted to bank)
                'collected',        // Tahsil edildi (paid/cashed)
                'bounced',          // Karşılıksız (returned unpaid)
                'cancelled',        // İptal
                'paid',             // Ödendi (for issued cheques)
                'pending_issue',    // Henüz verilmedi (for issued cheques)
            ])->default('in_portfolio');
            
            // For endorsed cheques: who did we endorse it to
            $table->foreignId('endorsed_to_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->date('endorsement_date')->nullable();
            
            // Related document (the obligation this cheque represents)
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            
            // Payment that cleared this cheque
            $table->foreignId('cleared_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            
            // For bounced cheques
            $table->date('bounce_date')->nullable();
            $table->text('bounce_reason')->nullable();
            $table->decimal('bounce_fee', 15, 2)->default(0);
            
            $table->text('notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['company_id', 'cheque_number', 'type'], 'unique_cheque_number');
            $table->index(['company_id', 'type', 'status'], 'idx_cheque_type_status');
            $table->index(['company_id', 'party_id'], 'idx_cheque_party');
            $table->index(['company_id', 'due_date', 'status'], 'idx_cheque_due_status');
            $table->index(['company_id', 'status', 'due_date'], 'idx_cheque_forecast');
        });
        
        // Add FK from documents to cheques
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('cheque_id')->references('id')->on('cheques')->nullOnDelete();
        });
        
        // Add FK from payments to cheques
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('cheque_id')->references('id')->on('cheques')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['cheque_id']);
        });
        
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['cheque_id']);
        });
        
        Schema::dropIfExists('cheques');
    }
};
