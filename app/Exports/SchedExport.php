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

    private function formatAbbreviatedDate($dateStr) {
        $obj = Carbon::parse($dateStr);
        $month = $obj->format('F');
        
        if ($month === 'September') return 'Sept. ' . $obj->format('d, Y');
        if (strlen($month) <= 5) return $obj->format('F d, Y');
        
        return $obj->format('M. d, Y');
    }
    
    private function formatAbbreviatedPeriod($startStr, $endStr) {
        $startObj = Carbon::parse($startStr);
        $endObj = Carbon::parse($endStr);
        
        $sMonth = $startObj->format('F');
        $eMonth = $endObj->format('F');

        $sFormat = strlen($sMonth) <= 5 ? $startObj->format('F d') : $startObj->format('M. d');
        if ($sMonth === 'September') $sFormat = 'Sept. ' . $startObj->format('d');

        $eFormat = strlen($eMonth) <= 5 ? $endObj->format('F d, Y') : $endObj->format('M. d, Y');
        if ($eMonth === 'September') $eFormat = 'Sept. ' . $endObj->format('d, Y');

        return "$sFormat-$eFormat";
    }

    public function array(): array
    {
        $paymentStart = Carbon::parse($this->loan->payment_start);
        $paymentEnd = Carbon::parse($this->loan->payment_end);
        
        $sMonth = $paymentStart->format('F');
        $eMonth = $paymentEnd->format('F');

        $sFormat = strlen($sMonth) <= 5 ? $paymentStart->format('F d, Y') : $paymentStart->format('M. d, Y');
        if ($sMonth === 'September') $sFormat = 'Sept. ' . $paymentStart->format('d, Y');

        $eFormat = strlen($eMonth) <= 5 ? $paymentEnd->format('F d, Y') : $paymentEnd->format('M. d, Y');
        if ($eMonth === 'September') $eFormat = 'Sept. ' . $paymentEnd->format('d, Y');

        $payingPeriod = $sFormat . ' - ' . $eFormat;

        $rows = [
            ['NIA REGION 1 MULTIPURPOSE COOPERATIVE', '', '', '', '', '', '', '', ''],
            ['BAYAOAS, URDANETA CITY, PANGASINAN', '', '', '', '', '', '', '', ''],
            ['COMPUTATION SHEET (' . $this->loan->type . ')', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', ''],
            ['NAME OF APPLICANT', '', $this->loan->borrower->name, '', '', '', '', '', ''],
            ['DATE OF LOAN GRANTED', '', $this->formatAbbreviatedDate($this->loan->date_of_application), '', '', '', '', '', ''],
            ['PAYING PERIOD :', '', $payingPeriod, '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', ''],
            ['Amount of Loan   :', '', $this->loan->amount_granted, '', '', '', '', '', ''],
            ['TERM:', '', fmod($this->loan->no_of_months, 1) !== 0.00 ? number_format($this->loan->no_of_months, 2) . ' mos' : round($this->loan->no_of_months) . ' mos', '', '', '', '', '', ''], 
            ['Installment Schedule :', '', '', '', '', '', '', '', ''],
            ['SEQ. NO.', 'PERIOD COVERED', 'PRINCIPAL', 'INTEREST', 'TOTAL', '', 'PAYMENTS', '', ''],
            ['', '', '', '', '(Principal + Interest)', 'BALANCE', 'BALANCE', 'DATE', 'AMOUNT'],
        ];

        $totalPrin = 0;
        $totalInt = 0;
        $totalSum = 0;

        $base_principal = round($this->loan->amount_granted, 2);
        if (!is_null($this->loan->actual_months) && $this->loan->schedules->isNotEmpty()) {
            $base_interest = round($this->loan->schedules->sum('interest_due'), 2);
        } else {
            if ($this->loan->type === 'CASAB') {
                $days = Carbon::parse($this->loan->payment_start)->diffInDays(Carbon::parse($this->loan->payment_end));
                $base_interest = round($base_principal * ($days / 30) * ($this->loan->base_interest / 100), 2);
            } else {
                $base_interest = round($base_principal * ($this->loan->interest_rate / 100), 2);
            }
        }
        $total_liability = round($base_principal + $base_interest, 2);

        // --- SPECIAL LOAN LOGIC ---
        if ($this->loan->type === 'SPECIAL LOAN') {
            $rows[] = ['', 'Loan Granted', '', '', '', $base_principal, $base_principal, '', ''];

            $runPrinBal = $base_principal; // Special balance tracks principal
            $remPrin = $base_principal;
            $remInt = $base_interest;

            foreach ($this->loan->payments as $index => $pay) {
                $payTotal = $pay->amount_paid + $pay->interest;
                $runPrinBal -= $pay->amount_paid;
                $remPrin -= $pay->amount_paid;
                $remInt -= $pay->interest;

                $paymentDateStr = $this->formatAbbreviatedDate($pay->payment_date);

                $rows[] = [
                    $index + 1,
                    $paymentDateStr,
                    $pay->amount_paid,
                    $pay->interest,
                    $payTotal,
                    max(0, $runPrinBal),
                    max(0, $runPrinBal),
                    $paymentDateStr, 
                    $payTotal
                ];

                $totalPrin += $pay->amount_paid;
                $totalInt  += $pay->interest;
                $totalSum  += $payTotal;
            }

            if ($remPrin > 0 || $remInt > 0) {
                $remPrin = max(0, $remPrin);
                $remInt = max(0, $remInt);

                $rows[] = [
                    count($this->loan->payments) + 1,
                    $this->formatAbbreviatedDate($this->loan->payment_end) . ' (Due)',
                    $remPrin,
                    $remInt,
                    $remPrin + $remInt,
                    0, 
                    '', '', '' 
                ];

                $totalPrin += $remPrin;
                $totalInt  += $remInt;
                $totalSum  += ($remPrin + $remInt);
            }

        } else {
            // --- REGULAR / CASAB LOAN LOGIC ---
            $rows[] = ['', 'Principal', '', '', '', $this->loan->amount_granted, $this->loan->amount_granted, '', ''];

            $actualPrinBal = round($this->loan->amount_granted, 2);

            foreach ($this->loan->schedules as $index => $sched) {
                $period = $this->formatAbbreviatedPeriod($sched->period_start, $sched->period_end);
                
                $isWholeMonth = $this->loan->payment_preference === 'whole_month';
                $isCasab = $this->loan->type === 'CASAB';
                
                // Show balance only if Casab, Half_month, or the 2nd Row (EOM) of Whole_month
                $showBalance = $isCasab || !$isWholeMonth || (($index + 1) % 2 == 0);
                $balanceDisplay = $showBalance ? max(0, $sched->balance_after) : '';
                
                $paymentRecord = null;
                if ($isCasab || !$isWholeMonth) {
                    $paymentRecord = $this->loan->payments->where('period_covered', $sched->period_end)->first();
                } else {
                    $monthKey = Carbon::parse($sched->period_end)->format('Y-m');
                    $paymentRecord = $this->loan->payments->where('period_covered', $monthKey)->first();
                    if (($index + 1) % 2 != 0) {
                        $paymentRecord = null; 
                    }
                }

                if ($paymentRecord) {
                    $payAmt = $paymentRecord->amount_paid + $paymentRecord->interest;
                    $actualPrinBal -= $paymentRecord->amount_paid;
                    $payDate = $this->formatAbbreviatedDate($paymentRecord->payment_date);
                    
                    // Payments balance = whatever balance is designated for the row
                    $payBalDisplay = $balanceDisplay !== '' ? $balanceDisplay : max(0, $sched->balance_after);
                } else {
                    $payAmt = '';
                    $payDate = '';
                    $payBalDisplay = '';
                }

                $rows[] = [
                    $index + 1,
                    $period,
                    $sched->principal_due,
                    $sched->interest_due,
                    $sched->total_due,
                    $balanceDisplay,
                    $payBalDisplay, 
                    $payDate, 
                    $payAmt 
                ];

                $totalPrin += $sched->principal_due;
                $totalInt  += $sched->interest_due;
                $totalSum  += $sched->total_due;
            }
        }

        $rows[] = ['TOTAL', '', $totalPrin, $totalInt, $totalSum, '', '', '', ''];

        $rows[] = ['', 'Prepared by:', '', '', 'Approved:', '', '', '', '']; 
        $rows[] = ['', '', '', '', '', '', '', '', '']; 
        $rows[] = ['', 'ARNEL S. ABALOS', '', '', 'FRANCIS DAVE T. RAMIREZ', '', '', '', ''];
        $rows[] = ['', 'Member-Credit Committee', '', '', 'Chair-Person Committee', '', '', '', ''];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 3.22,
            'B' => 19.65,
            'C' => 10.70,
            'D' => 9.40,
            'E' => 10.90,
            'F' => 10.50,
            'G' => 10.50,
            'H' => 11.50, 
            'I' => 9.20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->array()) - 4; 
        $sigRowStart = $lastRow + 1; 
        $nameRow = $sigRowStart + 2; 
        $titleRow = $nameRow + 1; 

        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4); 
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageMargins()->setTop(0.53);
        $sheet->getPageMargins()->setHeader(0.27);
        $sheet->getPageMargins()->setBottom(0.3);
        $sheet->getPageMargins()->setLeft(0);
        $sheet->getPageMargins()->setRight(0);

        $sheet->getRowDimension(3)->setRowHeight(30.95);
        $sheet->getRowDimension(4)->setRowHeight(11.10);
        $sheet->getRowDimension(8)->setRowHeight(3.75);

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        $sheet->mergeCells('A5:B5'); 
        $sheet->mergeCells('C5:E5'); 
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('C6:E6'); 
        $sheet->mergeCells('A7:B7');
        $sheet->mergeCells('C7:E7'); 
        
        $sheet->mergeCells('A9:B9');
        $sheet->mergeCells('A10:B10');

        $sheet->mergeCells('A11:I11');
        $sheet->mergeCells('A12:A13'); 
        $sheet->mergeCells('B12:B13'); 
        $sheet->mergeCells('C12:C13'); 
        $sheet->mergeCells('D12:D13'); 
        $sheet->mergeCells('G12:I12'); 
        $sheet->mergeCells("A{$lastRow}:B{$lastRow}");

        $sheet->mergeCells("B{$sigRowStart}:D{$sigRowStart}"); 
        $sheet->mergeCells("E{$sigRowStart}:I{$sigRowStart}"); 
        
        $sheet->mergeCells("B{$nameRow}:D{$nameRow}"); 
        $sheet->mergeCells("E{$nameRow}:I{$nameRow}"); 
        
        $sheet->mergeCells("B{$titleRow}:D{$titleRow}"); 
        $sheet->mergeCells("E{$titleRow}:I{$titleRow}"); 

        $styles = [
            'A1:A3' => [
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            'A5:A11' => ['font' => ['name' => 'Arial', 'size' => 10]],
            'C5:E7' => [
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'horizontal' => ['borderStyle' => Border::BORDER_THIN],
                    'bottom' => ['borderStyle' => Border::BORDER_THIN] 
                ]
            ],
            'C9' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'numberFormat' => ['formatCode' => '#,##0.00'],
                'font' => ['name' => 'Arial', 'size' => 11]
            ],
            'C10' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'font' => ['name' => 'Arial', 'size' => 11]
            ],
            'A12:I12' => [
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]
            ],
            'A13:I13' => [
                'font' => ['name' => 'Arial', 'size' => 8],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            "A14:A{$lastRow}" => [
                'font' => ['name' => 'Arial', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            "B14:B{$lastRow}" => [
                'font' => ['name' => 'Cambria', 'size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ],
            "H14:H{$lastRow}" => [ 
                'font' => ['name' => 'Cambria', 'size' => 8],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            "A12:I{$lastRow}" => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ],
            "A{$lastRow}:B{$lastRow}" => [
                'font' => ['bold' => true, 'italic' => true, 'name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            "C{$lastRow}:I{$lastRow}" => [
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ],
            "B{$sigRowStart}:I{$sigRowStart}" => [
                'font' => ['name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ],
            "B{$nameRow}:I{$titleRow}" => [
                'font' => ['name' => 'Arial', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
        
        $sheet->getStyle("C14:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00_-');

        return $styles;
    }
}