<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(FinanceCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FinanceCategory::class, 'parent_id');
    }

    public function transactions()
    {
        return $this->hasMany(FinanceTransaction::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }
}

