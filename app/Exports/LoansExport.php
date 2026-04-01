<?php
// namespace App\Exports;

// use App\Models\Loan;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMapping;

// class LoansExport implements FromCollection, WithHeadings, WithMapping
// {
//     protected $type;

//     public function __construct($type)
//     {
//         $this->type = $type;
//     }

//     public function collection()
//     {
//         return Loan::where('type', $this->type)->with('borrower.office')->get();
//     }

//     public function map($loan): array
//     {
//         $balance = $loan->amount_granted - $loan->payments->sum('amount_paid');
//         return [
//             $loan->control_number,         
//             $loan->date_of_application,     
//             $loan->borrower->name,
//             $loan->borrower->co_maker,
//             $loan->borrower->office->name,
//             $loan->amount_granted,
//             $loan->net_proceeds,         
//             $balance > 0 ? $balance : 'FULL PAYMENT',
//         ];
//     }

//     public function headings(): array
//     {
//         return ['Control No.', 'Date Applied', 'Name', 'Co-Maker', 'Office', 'Amount', 'Net Proceeds', 'Balance'];
//     }
// }
// namespace App\Exports;

// use App\Models\Loan;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMapping;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithStyles;
// use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// class LoansExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
// {
//     protected $type;

//     public function __construct($type)
//     {
//         $this->type = $type;
//     }

//     public function collection()
//     {
//         // 1. Start the query and eager load relationships to prevent N+1 issues
//         $query = Loan::with(['borrower.office', 'payments']);

//         // 2. Filter ONLY if a specific type is requested
//         if ($this->type !== 'ALL') {
//             $query->where('type', $this->type);
//         }

//         return $query->get();
//     }

//     public function map($loan): array
//     {
//         // Calculate the balance dynamically
//         $balance = $loan->amount_granted - $loan->payments->sum('amount_paid');
        
//         return [
//             $loan->control_number,         
//             $loan->date_of_application,     
//             $loan->borrower->name ?? 'N/A',
//             $loan->borrower->co_maker ?? 'N/A',
//             $loan->borrower->office->name ?? 'N/A',
//             $loan->amount_granted,
//             $loan->net_proceeds,         
//             $balance > 0 ? $balance : 'FULL PAYMENT',
//         ];
//     }

//     public function headings(): array
//     {
//         return [
//             'Control No.', 
//             'Date Applied', 
//             'Name', 
//             'Co-Maker', 
//             'Office', 
//             'Amount', 
//             'Net Proceeds', 
//             'Balance'
//         ];
//     }

//     // Apply basic styling to the Excel sheet
//     public function styles(Worksheet $sheet)
//     {
//         return [
//             // Make the first row (headings) bold
//             1 => ['font' => ['bold' => true]],
//         ];
//     }
// }

namespace App\Exports;

use App\Models\Loan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LoansExport implements FromArray, ShouldAutoSize
{
    protected $type;
    protected $year;
    protected $office;

    public function __construct($type, $year, $office)
    {
        $this->type = $type;
        $this->year = $year;
        $this->office = $office;
    }

    public function array(): array
    {
        $rows = [];
        
        $headings = [
            'Control No.',
            'Application Date',
            'Office', 
            'Name',
            'Co-Maker',
            'Type',
            'Principal Amount',
            'Service Fee',
            'Interest',
            'Surcharge',
            'Net Proceeds',
            'No. of Months',
            'Payment Start',
            'Payment End'
        ];

        // Apply Year and Office Filters to the Query
        $query = Loan::with('borrower.office')->whereYear('date_of_application', $this->year);
        
        if ($this->office !== 'ALL') {
            $query->whereHas('borrower.office', function($q) {
                $q->where('name', $this->office);
            });
        }

        if ($this->type !== 'ALL') {
            $query->where('type', $this->type);
            $loans = $query->get();
            
            $rows[] = $headings;
            $totalNetProceeds = 0;
            
            foreach ($loans as $loan) {
                $rows[] = $this->mapLoan($loan);
                $totalNetProceeds += $loan->net_proceeds;
            }
            
            $rows[] = [
                '', '', '', '', '', '', '', '', '', 'TOTAL NET:', $totalNetProceeds, '', '', ''
            ];
            
        } else {
            $loans = $query->orderBy('type', 'asc')->get();
            $groupedLoans = $loans->groupBy('type');
            
            foreach ($groupedLoans as $type => $group) {
                $officeText = $this->office !== 'ALL' ? " - " . $this->office : "";
                $rows[] = [$type . ' LOANS (' . $this->year . $officeText . ')'];
                $rows[] = $headings;
                
                $totalNetProceeds = 0;
                
                foreach ($group as $loan) {
                    $rows[] = $this->mapLoan($loan);
                    $totalNetProceeds += $loan->net_proceeds;
                }
                
                $rows[] = [
                    '', '', '', '', '', '', '', '', '', 'TOTAL NET:', $totalNetProceeds, '', '', ''
                ];
                
                $rows[] = []; 
            }
        }

        return $rows;
    }

    private function mapLoan($loan): array
    {
        return [
            $loan->control_number,
            $loan->date_of_application,
            $loan->borrower->office->name ?? 'N/A',
            $loan->borrower->name ?? '',
            $loan->borrower->co_maker ?? 'N/A',
            $loan->type,
            $loan->amount_granted,
            $loan->service_fee,
            $loan->interest_rate, 
            $loan->surcharge,
            $loan->net_proceeds,
            $loan->no_of_months,
            $loan->payment_start,
            $loan->payment_end,
        ];
    }
}