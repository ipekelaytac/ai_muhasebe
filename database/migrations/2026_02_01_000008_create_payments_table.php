<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments - Cash/Bank movements
 * 
 * This tracks actual money movement, independent of obligations.
 * A payment can settle one or many documents via allocations.
 * 
 * Cash/Bank balance = sum(payments where direction=in) - sum(payments where direction=out)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            // Payment identification
            $table->string('payment_number', 50); // Auto-generated
            $table->string('reference_number', 100)->nullable(); // External reference
            
            // Payment type
            $table->enum('type', [
                'cash_in',       // Kasa girişi
                'cash_out',      // Kasa çıkışı
                'bank_in',       // Banka girişi
                'bank_out',      // Banka çıkışı
                'bank_transfer', // Havale/EFT (can be in or out)
                'pos_in',        // POS tahsilat
                'cheque_in',     // Çek tahsilat (when cheque is cashed)
                'cheque_out',    // Çek ödeme (when we give cheque)
                'transfer',      // Internal transfer (kasa<->banka)
            ]);
            
            // Direction: money coming in or going out
            $table->enum('direction', ['in', 'out']);
            
            // Party (optional - some payments like internal transfers may not have party)
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            
            // Payment destination (one of these should be filled)
            $table->foreignId('cashbox_id')->nullable()->constrained('cashboxes')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            
            // For transfers: destination account
            $table->foreignId('to_cashbox_id')->nullable()->constrained('cashboxes')->nullOnDelete();
            $table->foreignId('to_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            
            // Payment details
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('TRY');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            
            // For POS/bank payments
            $table->decimal('fee_amount', 15, 2)->default(0); // Commission/fee
            $table->decimal('net_amount', 15, 2); // Amount after fees
            
            // Status
            $table->enum('status', [
                'pending',    // Not yet confirmed
                'confirmed',  // Confirmed payment
                'cancelled',  // Cancelled
                'reversed',   // This is a reversal
            ])->default('confirmed');
            
            // For reversals
            $table->foreignId('reversed_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('reversal_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            
            // Link to cheque if payment is cheque-based
            $table->unsignedBigInteger('cheque_id')->nullable(); // FK added after cheques table
            
            // Period reference
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['company_id', 'payment_number'], 'unique_payment_number');
            $table->index(['company_id', 'branch_id', 'payment_date', 'direction'], 'idx_payment_date_dir');
            $table->index(['company_id', 'cashbox_id', 'payment_date'], 'idx_payment_cashbox');
            $table->index(['company_id', 'bank_account_id', 'payment_date'], 'idx_payment_bank');
            $table->index(['company_id', 'party_id'], 'idx_payment_party');
            $table->index(['company_id', 'type', 'status'], 'idx_payment_type_status');
            $table->index(['company_id', 'period_year', 'period_month'], 'idx_payment_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
