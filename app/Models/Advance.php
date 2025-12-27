<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'advance_date',
        'amount',
        'method',
        'note',
        'status',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'amount' => 'decimal:2',
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

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function settlements()
    {
        return $this->hasMany(AdvanceSettlement::class);
    }

    public function getRemainingAmountAttribute()
    {
        $settled = $this->settlements()->sum('settled_amount');
        return $this->amount - $settled;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 1);
    }
}

