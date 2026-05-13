<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommitteeSignatory extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_committee_name',
        'chair_person_name'
    ];
}
