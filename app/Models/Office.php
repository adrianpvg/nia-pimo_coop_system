<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Office extends Model {
    protected $fillable = ['name']; 

    public function borrowers() { return $this->hasMany(\App\Models\Borrower::class); }
}