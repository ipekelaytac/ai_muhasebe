<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('accounting_period_id')->nullable()->constrained()->onDelete('set null');
            
            // Payment identification
            $table->string('payment_number')->nullable();
            $table->enum('payment_type', [
                'cash_in',
                'cash_out',
                'bank_in',
                'bank_out',
                'transfer',
                'pos_in'
            ]);
            $table->enum('direction', ['inflow', 'outflow']);
            $table->enum('status', ['draft', 'posted', 'reversed', 'canceled'])->default('posted');
            
            // Party (optional - payment may not be linked to a party directly)
            $table->foreignId('party_id')->nullable()->constrained()->onDelete('set null');
            
            // Cash/Bank account
            $table->foreignId('cashbox_id')->nullable()->constrained()->onDelete('restrict');
            $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('restrict');
            
            // Transfer: from/to accounts
            $table->foreignId('from_cashbox_id')->nullable()->constrained('cashboxes')->onDelete('restrict');
            $table->foreignId('to_cashbox_id')->nullable()->constrained('cashboxes')->onDelete('restrict');
            $table->foreignId('from_bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('restrict');
            $table->foreignId('to_bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('restrict');
            
            // Dates
            $table->date('payment_date');
            
            // Amounts
            $table->decimal('amount', 15, 2);
            $table->decimal('allocated_amount', 15, 2)->default(0); // Sum of allocations (cached)
            $table->decimal('unallocated_amount', 15, 2); // amount - allocated_amount (cached)
            
            // Description
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // For additional fields
            
            // Reference to reversal payment (if this is a reversal)
            $table->foreignId('reverses_payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->foreignId('original_payment_id')->nullable()->constrained('payments')->onDelete('set null');
            
            // Audit
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'branch_id', 'payment_date', 'direction']);
            $table->index(['company_id', 'branch_id', 'payment_type', 'status']);
            $table->index(['company_id', 'branch_id', 'party_id']);
            $table->index(['cashbox_id', 'payment_date']);
            $table->index(['bank_account_id', 'payment_date']);
            $table->index(['accounting_period_id']);
        });
        
        // Constraint: payment must have either cashbox or bank_account (or both for transfers)
        // This is enforced at application level, but we can add a check constraint if DB supports it
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
