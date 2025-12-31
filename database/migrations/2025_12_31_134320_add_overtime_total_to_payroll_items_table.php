<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOvertimeTotalToPayrollItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('overtime_total', 12, 2)->default(0)->after('meal_allowance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn('overtime_total');
        });
    }
}
