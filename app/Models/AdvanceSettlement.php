<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'advance_id',
        'payroll_item_id',
        'payroll_installment_id',
        'settled_amount',
        'settled_date',
        'created_by',
    ];

    protected $casts = [
        'settled_date' => 'date',
        'settled_amount' => 'decimal:2',
    ];

    public function advance()
    {
        return $this->belongsTo(Advance::class);
    }

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function installment()
    {
        return $this->belongsTo(PayrollInstallment::class, 'payroll_installment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

