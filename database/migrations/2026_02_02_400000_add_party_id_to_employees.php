<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add party_id to employees table for accounting integration
 * 
 * Every employee must have exactly 1 corresponding Party record of type 'employee'.
 * This enables employees to appear in accounting document/payment screens.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Add party_id column (nullable initially for backfill)
            $table->unsignedBigInteger('party_id')->nullable()->after('branch_id');
            
            // Add foreign key constraint
            $table->foreign('party_id')
                ->references('id')
                ->on('parties')
                ->onDelete('restrict'); // Prevent deletion if party has accounting records
            
            // Add unique constraint to ensure 1-1 relationship
            $table->unique('party_id', 'unique_employee_party');
            
            // Add index for faster lookups
            $table->index('party_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['party_id']);
            $table->dropUnique('unique_employee_party');
            $table->dropIndex(['party_id']);
            $table->dropColumn('party_id');
        });
    }
};
