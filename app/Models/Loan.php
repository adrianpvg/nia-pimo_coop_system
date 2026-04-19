<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model {
    protected $fillable = [
        'borrower_id', 
        'type',
        'control_number',
        'date_of_application',
        'amount_granted', 
        'service_fee',
        'interest_rate',
        'surcharge',
        'net_proceeds',
        'payment_start', 
        'payment_end',
        'no_of_months',
        'actual_months'
    ];

    public function borrower() { return $this->belongsTo(\App\Models\Borrower::class); }
    public function payments() { return $this->hasMany(\App\Models\Payment::class); }

    public function getBalanceAttribute() {
        return $this->amount_granted - $this->payments()->sum('amount_paid');
    }

    public function schedules() { return $this->hasMany(\App\Models\LoanSchedule::class); }
}