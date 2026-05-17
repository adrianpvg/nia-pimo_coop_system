<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    protected $fillable = [
        'loan_id', 
        'created_by',    
        'updated_by',    
        'period_covered', 
        'amount_paid', 
        'interest', 
        'or_number', 
        'payment_date'
    ];
    
    public function loan() { 
        return $this->belongsTo(\App\Models\Loan::class); 
    }

    public function creator() { 
        return $this->belongsTo(\App\Models\User::class, 'created_by'); 
    }

    public function updater() { 
        return $this->belongsTo(\App\Models\User::class, 'updated_by'); 
    }
}