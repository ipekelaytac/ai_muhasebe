<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            
            $table->enum('type', ['income', 'expense']);
            $table->date('transaction_date');
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 12, 2);
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            $table->index(['customer_id', 'transaction_date'], 'cust_trans_cust_date_idx');
            $table->index(['company_id', 'branch_id', 'transaction_date'], 'cust_trans_comp_br_date_idx');
            $table->index('type', 'cust_trans_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_transactions');
    }
}
