<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounting Periods - Month locking mechanism
 * 
 * Once locked, no documents or payments can be created/modified in that period.
 * Adjustments must be made via reversals in an open period.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'locked', 'closed'])->default('open');
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('lock_notes')->nullable();
            $table->timestamps();
            
            // Each company can have only one period per year/month
            $table->unique(['company_id', 'year', 'month'], 'unique_company_period');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
