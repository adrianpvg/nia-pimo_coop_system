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

        if ($loan->type === 'SPECIAL LOAN') {
            $this->generateSpecialLoanSchedule($loan);
        } elseif ($loan->type === 'CASAB') {
            $this->generateCasabLoanSchedule($loan);
        } else {
            $this->generateRegularLoanSchedule($loan);
        }
    }

    private function generateCasabLoanSchedule(Loan $loan)
    {
        $paymentStart = Carbon::parse($loan->payment_start);
        $paymentEnd = Carbon::parse($loan->payment_end);
        
        $totalDays = $paymentStart->diffInDays($paymentEnd);
        
        $principalDue = $loan->amount_granted;
        $interestRateDecimal = $loan->base_interest / 100;
        
        $interestDue = round($principalDue * ($totalDays / 30) * $interestRateDecimal, 2);
        
        $totalDue = $principalDue + $interestDue;
        
        $schedules[] = [
            'loan_id' => $loan->id,
            'period_start' => $paymentStart->format('Y-m-d'),
            'period_end' => $paymentEnd->format('Y-m-d'),
            'principal_due' => $principalDue,
            'interest_due' => $interestDue,
            'total_due' => $totalDue,
            'balance_after' => 0, 
            'created_at' => now(),
            'updated_at' => now(),
        ];

        LoanSchedule::insert($schedules);

        $loan->update([
            'interest' => $interestDue
        ]);
    }

    private function generateSpecialLoanSchedule(Loan $loan)
    {
        $totalPeriods = $loan->actual_months; 
        $principalDue = round($loan->amount_granted / $totalPeriods, 2);
        
        $interestRateDecimal = $loan->base_interest / 100;
        $interestDue = round($loan->amount_granted * $interestRateDecimal, 2);
        
        $totalDue = $principalDue + $interestDue;
        
        $exactRunningBalance = $loan->amount_granted;
        $currentDate = Carbon::parse($loan->payment_start);
        
        $schedules = [];
        $totalRoundedInterestAdded = 0;
        
        for ($i = 1; $i <= $totalPeriods; $i++) {
            $periodStart = $currentDate->copy()->startOfMonth();
            $periodEnd = $currentDate->copy()->endOfMonth();

            if ($i === $totalPeriods) {
                $actualPrincipal = round($exactRunningBalance, 2);
            } else {
                $actualPrincipal = $principalDue;
            }

            $exactRunningBalance -= $actualPrincipal;
            $totalRoundedInterestAdded += $interestDue;

            $schedules[] = [
                'loan_id' => $loan->id,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'principal_due' => $actualPrincipal,
                'interest_due' => $interestDue,
                'total_due' => round($actualPrincipal + $interestDue, 2),
                'balance_after' => max(0, round($exactRunningBalance, 2)),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $currentDate->addMonth();
        }

        LoanSchedule::insert($schedules);

        $loan->update([
            'interest' => $totalRoundedInterestAdded
        ]);
    }

    private function generateRegularLoanSchedule(Loan $loan)
    {
        $exactRunningBalance = $loan->amount_granted;
        $exactMonthlyBalance = $loan->amount_granted; 
        
        $totalPeriods = $loan->actual_months * 2;
        $exactPrincipalDue = $exactRunningBalance / $totalPeriods;
        
        $baseDecimal = $loan->base_interest / 100;
        $interestRate = $baseDecimal / 2; 
        
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

            $exactInterestDue = $exactMonthlyBalance * $interestRate;
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

            $showBalance = !$isFirstHalf || $i === $totalPeriods;

            $schedules[] = [
                'loan_id' => $loan->id,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'principal_due' => $principalDue,
                'interest_due' => $interestDue,
                'total_due' => $totalDue,
                'balance_after' => $showBalance ? max(0, round($exactRunningBalance, 2)) : 0, 
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!$isFirstHalf) {
                $exactMonthlyBalance = $exactRunningBalance;
                $currentDate->addMonth();
            }
        }

        LoanSchedule::insert($schedules);

        $loan->update([
            'interest' => $totalRoundedInterestAdded
        ]);
    }
}