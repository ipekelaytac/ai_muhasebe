<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            
            $table->string('check_number', 100);
            $table->string('bank_name', 255);
            $table->decimal('amount', 12, 2);
            $table->date('received_date');
            $table->date('due_date');
            $table->date('cashed_date')->nullable();
            $table->enum('status', ['pending', 'cashed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            $table->index(['company_id', 'branch_id']);
            $table->index('customer_id');
            $table->index('status');
            $table->index('received_date');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('checks');
    }
}
