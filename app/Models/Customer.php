<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'type',
        'phone',
        'email',
        'address',
        'tax_number',
        'tax_office',
        'status',
        'notes',
    ];

    protected $casts = [
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

    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    public function checks()
    {
        return $this->hasMany(Check::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeCustomer($query)
    {
        return $query->where('type', 'customer');
    }

    public function scopeSupplier($query)
    {
        return $query->where('type', 'supplier');
    }

    public function getBalanceAttribute()
    {
        $income = $this->transactions()->where('type', 'income')->sum('amount');
        $expense = $this->transactions()->where('type', 'expense')->sum('amount');
        return $income - $expense;
    }
}

