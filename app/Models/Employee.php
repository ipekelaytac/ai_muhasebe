<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'party_id',
        'full_name',
        'phone',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the party record for this employee (1-1 relationship)
     */
    public function party()
    {
        return $this->belongsTo(\App\Domain\Accounting\Models\Party::class);
    }

    public function contracts()
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function activeContract()
    {
        $today = now()->toDateString();
        return $this->hasOne(EmployeeContract::class)
            ->where('effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->latest('effective_from');
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * @deprecated Legacy Advance model removed. Use accounting documents with type=advance_given instead.
     * Access via: $employee->party->documents()->where('type', 'advance_given')
     */
    public function advances()
    {
        // Return empty relationship to prevent errors
        return $this->hasMany(PayrollItem::class)->whereRaw('1 = 0');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}

