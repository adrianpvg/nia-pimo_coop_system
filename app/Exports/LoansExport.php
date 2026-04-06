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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LoansExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithDrawings
{
    protected $type;
    protected $year;
    protected $office;
    
    // Arrays to track row numbers for dynamic styling
    protected $titleRows = [];
    protected $headingRows = [];
    protected $totalRows = [];

    public function __construct($type, $year, $office)
    {
        $this->type = $type;
        $this->year = $year;
        $this->office = $office;
    }

    /**
     * Add Left and Right Header Images
     */
    public function drawings()
    {
        $drawings = [];

        // 1. LEFT HEADER IMAGE
        $drawingLeft = new Drawing();
        $drawingLeft->setName('NIA COOP Left Header');
        $drawingLeft->setDescription('NIA COOP Left Header');
        $drawingLeft->setPath(public_path('images/left-header.png')); 
        $drawingLeft->setHeight(100); 
        $drawingLeft->setCoordinates('A1'); // Anchor to top left
        $drawingLeft->setOffsetX(16); // Slight padding from the left edge
        $drawings[] = $drawingLeft;

        // 2. RIGHT HEADER IMAGE
        $drawingRight = new Drawing();
        $drawingRight->setName('NIA COOP Right Header');
        $drawingRight->setDescription('NIA COOP Right Header');
        $drawingRight->setPath(public_path('images/right-header.png')); 
        $drawingRight->setHeight(60); 
        // Anchor to the right side. If the image spills past column N, 
        // change this to 'K1' or 'M1' depending on the exact width of your image.
        $drawingRight->setCoordinates('N1'); 
        $drawingRight->setOffsetY(11);
        $drawingRight->setOffsetX(14);
        $drawings[] = $drawingRight;

        return $drawings;
    }

    public function array(): array
    {
        $rows = [
            [''], [''], [''], [''], [''] 
        ];
        $currentRow = 6; 
        
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

        $query = Loan::with('borrower.office')->whereYear('date_of_application', $this->year);
        
        if ($this->office !== 'ALL') {
            $query->whereHas('borrower.office', function($q) {
                $q->where('name', $this->office);
            });
        }

        if ($this->type !== 'ALL') {
            $query->where('type', $this->type);
            $loans = $query->get();
            
            $officeText = $this->office !== 'ALL' ? " - " . $this->office : "";
            $rows[] = [$this->type . ' (' . $this->year . $officeText . ')'];
            $this->titleRows[] = $currentRow++;
            
            $rows[] = $headings;
            $this->headingRows[] = $currentRow++;

            $totalNetProceeds = 0;
            
            foreach ($loans as $loan) {
                $rows[] = $this->mapLoan($loan);
                $totalNetProceeds += (float) $loan->net_proceeds;
                $currentRow++;
            }
            
            $rows[] = [
                '', '', '', '', '', '', '', '', '', 'TOTAL NET:', (float) $totalNetProceeds, '', '', ''
            ];
            $this->totalRows[] = $currentRow++;
            
        } else {
            $loans = $query->get();
            $groupedLoans = $loans->groupBy('type');
            
            // Order Arrangement
            $sortOrder = ['REGULAR SALARY LOAN', 'SPECIAL LOAN', 'CASAB'];
            $orderedGroups = [];

            foreach ($sortOrder as $type) {
                if ($groupedLoans->has($type)) {
                    $orderedGroups[$type] = $groupedLoans->get($type);
                    $groupedLoans->forget($type);
                }
            }
            
            foreach ($groupedLoans as $type => $group) {
                $orderedGroups[$type] = $group;
            }
            
            foreach ($orderedGroups as $type => $group) {
                $officeText = $this->office !== 'ALL' ? " - " . $this->office : "";
                
                $rows[] = [$type . ' (' . $this->year . $officeText . ')'];
                $this->titleRows[] = $currentRow++;
                
                $rows[] = $headings;
                $this->headingRows[] = $currentRow++;
                
                $totalNetProceeds = 0;
                
                foreach ($group as $loan) {
                    $rows[] = $this->mapLoan($loan);
                    $totalNetProceeds += (float) $loan->net_proceeds;
                    $currentRow++;
                }
                
                $rows[] = [
                    '', '', '', '', '', '', '', '', '', 'TOTAL NET:', (float) $totalNetProceeds, '', '', ''
                ];
                $this->totalRows[] = $currentRow++;
                
                $rows[] = ['']; 
                $currentRow++;
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
            (float) $loan->amount_granted,
            (float) $loan->service_fee,
            (float) $loan->interest_rate, 
            (float) $loan->surcharge,
            (float) $loan->net_proceeds,
            $loan->no_of_months,
            $loan->payment_start,
            $loan->payment_end,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $sheet->getColumnDimension('A')->setAutoSize(false);
                $sheet->getColumnDimension('A')->setWidth(12); 
                
                $sheet->getColumnDimension('B')->setAutoSize(false);
                $sheet->getColumnDimension('B')->setWidth(15);
                
                $sheet->getColumnDimension('L')->setAutoSize(false);
                $sheet->getColumnDimension('L')->setWidth(15);

                $sheet->getColumnDimension('F')->setAutoSize(false);
                $sheet->getColumnDimension('F')->setWidth(21);

                $sheet->getColumnDimension('G')->setAutoSize(false);
                $sheet->getColumnDimension('G')->setWidth(16);

                $sheet->getColumnDimension('H')->setAutoSize(false);
                $sheet->getColumnDimension('H')->setWidth(12);
                
                $sheet->getColumnDimension('I')->setAutoSize(false);
                $sheet->getColumnDimension('I')->setWidth(12);

                $sheet->getColumnDimension('J')->setAutoSize(false);
                $sheet->getColumnDimension('J')->setWidth(12);

                $sheet->getColumnDimension('K')->setAutoSize(false);
                $sheet->getColumnDimension('K')->setWidth(16);

                $sheet->getColumnDimension('M')->setAutoSize(false);
                $sheet->getColumnDimension('M')->setWidth(13);

                $sheet->getColumnDimension('N')->setAutoSize(false);
                $sheet->getColumnDimension('N')->setWidth(13);

                $centerAlign = [
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ];
                $sheet->getStyle('A:A')->applyFromArray($centerAlign);
                $sheet->getStyle('B:B')->applyFromArray($centerAlign);
                $sheet->getStyle('L:L')->applyFromArray($centerAlign);
                $sheet->getStyle('M:M')->applyFromArray($centerAlign);
                $sheet->getStyle('N:N')->applyFromArray($centerAlign);
                

                // Title Rows
                foreach ($this->titleRows as $row) {
                    $sheet->mergeCells("A{$row}:N{$row}");
                    $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '28A745']
                        ]
                    ]);
                }

                // Column Headings (Bold & Centered)
                foreach ($this->headingRows as $row) {
                    $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ]
                    ]);
                }

                // Total Rows
                foreach ($this->totalRows as $row) {
                    $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                    ]);
                    $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}