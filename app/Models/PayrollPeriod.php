<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'year',
        'month',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function getPeriodNameAttribute()
    {
        $monthNames = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        ];
        return $monthNames[$this->month] . ' ' . $this->year;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 1);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 0);
    }
}

