<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'effective_from',
        'effective_to',
        'monthly_net_salary',
        'pay_day_1',
        'pay_amount_1',
        'pay_day_2',
        'pay_amount_2',
        'meal_allowance',
        'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'monthly_net_salary' => 'decimal:2',
        'pay_amount_1' => 'decimal:2',
        'pay_amount_2' => 'decimal:2',
        'meal_allowance' => 'decimal:2',
        'pay_day_1' => 'integer',
        'pay_day_2' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isActiveForDate($date)
    {
        $date = is_string($date) ? $date : $date->toDateString();
        return $this->effective_from <= $date
            && ($this->effective_to === null || $this->effective_to >= $date);
    }
}

