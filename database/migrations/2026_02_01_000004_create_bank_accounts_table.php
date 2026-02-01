<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank Accounts - Company bank accounts
 * 
 * Balance is NEVER stored here - always computed from payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('bank_name', 100);
            $table->string('branch_name', 100)->nullable(); // Bank branch
            $table->string('account_number', 50)->nullable();
            $table->string('iban', 50)->nullable();
            $table->char('currency', 3)->default('TRY');
            
            // Account type
            $table->enum('account_type', ['checking', 'savings', 'credit', 'pos'])->default('checking');
            
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            
            // Opening balance from legacy system
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['company_id', 'code'], 'unique_bank_account_code');
            $table->index(['company_id', 'branch_id', 'is_active']);
            $table->index(['company_id', 'account_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
