<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'check_number',
        'bank_name',
        'amount',
        'received_date',
        'due_date',
        'cashed_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'due_date' => 'date',
        'cashed_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCashed($query)
    {
        return $query->where('status', 'cashed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}

