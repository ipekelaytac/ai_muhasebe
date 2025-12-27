<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['income', 'expense']);
            $table->string('name', 190);
            $table->foreignId('parent_id')->nullable()->constrained('finance_categories')->nullOnDelete();

            $table->boolean('is_active')->default(1);

            $table->timestamps();

            $table->unique(['company_id', 'type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finance_categories');
    }
}
