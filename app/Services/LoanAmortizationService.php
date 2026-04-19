<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Carbon\Carbon;

class LoanAmortizationService
{
    public function generateSchedule(Loan $loan)
    {
        // 1. Clear any existing schedule in case they are updating the term
        $loan->schedules()->delete();

        // 2. Setup initial variables
        $balance = $loan->amount_granted;
        $totalPeriods = $loan->actual_months * 2; // 2 periods per month (1-15, 16-EOM)
        
        // Fixed principal spread evenly across all periods
        $basePrincipalDue = round($balance / $totalPeriods, 2);
        
        // Start date tracking based on the loan's payment_start
        $currentDate = Carbon::parse($loan->payment_start);
        
        $schedules = [];

        // 3. Loop through and generate each period
        for ($i = 1; $i <= $totalPeriods; $i++) {
            
            // Determine if this is the 1st half or 2nd half of the month
            $isFirstHalf = ($i % 2 !== 0); 

            if ($isFirstHalf) {
                $periodStart = $currentDate->copy()->startOfMonth(); // e.g., Feb 1
                $periodEnd = $currentDate->copy()->day(15);          // e.g., Feb 15
            } else {
                $periodStart = $currentDate->copy()->day(16);        // e.g., Feb 16
                $periodEnd = $currentDate->copy()->endOfMonth();     // e.g., Feb 28/29 (Handles leap years automatically!)
            }

            // Calculate Math
            // On the absolute last period, charge whatever exact balance is left to fix rounding decimal drift
            if ($i === $totalPeriods) {
                $principalDue = $balance;
            } else {
                $principalDue = $basePrincipalDue;
            }

            // Excel Formula: Balance * 0.015 / 2  (Which is 0.0075 per half-month)
            $interestDue = round($balance * 0.0075, 2);
            $totalDue = round($principalDue + $interestDue, 2);
            
            $balance = round($balance - $principalDue, 2);

            // Add row to our array
            $schedules[] = [
                'loan_id' => $loan->id,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'principal_due' => $principalDue,
                'interest_due' => $interestDue,
                'total_due' => $totalDue,
                'balance_after' => max(0, $balance), // Ensure it doesn't show -0.00
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // If we just finished the second half of the month, advance the clock to the next month
            if (!$isFirstHalf) {
                $currentDate->addMonth();
            }
        }

        // 4. Save everything to the database in one quick query
        LoanSchedule::insert($schedules);
    }
}