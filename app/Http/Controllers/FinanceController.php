<?php
namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\Borrower;
use App\Models\Office;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoansExport;
use App\Exports\RegularLoanSchedExport;
use App\Exports\SchedExport;
use App\Services\LoanAmortizationService;

class FinanceController extends Controller
{
    public function index(Request $request, $type = 'ALL')
    {
        $year = $request->input('year', Carbon::now()->year);
        $officeFilter = $request->input('office', 'ALL');

        $query = Loan::with(['borrower.office', 'payments'])->whereYear('date_of_application', $year);

        if ($type !== 'ALL') {
            $query->where('type', $type);
        }

        if ($officeFilter !== 'ALL') {
            $query->whereHas('borrower.office', function($q) use ($officeFilter) {
                $q->where('name', $officeFilter);
            });
        }

        $loans = $query->get();

        $loans->map(function($loan) {
            $loan->total_paid = $loan->payments->sum('amount_paid');
            // Added round() to fix the floating-point precision issue causing 0 balance to be marked unpaid
            $loan->balance = round($loan->amount_granted - $loan->total_paid, 2);
            $loan->is_paid = $loan->balance <= 0.00;
            return $loan;
        });

        $total_principal = $loans->sum('amount_granted');
        $summary = [
            'total_loans' => $loans->count(),
            'total_principal' => $total_principal,
            'total_net' => $loans->sum('net_proceeds'),
            'total_paid' => $loans->sum('total_paid'),
            'total_balance' => $loans->sum('balance'),
            'total_paid_count' => $loans->where('is_paid', true)->count(),
            'total_unpaid_count' => $loans->where('is_paid', false)->count(),
            'loans_per_type' => $loans->groupBy('type')->map(function($group) {
                return [
                    'paid' => $group->where('is_paid', true)->count(),
                    'total' => $group->count()
                ];
            })->toArray(),
        ];

        if ($type === 'ALL') {
            $summary['chart_data'] = $loans->groupBy('type')->map(function ($group) {
                return $group->sum('amount_granted');
            })->toArray();

            $summary['table_data'] = $loans->groupBy('type')->map(function ($group) {
                return [
                    'principal' => $group->sum('amount_granted'),
                    'net' => $group->sum('net_proceeds'),
                    'balance' => $group->sum('balance')
                ];
            });
        } else {
            if ($total_principal > 0) {
                $total_paid = $loans->sum('total_paid');
                $total_balance = $loans->sum('balance');
                $summary['chart_data'] = [
                    'Paid Amount' => $total_paid,
                    'Remaining Balance' => $total_balance
                ];
            } else {
                $summary['chart_data'] = []; 
            }
        }

        $availableYears = Loan::selectRaw('YEAR(date_of_application) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (!in_array(date('Y'), $availableYears)) {
            array_unshift($availableYears, date('Y'));
        }
        
        $availableOffices = Office::orderBy('name')->pluck('name')->toArray();

        return view('finance.index', compact('loans', 'type', 'summary', 'availableYears', 'year', 'availableOffices', 'officeFilter'));
    }

    public function store(Request $request)
    {
        $rules = [
            'type' => 'required|string',
            'borrower_name' => 'required|string',
            'employee_id' => 'required|string',
            'office_name' => 'required|string',
            'date_of_application' => 'required|date',
            'amount_granted' => 'required|numeric|min:0',
            'base_interest' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
        ];

        if ($request->type === 'REGULAR SALARY LOAN') {
            $rules['employee_type'] = 'required|string|in:Casual,COS,Permanent,Co-Terminous';
            
            // Constraint (1): Check if a borrower with this employee_id already has a Regular Salary Loan application
            $employeeIdUpper = strtoupper($request->employee_id);
            $hasExistingRegularLoan = Loan::where('type', 'REGULAR SALARY LOAN')
                ->whereHas('borrower', function($q) use ($employeeIdUpper) {
                    $q->where('employee_id', $employeeIdUpper);
                })->exists();

            if ($hasExistingRegularLoan) {
                return back()
                    ->withErrors(['employee_id' => 'This Employee ID already has an active application under Regular Loans.'])
                    ->withInput();
            }
        }

        if ($request->type === 'CASAB') {
            $rules['no_of_months'] = 'nullable'; 
            $rules['payment_start'] = 'required|date';
            $rules['payment_end'] = 'required|date|after:payment_start'; 
        } elseif ($request->type === 'SPECIAL LOAN') {
            $rules['no_of_months'] = 'required|numeric|min:1|max:6';
            $rules['payment_start'] = 'required|date';
            $rules['payment_end'] = 'required|date|after_or_equal:payment_start';
        } else {
            $rules['no_of_months'] = 'required|numeric|min:1|max:36';
            $rules['payment_start'] = 'required|date';
            $rules['payment_end'] = 'required|date|after_or_equal:payment_start';
        }

        $request->validate($rules);

        if ($request->type === 'CASAB') {
            $endMonthDay = \Carbon\Carbon::parse($request->payment_end)->format('m-d');
            if ($endMonthDay !== '05-16' && $endMonthDay !== '11-16') {
                return back()->withErrors(['type' => 'CASAB loans must end on the Mid-Year (May 16) or Year-End (Nov 16) bonus date.'])->withInput();
            }

            $start = Carbon::parse($request->payment_start);
            $end = Carbon::parse($request->payment_end);
            $exactDays = $start->diffInDays($end);
            
            $request->merge([
                'no_of_months' => round($exactDays / 30, 2)
            ]);
        }

        $date = Carbon::parse($request->date_of_application);
        $year = $date->format('y'); 
        $month = $date->format('m'); 
        
        $lastLoan = Loan::whereYear('date_of_application', $date->year)
                        ->whereMonth('date_of_application', $date->month)
                        ->orderBy('control_number', 'desc')
                        ->first();

        if ($lastLoan) {
            $lastSequence = (int) substr($lastLoan->control_number, -3);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }
        
        $control_number = $year . '-' . $month . '-' . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

        $office = Office::firstOrCreate(['name' => strtoupper($request->office_name)]);
        
        // Match or create the borrower
        $borrower = Borrower::firstOrCreate(
            ['name' => strtoupper($request->borrower_name)], 
            [
                'office_id' => $office->id,
                'employee_id' => strtoupper($request->employee_id)
            ]
        );

        if ($request->filled('co_maker') || $request->filled('employee_id')) {
            $borrower->update([
                'co_maker' => strtoupper($request->co_maker) ?? $borrower->co_maker,
                'employee_id' => strtoupper($request->employee_id) ?? $borrower->employee_id
            ]);
        }

        $loan = Loan::create([
            'borrower_id' => $borrower->id,
            'type' => strtoupper($request->type),
            'employee_type' => $request->employee_type,
            'control_number' => $control_number,
            'date_of_application' => $request->date_of_application,
            'amount_granted' => $request->amount_granted,
            'service_fee' => $request->service_fee ?? 0,
            'base_interest' => $request->base_interest ?? 0,
            'interest_rate' => $request->interest_rate ?? 0, 
            'surcharge' => $request->surcharge ?? 0,
            'net_proceeds' => $request->net_proceeds,
            'payment_start' => $request->payment_start,
            'payment_end' => $request->payment_end,
            'no_of_months' => $request->no_of_months, 
            'payment_preference' => 'half_month' 
        ]);

        return redirect()->route('finance.show', $loan->id)
                         ->with('success', 'Application Added! Control No: ' . $control_number);
    }

    public function show($id)
    {
        $loan = Loan::with(['borrower.office', 'payments', 'schedules'])->findOrFail($id);
        
        $availableOffices = Office::orderBy('name')->pluck('name')->toArray();
        if (empty($availableOffices)) {
            $availableOffices = ['PIMO', 'RO1', 'ASRIS', 'ADRIS', 'LARIS', 'SFDRIS'];
        }
        
        return view('finance.show', compact('loan', 'availableOffices'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'borrower_name' => 'required|string|max:255',
            'employee_id' => 'required|string|max:255',
            'co_maker' => 'nullable|string|max:255',
            'office_name' => 'required|string|max:255',
            'date_of_application' => 'required|date',
        ]);

        $loan = Loan::with('borrower')->findOrFail($id);
        $employeeIdUpper = strtoupper($request->employee_id);

        $idTakenByOther = Borrower::where('employee_id', $employeeIdUpper)
            ->where('id', '!=', $loan->borrower_id)
            ->exists();

        if ($idTakenByOther) {
            return back()
                ->withErrors(['employee_id' => 'This Employee ID is already assigned to a different borrower profile.'])
                ->withInput();
        }

        $office = Office::firstOrCreate(['name' => strtoupper($request->office_name)]);

        $loan->borrower->update([
            'name' => strtoupper($request->borrower_name),
            'employee_id' => $employeeIdUpper,
            'co_maker' => $request->filled('co_maker') ? strtoupper($request->co_maker) : null,
            'office_id' => $office->id,
        ]);

        $loan->update([
            'date_of_application' => $request->date_of_application,
        ]);

        return back()->with('success', 'Applicant details updated successfully.');
    }

    public function destroyLoan($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->payments()->delete(); 
        $loan->delete();
        return redirect()->route('finance.index')->with('success', 'Loan application and all related payment records have been deleted.');
    }

    public function addPayment(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'period_covered' => 'required|string',
            'or_number' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        $loan = \App\Models\Loan::with('schedules', 'payments')->findOrFail($request->loan_id);

        if ($loan->type === 'SPECIAL LOAN') {
            $request->validate([
                'custom_amount_paid' => 'required|numeric|min:1'
            ]);

            $paymentAmount = $request->custom_amount_paid;
            
            $totalPrincipal = $loan->amount_granted;
            $totalInterest = $loan->amount_granted * ($loan->base_interest / 100) * $loan->actual_months; 
            
            $paidPrincipal = $loan->payments->sum('amount_paid');
            $paidInterest = $loan->payments->sum('interest');
            
            $remPrincipal = max(0, $totalPrincipal - $paidPrincipal);
            $remInterest = max(0, $totalInterest - $paidInterest);
            $totalRemaining = $remPrincipal + $remInterest;

            if ($paymentAmount > round($totalRemaining, 2)) {
                return back()->withErrors(['custom_amount_paid' => 'Payment exceeds remaining balance.']);
            }

            $totalExpectedLiability = $totalPrincipal + $totalInterest;
            
            $principalRatio = $totalPrincipal / $totalExpectedLiability;
            $interestRatio = $totalInterest / $totalExpectedLiability;

            $appliedToPrincipal = round($paymentAmount * $principalRatio, 2);
            $appliedToInterest = round($paymentAmount * $interestRatio, 2);

            $actualTotal = $appliedToPrincipal + $appliedToInterest;
            if ($actualTotal !== $paymentAmount) {
                $difference = $paymentAmount - $actualTotal;
                $appliedToPrincipal += $difference;
            }

            if ($appliedToInterest > $remInterest) {
                $excess = $appliedToInterest - $remInterest;
                $appliedToInterest = $remInterest;
                $appliedToPrincipal += $excess;
            }

            \App\Models\Payment::create([
                'loan_id' => $loan->id,
                'created_by' => auth()->id(), 
                'period_covered' => 'SPECIAL-' . uniqid(), 
                'amount_paid' => $appliedToPrincipal,
                'interest' => $appliedToInterest,
                'or_number' => $request->or_number,
                'payment_date' => $request->payment_date,
            ]);

            return back()->with('success', 'Special Loan Payment Recorded Successfully!');
        }

        if ($loan->payments()->where('period_covered', $request->period_covered)->exists()) {
            return back()->withErrors(['period_covered' => 'This period has already been paid.']);
        }

        $schedules = $loan->schedules->filter(function ($sched) use ($request) {
            if (strlen($request->period_covered) > 7) {
                return $sched->period_end === $request->period_covered;
            } else {
                return Carbon::parse($sched->period_end)->format('Y-m') === $request->period_covered;
            }
        });

        if ($schedules->isEmpty()) {
            return back()->withErrors(['period_covered' => 'Invalid schedule period selected.']);
        }

        $totalPrincipal = $schedules->sum('principal_due');
        $totalInterest = $schedules->sum('interest_due');

        \App\Models\Payment::create([
            'loan_id' => $loan->id,
            'created_by' => auth()->id(), 
            'period_covered' => $request->period_covered,
            'amount_paid' => $totalPrincipal,
            'interest' => $totalInterest,
            'or_number' => $request->or_number,
            'payment_date' => $request->payment_date,
        ]);

        return back()->with('success', 'Payment Recorded Successfully!');
    }

    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'or_number' => 'required|string|max:255',
        ]);

        $payment = Payment::findOrFail($id);
        
        $payment->update([
            'or_number' => $request->or_number,
            'updated_by' => auth()->id(), 
        ]);

        return back()->with('success', 'Payment Service Invoice successfully updated.');
    }

    public function deletePayment($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();
        
        return back()->with('success', 'Payment successfully deleted.');
    }

    public function updateActualMonths(Request $request, $id, \App\Services\LoanAmortizationService $amortizationService)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->type === 'CASAB') {
            $rules = [ 'actual_months' => 'required|numeric' ];
        } elseif ($loan->type === 'SPECIAL LOAN') {
            $rules = [ 'actual_months' => 'required|numeric|min:1|max:6' ];
        } else {
            $rules = [
                'actual_months' => 'required|integer|min:1|max:36',
                'payment_preference' => 'required|in:half_month,whole_month'
            ];
        }

        $request->validate($rules);

        // Calculate dynamic actual months for robust backend integrity
        if ($loan->type === 'CASAB') {
            $days = Carbon::parse($loan->payment_start)->diffInDays(Carbon::parse($loan->payment_end));
            $actual_months = round($days / 30, 2);
        } else {
            $actual_months = $request->actual_months;
        }

        $principal = $loan->amount_granted;
        $base_rate = $loan->base_interest;
        $monthlyRate = $base_rate / 100;
        $totalExpectedInterest = 0;

        // DYNAMIC INTEREST CALCULATION (Matching Frontend JS)
        if ($loan->type === 'CASAB') {
            $days = Carbon::parse($loan->payment_start)->diffInDays(Carbon::parse($loan->payment_end));
            $totalExpectedInterest = $principal * ($days / 30) * $monthlyRate;

        } elseif ($loan->type === 'SPECIAL LOAN') {
            $totalExpectedInterest = $principal * $monthlyRate * $actual_months;

        } else {
            // REGULAR SALARY LOAN
            $totalPeriods = $actual_months * 2; 
            $halfMonthRate = $monthlyRate / 2;

            if ($principal > 0 && $totalPeriods > 0) {
                $basePrincipalDue = $principal / $totalPeriods;
                $runningBalance = $principal;
                $monthlyBalance = $principal; 

                for ($i = 1; $i <= $totalPeriods; $i++) {
                    $isFirstHalf = ($i % 2 !== 0);
                    $interestDue = $monthlyBalance * $halfMonthRate;
                    
                    $totalExpectedInterest += $interestDue;
                    $runningBalance -= $basePrincipalDue;

                    if (!$isFirstHalf) {
                        $monthlyBalance = $runningBalance;
                    }
                }
            }
        }

        // Calculate new Total Effective Interest Rate
        if ($principal > 0) {
            $new_interest_rate = ($totalExpectedInterest / $principal) * 100;
        } else {
            $new_interest_rate = 0;
        }

        $hasExistingPayments = $loan->payments()->exists();

        $loan->payments()->delete();

        $loan->update([
            'actual_months' => $actual_months,
            'payment_preference' => $request->payment_preference ?? 'half_month', 
            'interest_rate' => $new_interest_rate,
        ]);

        $amortizationService->generateSchedule($loan);

        // Build the dynamic success message
        $message = 'Amortization term and interest rate successfully updated.';
        if ($hasExistingPayments) {
            $message .= ' All previous payment records have been reset to match the new schedule.';
        } else {
            $message .= ' The schedule has been successfully generated.';
        }

        return back()->with('success', $message);
    }

    public function export(Request $request, $type = 'ALL') 
    {
        $year = $request->input('year', Carbon::now()->year);
        $officeFilter = $request->input('office', 'ALL');
        
        $filename = $type === 'ALL' 
            ? "all_loans_{$year}_summary.xlsx" 
            : strtolower(str_replace(' ', '_', $type)) . "_{$year}_summary.xlsx";

        return Excel::download(new LoansExport($type, $year, $officeFilter), $filename);
    }

    public function exportSched($id) 
    {
        $loan = Loan::with(['borrower.office', 'schedules', 'payments'])->findOrFail($id);
        
        $date = Carbon::parse($loan->date_of_application)->format('M d, Y');
        $filename = "{$loan->borrower->name} - {$date} Schedule.xlsx";

        return Excel::download(new SchedExport($loan), $filename);
    }

    public function exportRegularSched()
    {
        $request = request();
        $year = $request->input('year', Carbon::now()->year);
        $officeFilter = $request->input('office', 'ALL');
        $employeeType = $request->input('employee_type');
        $scheduleMonth = $request->input('schedule_month');
        $schedulePeriod = $request->input('schedule_period');
        $employeeIdsString = $request->input('employee_ids');

        // 1. Process and Clean Comma-Separated IDs if provided
        $validEmployeeIds = null; 

        if (!empty($employeeIdsString)) {
            // Convert string "ID-1, ID-2, ID-3" into an array ['ID-1', 'ID-2', 'ID-3']
            $inputIds = array_map('trim', explode(',', $employeeIdsString));
            $inputIds = array_filter($inputIds); // Remove empty values

            // 2. Query loans matching these IDs to verify their compliance
            // Assuming 'payment_preference' is determined by the $schedulePeriod context (e.g., 'half_month' or 'whole_month')
            $expectedPreference = ($employeeType === 'Permanent') ? 'half_month' : 'whole_month'; // Adjust this mapping based on your business logic

            $matchingLoans = Loan::whereHas('borrower', function($q) use ($inputIds) {
                    $q->whereIn('employee_id', $inputIds);
                })
                ->where('employee_type', $employeeType)
                ->where('payment_preference', $expectedPreference)
                ->with('borrower')
                ->get();

            // 3. Strict Check: If the count of valid loans doesn't match the unique input count,
            // it means at least one ID broke the condition.
            $foundEmployeeIds = $matchingLoans->pluck('borrower.employee_id')->toArray();
            
            // Pass only the pristine, fully-compliant array of IDs down to the export
            $validEmployeeIds = $foundEmployeeIds;

            // OPTIONAL: If you want to abort entirely if ONE ID breaks the condition instead of just filtering:
            /*
            if (count($inputIds) !== count($foundEmployeeIds)) {
                return back()->withErrors(['employee_ids' => 'One or more provided Employee IDs do not match the specified Employee Type or Payment Preference constraints.']);
            }
            */
        }

        $scheduleDate = $scheduleMonth;
        
        if ($employeeType !== 'Permanent') {
            if ($schedulePeriod === 'end') {
                $scheduleDate = Carbon::parse($scheduleMonth . '-01')->endOfMonth()->format('Y-m-d');
            } else {
                $scheduleDate = $scheduleMonth . '-15';
            }
        }

        $filename = "regular_loans_sched_{$scheduleDate}.xlsx";

        return Excel::download(
            // Pass $validEmployeeIds instead of raw string input to ensure data integrity
            new RegularLoanSchedExport($year, $officeFilter, $employeeType, $scheduleMonth, $scheduleDate, $validEmployeeIds),
            $filename
        );
    }
}