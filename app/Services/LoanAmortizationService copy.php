<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Carbon\Carbon;

class LoanAmortizationService
{
    public function generateSchedule(Loan $loan)
    {
        $loan->schedules()->delete();

        $exactRunningBalance = $loan->amount_granted;
        $exactMonthlyBalance = $loan->amount_granted; 
        
        $totalPeriods = $loan->actual_months * 2; 
        $exactPrincipalDue = $exactRunningBalance / $totalPeriods;
        $interestRatePerHalfMonth = ($loan->base_interest * 0.01) / 2;
        $currentDate = Carbon::parse($loan->payment_start);
        
        $schedules = [];
        
        $totalRoundedInterestAdded = 0; 
        $exactTotalInterestAccumulator = 0; 

        for ($i = 1; $i <= $totalPeriods; $i++) {
            
            $isFirstHalf = ($i % 2 !== 0); 

            if ($isFirstHalf) {
                $periodStart = $currentDate->copy()->startOfMonth();
                $periodEnd = $currentDate->copy()->day(15);
            } else {
                $periodStart = $currentDate->copy()->day(16);
                $periodEnd = $currentDate->copy()->endOfMonth();
            }

            if ($i === $totalPeriods) {
                $principalDue = round($exactRunningBalance, 2);
            } else {
                $principalDue = round($exactPrincipalDue, 2);
            }

            $exactInterestDue = $exactMonthlyBalance * $interestRatePerHalfMonth;
            $exactTotalInterestAccumulator += $exactInterestDue;

            if ($i === $totalPeriods) {
                $targetTotalInterest = round($exactTotalInterestAccumulator, 2);
                $interestDue = round($targetTotalInterest - $totalRoundedInterestAdded, 2);
            } else {
                $interestDue = round($exactInterestDue, 2);
            }

            $totalDue = round($principalDue + $interestDue, 2);
            
            $exactRunningBalance -= $exactPrincipalDue;
            
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

        LoanSchedule::insert($schedules);
    }
}