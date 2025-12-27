<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'file_path',
        'file_type',
    ];

    public function transaction()
    {
        return $this->belongsTo(FinanceTransaction::class, 'transaction_id');
    }
}

