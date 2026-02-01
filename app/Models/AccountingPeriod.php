<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AccountingPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'year',
        'month',
        'start_date',
        'end_date',
        'status',
        'locked_by',
        'locked_at',
        'lock_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'locked_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    // Helper methods
    public function isLocked()
    {
        return $this->status === 'locked';
    }

    public function isOpen()
    {
        return $this->status === 'open';
    }

    public function containsDate($date)
    {
        $date = Carbon::parse($date);
        return $date->gte($this->start_date) && $date->lte($this->end_date);
    }

    // Static factory
    public static function findOrCreateForDate($companyId, $branchId, $date)
    {
        $date = Carbon::parse($date);
        $year = $date->year;
        $month = $date->month;

        return static::firstOrCreate(
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'start_date' => $date->copy()->startOfMonth(),
                'end_date' => $date->copy()->endOfMonth(),
                'status' => 'open',
            ]
        );
    }
}
