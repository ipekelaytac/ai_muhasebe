<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit Logs - Comprehensive change tracking
 * 
 * Tracks all changes to financial records for audit compliance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            
            // What was changed
            $table->string('auditable_type', 100);
            $table->unsignedBigInteger('auditable_id');
            
            // Action type
            $table->enum('action', ['create', 'update', 'delete', 'restore', 'status_change', 'lock', 'unlock']);
            
            // Change details
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            
            // Who made the change
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            
            // When
            $table->timestamp('created_at');
            
            // Indexes
            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_auditable');
            $table->index(['company_id', 'created_at'], 'idx_audit_company_date');
            $table->index(['user_id', 'created_at'], 'idx_audit_user_date');
            $table->index(['action', 'created_at'], 'idx_audit_action_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
