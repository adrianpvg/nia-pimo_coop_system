<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Borrower extends Model {
    protected $fillable = ['name', 'office_id', 'co_maker']; // <--- Ensure these are here

    public function office() { return $this->belongsTo(\App\Models\Office::class); }
    public function loans() { return $this->hasMany(\App\Models\Loan::class); }
}