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
        $request->validate([
            'type' => 'required|string',
            'borrower_name' => 'required|string',
            'office_name' => 'required|string',
            'date_of_application' => 'required|date',
            'payment_start' => 'required|date',
            'payment_end' => 'required|date|after_or_equal:payment_start',
            'no_of_months' => 'required|integer|min:1|max:120',
            'amount_granted' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
        ]);

        $date = Carbon::parse($request->date_of_application);
        $year = $date->format('y'); 
        $month = $date->format('m'); 
        
        // --- CORRECTED LOGIC START ---
        // Instead of count(), find the loan with the highest control number for this specific month
        $lastLoan = Loan::whereYear('date_of_application', $date->year)
                        ->whereMonth('date_of_application', $date->month)
                        ->orderBy('control_number', 'desc')
                        ->first();

        if ($lastLoan) {
            // Extract the last 3 digits (the sequence) from the existing control number and increment it
            $lastSequence = (int) substr($lastLoan->control_number, -3);
            $nextSequence = $lastSequence + 1;
        } else {
            // If no loans exist for this month yet, start at 1
            $nextSequence = 1;
        }
        
        $control_number = $year . '-' . $month . '-' . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
        // --- CORRECTED LOGIC END ---

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
            'interest_rate' => $request->interest_rate ?? 0, 
            'surcharge' => $request->surcharge ?? 0,
            'net_proceeds' => $request->net_proceeds,
            'payment_start' => $request->payment_start,
            'payment_end' => $request->payment_end,
            'no_of_months' => $request->no_of_months, 
        ]);
        return redirect()->route('finance.show', $loan->id)
                         ->with('success', 'Application Added! Control No: ' . $control_number);
        //return back()->with('success', 'Application Added! Control No: ' . $control_number);
    }

    public function show($id)
    {
        $loan = Loan::with(['borrower.office', 'payments'])->findOrFail($id);
        return view('finance.show', compact('loan'));
    }

    public function updateActualMonths(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        // Dynamically determine the max limit for backend security
        $maxTerm = ($loan->type === 'REGULAR SALARY LOAN') ? 32 : 36;

        $request->validate([
            'actual_months' => 'required|integer|min:1|max:' . $maxTerm
        ]);

        $loan->update([
            'actual_months' => $request->actual_months
        ]);

        return back()->with('success', 'Amortization term successfully set to ' . $request->actual_months . ' months.');
    }

    // NEW: Delete Entire Loan Application Logic
    public function destroyLoan($id)
    {
        $loan = Loan::findOrFail($id);
        
        // Delete all associated payments first so the database doesn't crash from foreign key constraints
        $loan->payments()->delete(); 
        
        // Then delete the loan
        $loan->delete();

        // Redirect back to the index view, carrying over the success message
        return redirect()->route('finance.index')->with('success', 'Loan application and all related payment records have been deleted.');
    }

    public function addPayment(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'or_number' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        Payment::create([
            'loan_id' => $request->loan_id,
            'amount_paid' => $request->amount_paid,
            'interest' => $request->interest ?? 0,
            'or_number' => $request->or_number,
            'payment_date' => $request->payment_date,
        ]);
        return back()->with('success', 'Payment Recorded!');
    }

    public function deletePayment($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();
        
        return back()->with('success', 'Payment successfully deleted.');
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
}