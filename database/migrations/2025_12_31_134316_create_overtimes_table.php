<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOvertimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            
            $table->date('overtime_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 5, 2);
            $table->decimal('rate', 10, 2);
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            $table->index(['company_id', 'branch_id']);
            $table->index('employee_id');
            $table->index('overtime_date');
            $table->index(['employee_id', 'overtime_date'], 'overtime_emp_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('overtimes');
    }
}
