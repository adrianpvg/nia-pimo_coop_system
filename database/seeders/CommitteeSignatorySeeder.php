<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommitteeSignatory;

class CommitteeSignatorySeeder extends Seeder
{
    public function run()
    {
        CommitteeSignatory::create([
            'credit_committee_name' => 'ARNEL S. ABALOS',
            'chair_person_name' => 'FRANCIS DAVE T. RAMIREZ',
        ]);
    }
}
