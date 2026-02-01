<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cashboxes - Physical cash storage locations (Kasalar)
 * 
 * Balance is NEVER stored here - always computed from payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('code', 50);
            $table->string('name', 100);
            $table->char('currency', 3)->default('TRY');
            $table->text('description')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            
            // Opening balance from legacy system (only for initial migration)
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['company_id', 'code'], 'unique_cashbox_code');
            $table->index(['company_id', 'branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashboxes');
    }
};
