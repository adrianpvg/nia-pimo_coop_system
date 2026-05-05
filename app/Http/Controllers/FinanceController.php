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
            $loan->balance = $loan->amount_granted - $loan->total_paid;
            return $loan;
        });

        $total_principal = $loans->sum('amount_granted');
        $summary = [
            'total_loans' => $loans->count(),
            'total_principal' => $total_principal,
            'total_net' => $loans->sum('net_proceeds'),
            'total_balance' => $loans->sum('balance'),
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
            'office_name' => 'required|string',
            'date_of_application' => 'required|date',
            'amount_granted' => 'required|numeric|min:0',
            'base_interest' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
        ];

        // --- NEW DYNAMIC CONSTRAINTS ---
        if ($request->type === 'CASAB') {
            $rules['no_of_months'] = 'required|integer|in:1';
            $rules['payment_start'] = 'required|date|after_or_equal:date_of_application';
            $rules['payment_end'] = 'required|date|same:payment_start'; // Must be paid in exactly 1 period
        } elseif ($request->type === 'SPECIAL LOAN') {
            $rules['no_of_months'] = 'required|integer|min:1|max:6';
            $rules['payment_start'] = 'required|date';
            $rules['payment_end'] = 'required|date|after_or_equal:payment_start';
        } else {
            // REGULAR SALARY LOAN
            $rules['no_of_months'] = 'required|integer|min:1|max:36';
            $rules['payment_start'] = 'required|date';
            $rules['payment_end'] = 'required|date|after_or_equal:payment_start';
        }

        $request->validate($rules);

        // EXTRA SECURITY: Ensure CASAB dates are exactly May 16 or Nov 16
        if ($request->type === 'CASAB') {
            $startMonthDay = \Carbon\Carbon::parse($request->payment_start)->format('m-d');
            if ($startMonthDay !== '05-16' && $startMonthDay !== '11-16') {
                return back()->withErrors(['type' => 'CASAB loans must be scheduled for the Mid-Year (May 16) or Year-End (Nov 16) bonus.'])->withInput();
            }
        }

        if ($request->type === 'CASAB') {
            $rules['no_of_months'] = 'required|integer|in:1';
            // Start and End must be the same for a one-time bonus deduction
            $request->merge([
                'payment_end' => $request->payment_start,
                'no_of_months' => 1
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
        $borrower = Borrower::firstOrCreate(
            ['name' => strtoupper($request->borrower_name)], 
            ['office_id' => $office->id]
        );

        if ($request->filled('co_maker')) {
            $borrower->update(['co_maker' => strtoupper($request->co_maker)]);
        }

        $loan = Loan::create([
            'borrower_id' => $borrower->id,
            'type' => strtoupper($request->type),
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
            'payment_preference' => ($request->type === 'CASAB') ? 'whole_month' : null 
        ]);

        
        return redirect()->route('finance.show', $loan->id)
                         ->with('success', 'Application Added! Control No: ' . $control_number);
    }

    public function show($id)
    {
        $loan = Loan::with(['borrower.office', 'payments', 'schedules'])->findOrFail($id);
        
        return view('finance.show', compact('loan'));
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

        // --- SPECIAL LOAN PAYMENT LOGIC ---
        // --- SPECIAL LOAN PAYMENT LOGIC ---
        if ($loan->type === 'SPECIAL LOAN') {
            $request->validate([
                'custom_amount_paid' => 'required|numeric|min:1'
            ]);

            $paymentAmount = $request->custom_amount_paid;
            
            // Calculate total expected
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

            // PROPORTIONAL SPLIT LOGIC
            $totalExpectedLiability = $totalPrincipal + $totalInterest;
            
            // What percentage of the total debt is principal, and what percentage is interest?
            $principalRatio = $totalPrincipal / $totalExpectedLiability;
            $interestRatio = $totalInterest / $totalExpectedLiability;

            // Split the incoming payment by those exact percentages
            $appliedToPrincipal = round($paymentAmount * $principalRatio, 2);
            $appliedToInterest = round($paymentAmount * $interestRatio, 2);

            // Because of rounding, there might be a 1-cent drift. We apply any remainder to principal.
            $actualTotal = $appliedToPrincipal + $appliedToInterest;
            if ($actualTotal !== $paymentAmount) {
                $difference = $paymentAmount - $actualTotal;
                $appliedToPrincipal += $difference;
            }

            // Final safety net: If we somehow overpay interest via drift, shift it to principal
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

        // --- REGULAR LOAN PAYMENT LOGIC ---
        if ($loan->payments()->where('period_covered', $request->period_covered)->exists()) {
            return back()->withErrors(['period_covered' => 'This period has already been paid.']);
        }

        $schedules = $loan->schedules->filter(function ($sched) use ($request) {
            if (strlen($request->period_covered) > 7) {
                return $sched->period_end === $request->period_covered;
            } else {
                return \Carbon\Carbon::parse($sched->period_end)->format('Y-m') === $request->period_covered;
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

        // ENFORCE MAX LIMITS BASED ON LOAN TYPE
        if ($loan->type === 'CASAB') {
            $rules = [ 'actual_months' => 'required|integer|in:1' ];
        } elseif ($loan->type === 'SPECIAL LOAN') {
            $rules = [ 'actual_months' => 'required|integer|min:1|max:6' ];
        } else {
            $rules = [
                'actual_months' => 'required|integer|min:1|max:36',
                'payment_preference' => 'required|in:half_month,whole_month'
            ];
        }

        $request->validate($rules);

        $loan->payments()->delete();

        $loan->update([
            'actual_months' => $request->actual_months,
            'payment_preference' => $request->payment_preference ?? 'whole_month', // Default to whole for special
        ]);

        $amortizationService->generateSchedule($loan);

        return back()->with('success', 'Amortization term updated. All previous payment records have been reset to match the new schedule.');
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

        return Excel::download(new \App\Exports\SchedExport($loan), $filename);
    }
}