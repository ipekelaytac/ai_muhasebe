<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cheque extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'document_id',
        'party_id',
        'type',
        'cheque_number',
        'bank_name',
        'account_number',
        'amount',
        'issue_date',
        'due_date',
        'status',
        'cashed_date',
        'bounced_date',
        'description',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'cashed_date' => 'date',
        'bounced_date' => 'date',
        'amount' => 'decimal:2',
        'metadata' => 'array',
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

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function scopeReceived($query)
    {
        return $query->where('type', 'received');
    }

    public function scopeIssued($query)
    {
        return $query->where('type', 'issued');
    }

    public function scopeInPortfolio($query)
    {
        return $query->where('status', 'in_portfolio');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['in_portfolio', 'bank_submitted']);
    }

    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    // Helper methods
    public function isReceived()
    {
        return $this->type === 'received';
    }

    public function isIssued()
    {
        return $this->type === 'issued';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isBounced()
    {
        return $this->status === 'bounced';
    }

    public function affectsCashflow()
    {
        return in_array($this->status, ['in_portfolio', 'bank_submitted', 'endorsed']);
    }
}
