<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'period_start',
        'period_end',
        'principal_due',
        'interest_due',
        'total_due',
        'balance_after',
        'is_paid',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
