<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            
            // What was changed
            $table->string('auditable_type'); // Document, Payment, Allocation, etc.
            $table->unsignedBigInteger('auditable_id');
            
            // Who changed it
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            
            // What happened
            $table->string('event'); // created, updated, deleted, allocated, reversed, etc.
            
            // What changed (JSON of old/new values)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            
            // Additional context
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['company_id', 'branch_id']);
            $table->index(['user_id']);
            $table->index(['event']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}
