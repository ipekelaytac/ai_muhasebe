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

    public function advances()
    {
        return $this->hasMany(Advance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}

