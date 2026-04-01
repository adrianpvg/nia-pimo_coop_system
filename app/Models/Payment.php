<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    // RESTORED: 'or_number'
    protected $fillable = ['loan_id', 'amount_paid', 'interest', 'or_number', 'payment_date'];
    
    public function loan() { return $this->belongsTo(\App\Models\Loan::class); }
}