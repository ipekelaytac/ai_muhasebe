<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['income', 'expense']);
            $table->foreignId('category_id')->constrained('finance_categories')->restrictOnDelete();

            $table->date('transaction_date');
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 12, 2);

            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();

            $table->string('related_table', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'transaction_date']);
            $table->index(['category_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finance_transactions');
    }
}
