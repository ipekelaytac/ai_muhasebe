<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDeductionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class, 'deduction_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}

