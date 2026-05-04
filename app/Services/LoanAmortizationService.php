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

        // 2. Setup initial variables using EXACT, unrounded math
        $exactRunningBalance = $loan->amount_granted;
        $exactMonthlyBalance = $loan->amount_granted; 
        
        $totalPeriods = $loan->actual_months * 2; 
        $exactPrincipalDue = $exactRunningBalance / $totalPeriods;
        $interestRatePerHalfMonth = ($loan->base_interest * 0.01) / 2;
        $currentDate = Carbon::parse($loan->payment_start);
        
        $schedules = [];
        
        // 👉 THE FIX: Trackers to force the final sum to match Excel perfectly
        $totalRoundedInterestAdded = 0; 
        $exactTotalInterestAccumulator = 0; 

        // 3. Loop through and generate each period
        for ($i = 1; $i <= $totalPeriods; $i++) {
            
            $isFirstHalf = ($i % 2 !== 0); 

            if ($isFirstHalf) {
                $periodStart = $currentDate->copy()->startOfMonth();
                $periodEnd = $currentDate->copy()->day(15);
            } else {
                $periodStart = $currentDate->copy()->day(16);
                $periodEnd = $currentDate->copy()->endOfMonth();
            }

            // --- PRINCIPAL CALCULATION ---
            if ($i === $totalPeriods) {
                $principalDue = round($exactRunningBalance, 2);
            } else {
                $principalDue = round($exactPrincipalDue, 2);
            }

            // --- INTEREST CALCULATION ---
            // Calculate exact Excel-style interest for this period in the background
            $exactInterestDue = $exactMonthlyBalance * $interestRatePerHalfMonth;
            $exactTotalInterestAccumulator += $exactInterestDue;

            if ($i === $totalPeriods) {
                // THE FINAL ROW "PLUG":
                // Take Excel's exact grand total and subtract all previously rounded rows.
                // This forces the table's total to match Excel perfectly without breaking calculator math.
                $targetTotalInterest = round($exactTotalInterestAccumulator, 2);
                $interestDue = round($targetTotalInterest - $totalRoundedInterestAdded, 2);
            } else {
                // Normal rounding for the table display
                $interestDue = round($exactInterestDue, 2);
            }

            $totalDue = round($principalDue + $interestDue, 2);
            
            // Advance the math for the next loop
            $exactRunningBalance -= $exactPrincipalDue;
            
            // Track the rounded interest we just locked into the database
            $totalRoundedInterestAdded += $interestDue;

            $schedules[] = [
                'loan_id' => $loan->id,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'principal_due' => $principalDue,
                'interest_due' => $interestDue,
                'total_due' => $totalDue,
                'balance_after' => max(0, round($exactRunningBalance, 2)),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!$isFirstHalf) {
                $exactMonthlyBalance = $exactRunningBalance;
                $currentDate->addMonth();
            }
        }

        // 4. Save everything to the database
        LoanSchedule::insert($schedules);
    }
}