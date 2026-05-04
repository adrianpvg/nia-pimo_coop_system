<?php

namespace App\Exports;

use App\Models\Loan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Carbon\Carbon;

class SchedExport implements FromArray, WithStyles, WithColumnWidths
{
    protected $loan;

    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
    }

    public function array(): array
    {
        $paymentStart = Carbon::parse($this->loan->payment_start);
        $paymentEnd = Carbon::parse($this->loan->payment_end);
        
        $payingPeriod = $paymentStart->format('M d, Y') . ' - ' . $paymentEnd->format('M d, Y');

        $rows = [
            ['NIA REGION 1 MULTIPURPOSE COOPERATIVE', '', '', '', '', '', '', '', ''],
            ['BAYAOAS, URDANETA CITY, PANGASINAN', '', '', '', '', '', '', '', ''],
            ['COMPUTATION SHEET (' . $this->loan->type . ')', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', ''],
            // Shifted labels to column A
            ['NAME OF APPLICANT', '', $this->loan->borrower->name, '', '', '', '', '', ''],
            ['DATE OF LOAN GRANTED', '', Carbon::parse($this->loan->date_of_application)->format('F d, Y'), '', '', '', '', '', ''],
            ['PAYING PERIOD :', '', $payingPeriod, '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', ''],
            ['Amount of Loan   :', '', $this->loan->amount_granted, '', '', '', '', '', ''],
            ['TERM:', '', $this->loan->no_of_months . ' mos', '', '', '', '', '', ''], 
            ['Installment Schedule :', '', '', '', '', '', '', '', ''],
            ['SEQ. NO.', 'PERIOD COVERED', 'PRINCIPAL', 'INTEREST', 'TOTAL', '', 'PAYMENTS', '', ''],
            ['', '', '', '', '(Principal + Interest)', 'BALANCE', 'BALANCE', 'DATE', 'AMOUNT'],
            ['', 'Principal', '', '', '', $this->loan->amount_granted, '', '', '']
        ];

        $totalPrin = 0;
        $totalInt = 0;
        $totalSum = 0;

        foreach ($this->loan->schedules as $index => $sched) {
            $period = Carbon::parse($sched->period_start)->format('M d') . '-' . Carbon::parse($sched->period_end)->format('d, Y');
            $balance = ($index + 1) % 2 == 0 ? $sched->balance_after : '';
            
            $rows[] = [
                $index + 1,
                $period,
                $sched->principal_due,
                $sched->interest_due,
                $sched->total_due,
                $balance,
                '', '', ''
            ];

            // Accumulate totals
            $totalPrin += $sched->principal_due;
            $totalInt  += $sched->interest_due;
            $totalSum  += $sched->total_due;
        }

        // The final TOTAL row appended after the loop
        $rows[] = ['TOTAL', '', $totalPrin, $totalInt, $totalSum, '', '', '', ''];

        // --- SIGNATURE BLOCK (Shifted to start at Column B) ---
        // Array index 0 represents Column A, so we put an empty string '' there.
        $rows[] = ['', 'Prepared by:', '', '', 'Approved:', '', '', '', '']; 
        $rows[] = ['', '', '', '', '', '', '', '', '']; // Space for physical signature
        $rows[] = ['', 'ARNEL S. ABALOS', '', '', 'FRANCIS DAVE T. RAMIREZ', '', '', '', ''];
        $rows[] = ['', 'Member-Credit Committee', '', '', 'Chair-Person Committee', '', '', '', ''];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 3.22,
            'B' => 19.65,
            'C' => 12.22,
            'D' => 10.94,
            'E' => 11.80,
            'F' => 12.51,
            'G' => 7.94,
            'H' => 7.36,
            'I' => 8.07,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Calculate dynamic row locations
        $lastRow = count($this->loan->schedules) + 15; // The row number of the "TOTAL" row
        $sigRowStart = $lastRow + 1; // "Prepared by:" / "Approved:"
        $nameRow = $lastRow + 3; // "ARNEL S. ABALOS" / "FRANCIS DAVE..."
        $titleRow = $lastRow + 4; // Titles

        // --- PAGE SETUP & MARGINS ---
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4); // Forces A4 size
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageMargins()->setTop(0.53);
        $sheet->getPageMargins()->setHeader(0.27);
        $sheet->getPageMargins()->setBottom(0.3);
        $sheet->getPageMargins()->setLeft(0);
        $sheet->getPageMargins()->setRight(0);

        // --- ROW HEIGHTS ---
        $sheet->getRowDimension(3)->setRowHeight(30.95);
        $sheet->getRowDimension(4)->setRowHeight(11.10);
        $sheet->getRowDimension(8)->setRowHeight(3.75);

        // --- GLOBAL DEFAULT FONT ---
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);

        // --- MERGES ---
        // Header merged across A to I
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        // Applicant Details Merges (Name, Date Granted, Paying Period)
        $sheet->mergeCells('A5:B5'); 
        $sheet->mergeCells('C5:E5'); // Values merged specifically across 3 columns (C, D, E)
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('C6:E6'); 
        $sheet->mergeCells('A7:B7');
        $sheet->mergeCells('C7:E7'); 
        
        // Amount and Term Merges (Labels merged A:B, Values remain exactly 1 column wide on C)
        $sheet->mergeCells('A9:B9');
        $sheet->mergeCells('A10:B10');

        $sheet->mergeCells('A11:I11'); // Installment Schedule Label

        // Table Header Merges
        $sheet->mergeCells('A12:A13'); // SEQ. NO.
        $sheet->mergeCells('B12:B13'); // PERIOD COVERED
        $sheet->mergeCells('C12:C13'); // PRINCIPAL
        $sheet->mergeCells('D12:D13'); // INTEREST
        $sheet->mergeCells('G12:I12'); // PAYMENTS across Balance, Date, Amount

        // Total Row Merge (Col A and B)
        $sheet->mergeCells("A{$lastRow}:B{$lastRow}");

        // --- SIGNATURE BLOCKS MERGES (Shifted +1 letter to start at B) ---
        $sheet->mergeCells("B{$sigRowStart}:D{$sigRowStart}"); // Prepared By
        $sheet->mergeCells("E{$sigRowStart}:I{$sigRowStart}"); // Approved By
        
        $sheet->mergeCells("B{$nameRow}:D{$nameRow}"); // Arnel
        $sheet->mergeCells("E{$nameRow}:I{$nameRow}"); // Francis
        
        $sheet->mergeCells("B{$titleRow}:D{$titleRow}"); // Title 1
        $sheet->mergeCells("E{$titleRow}:I{$titleRow}"); // Title 2

        // --- STYLES ---
        $styles = [
            // Title Headers (Centered, Bold)
            'A1:A3' => [
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            
            // Labels Font Size 10
            'A5:A11' => [
                'font' => ['name' => 'Arial', 'size' => 10],
            ],

            // Applicant Details Values (Centered, Bold, Bottom Border)
            'C5:E7' => [
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    // Applying bottom border individually per row inside the range
                    'horizontal' => ['borderStyle' => Border::BORDER_THIN],
                    'bottom' => ['borderStyle' => Border::BORDER_THIN] 
                ]
            ],
            
            // Amount of Loan value formatting (Bordered, 1 Col, number format)
            'C9' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'numberFormat' => ['formatCode' => '#,##0.00'],
                'font' => ['name' => 'Arial', 'size' => 11]
            ],
            // Term value formatting (Bordered, 1 Col, Centered)
            'C10' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'font' => ['name' => 'Arial', 'size' => 11]
            ],

            // Main Table Headers (Row 12)
            'A12:I12' => [
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER, 
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true // Wrap text for SEQ. NO. and others
                ]
            ],
            
            // Sub-Headers (Row 13)
            'A13:I13' => [
                'font' => ['name' => 'Arial', 'size' => 8],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            
            // Specific Font for Column A (SEQ NO. down)
            "A14:A{$lastRow}" => [
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            
            // Specific Font for Column B (PERIOD COVERED down)
            "B14:B{$lastRow}" => [
                'font' => ['name' => 'Cambria', 'size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ],
            
            // Schedule Table Grid Borders (from headers strictly to total row)
            "A12:I{$lastRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ],
            
            // Total Row specifically 
            "A{$lastRow}:B{$lastRow}" => [
                'font' => ['bold' => true, 'italic' => true, 'name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            "C{$lastRow}:I{$lastRow}" => [
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ],

            // --- SIGNATURE STYLES (Shifted to B:I) ---
            "B{$sigRowStart}:I{$sigRowStart}" => [
                'font' => ['name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ],
            // Removed 'bold' => true to make the names regular weight
            "B{$nameRow}:I{$nameRow}" => [
                'font' => ['name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            "B{$titleRow}:I{$titleRow}" => [
                'font' => ['name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];

        // Format numerical columns (C through I) down to the bottom of the table
        $sheet->getStyle("C14:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        return $styles;
    }
}