<?php

namespace App\Observers;

use App\Models\Employee;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Enums\PartyType;
use Illuminate\Support\Facades\DB;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        // Create corresponding Party if not exists
        if (!$employee->party_id) {
            DB::transaction(function () use ($employee) {
                $code = Party::generateCode($employee->company_id, PartyType::EMPLOYEE);
                
                $party = Party::create([
                    'company_id' => $employee->company_id,
                    'branch_id' => $employee->branch_id,
                    'type' => PartyType::EMPLOYEE,
                    'linkable_type' => Employee::class,
                    'linkable_id' => $employee->id,
                    'code' => $code,
                    'name' => $employee->full_name,
                    'phone' => $employee->phone,
                    'is_active' => (bool) $employee->status,
                ]);

                // Update employee with party_id (bypass observer to avoid recursion)
                $employee->party_id = $party->id;
                $employee->saveQuietly(); // Use saveQuietly to bypass events
            });
        }
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        // Sync party data when employee is updated
        if ($employee->party_id) {
            DB::transaction(function () use ($employee) {
                $party = Party::find($employee->party_id);
                
                if ($party && $party->linkable_type === Employee::class && $party->linkable_id === $employee->id) {
                    // Update party fields to match employee
                    $party->update([
                        'name' => $employee->full_name,
                        'phone' => $employee->phone,
                        'branch_id' => $employee->branch_id,
                        'is_active' => (bool) $employee->status,
                    ]);
                }
            });
        }
    }

    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        // Do not delete party - it may have accounting records
        // Just mark as inactive if needed
        if ($employee->party_id) {
            $party = Party::find($employee->party_id);
            if ($party) {
                $party->update(['is_active' => false]);
            }
        }
    }
}
