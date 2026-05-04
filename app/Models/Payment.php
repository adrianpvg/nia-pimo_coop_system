<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    protected $fillable = [
        'loan_id', 
        'created_by',    // Added
        'updated_by',    // Added
        'period_covered', 
        'amount_paid', 
        'interest', 
        'or_number', 
        'payment_date'
    ];
    
    public function loan() { 
        return $this->belongsTo(\App\Models\Loan::class); 
    }

    // NEW: Explicit relationships for the two different user tracking columns
    public function creator() { 
        return $this->belongsTo(\App\Models\User::class, 'created_by'); 
    }

    public function updater() { 
        return $this->belongsTo(\App\Models\User::class, 'updated_by'); 
    }
}