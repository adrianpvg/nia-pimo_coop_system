<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Office;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\Payment;

class TestSeeder extends Seeder {
    public function run() {
        // 1. Create Offices
        $ro1 = Office::create(['name' => 'RO1']);
        $pimo = Office::create(['name' => 'PIMO']);

        // 2. Create Borrower
        $borrower = Borrower::create([
            'name' => 'RENEEROSE KAMATOY',
            'office_id' => $ro1->id,
            'co_maker' => 'JOVITO GESLANI'
        ]);

        // 3. Create Loan (UPDATED WITH NEW FIELDS)
        $loan = Loan::create([
            'borrower_id' => $borrower->id,
            'type' => 'SPECIAL LOAN',
            
            // NEW FIELDS
            'control_number' => '26-01-001', 
            'date_of_application' => '2026-01-08', // Renamed from date_of_loan
            'amount_granted' => 15000.00,
            'service_fee' => 150.00,
            'interest_rate' => 0.00,
            'surcharge' => 0.00,
            'net_proceeds' => 14850.00, // Amount - Fee
            
            'payment_start' => '2026-01-08',
            'payment_end' => '2026-06-07',
            'no_of_months' => 5,
        ]);

        // 4. Create Payment
        Payment::create([
            'loan_id' => $loan->id,
            'amount_paid' => 5000.00,
            'interest' => 0.00,
            'or_number' => null,
            'payment_date' => '2026-01-20'
        ]);
    }
}