<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            
            $table->string('code', 50)->nullable();
            $table->string('name', 255);
            $table->enum('type', ['customer', 'supplier'])->default('customer');
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('tax_office', 255)->nullable();
            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['company_id', 'branch_id']);
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
