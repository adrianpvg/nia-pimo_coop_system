<?php

namespace App\Exports;

use App\Models\Loan;
use App\Models\CommitteeSignatory;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Carbon\Carbon;

class SchedExport implements FromArray, WithStyles, WithColumnWidths, WithDrawings
{
    protected $loan;
    public $mergedCustomCells = [];

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

    public function drawings()
    {
        $drawingLeft = new Drawing();
        $drawingLeft->setName('Left Header');
        $drawingLeft->setDescription('NIA Cooperative Left Header');
        $drawingLeft->setPath(public_path('images/left-header.png'));
        $drawingLeft->setHeight(75);
        $drawingLeft->setCoordinates('A1');

        $drawingRight = new Drawing();
        $drawingRight->setName('Right Header');
        $drawingRight->setDescription('NIA Cooperative Right Header');
        $drawingRight->setPath(public_path('images/right-header.png'));
        $drawingRight->setHeight(45);
        $drawingRight->setCoordinates('I1'); 
        $drawingRight->setOffsetX(30); 
        $drawingRight->setOffsetY(8); 
        return [$drawingLeft, $drawingRight];
    }

    public function array(): array
    {
        $this->mergedCustomCells = [];
        
        $paymentStart = Carbon::parse($this->loan->payment_start);
        $paymentEnd = Carbon::parse($this->loan->payment_end);
        
        $sMonth = $paymentStart->format('F');
        $eMonth = $paymentEnd->format('F');

        $sFormat = strlen($sMonth) <= 5 ? $paymentStart->format('F d, Y') : $paymentStart->format('M. d, Y');
        if ($sMonth === 'September') $sFormat = 'Sept. ' . $paymentStart->format('d, Y');

        $eFormat = strlen($eMonth) <= 5 ? $paymentEnd->format('F d, Y') : $paymentEnd->format('M. d, Y');
        if ($eMonth === 'September') $eFormat = 'Sept. ' . $paymentEnd->format('d, Y');

        $payingPeriod = $sFormat . ' - ' . $eFormat;

        $payBalHeader = ($this->loan->type === 'REGULAR SALARY LOAN' && $this->loan->employee_type === 'Permanent') ? 'TOTAL' : 'BALANCE';

        $rows = [
            ['', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', ''],
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
            ['SEQ. NO.', 'PERIOD COVERED', 'PRINCIPAL', 'INTEREST', 'TOTAL', 'BALANCE', 'PAYMENTS', '', ''],
            ['', '', '', '', '(Principal + Interest)', '', $payBalHeader, 'DATE', 'AMOUNT'],
        ];

        $totalPrin = 0;
        $totalInt = 0;
        $totalSum = 0;
        $totalPaymentSum = 0;

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

        if ($this->loan->type === 'SPECIAL LOAN') {
            $rows[] = ['', 'PRINCIPAL', '', '', '', $base_principal, '', '', ''];

            $runPrinBal = $base_principal; 
            $remPrin = $base_principal;
            $remInt = $base_interest;

            foreach ($this->loan->payments as $index => $pay) {
                $payTotal = $pay->amount_paid + $pay->interest;
                $runPrinBal -= $pay->amount_paid;
                $remPrin -= $pay->amount_paid;
                $remInt -= $pay->interest;

                $paymentDateStr = $this->formatAbbreviatedDate($pay->payment_date);
                
                $formattedRunPrinBal = max(0, $runPrinBal);
                if ($formattedRunPrinBal == 0) $formattedRunPrinBal = '-';

                $rows[] = [
                    $index + 1,
                    $paymentDateStr,
                    $pay->amount_paid,
                    $pay->interest,
                    $payTotal,
                    $formattedRunPrinBal,
                    $formattedRunPrinBal,
                    $paymentDateStr, 
                    $payTotal
                ];

                $totalPrin += $pay->amount_paid;
                $totalInt  += $pay->interest;
                $totalSum  += $payTotal;
                $totalPaymentSum += $payTotal;
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
            
            $rows[] = ['', 'PRINCIPAL', '', '', '', $this->loan->amount_granted, '', '', ''];

            $actualPrinBal = round($this->loan->amount_granted, 2);
            $runningLiabilityBal = $total_liability;
            $startRow = 19; 

            foreach ($this->loan->schedules as $index => $sched) {
                $currentRow = $startRow + $index;
                $period = $this->formatAbbreviatedPeriod($sched->period_start, $sched->period_end);
                
                $isWholeMonth = $this->loan->payment_preference === 'whole_month';
                $isHalfMonth = $this->loan->payment_preference === 'half_month';
                $isCasab = $this->loan->type === 'CASAB';
                $isRegular = $this->loan->type === 'REGULAR SALARY LOAN';
                $isPermanent = $isRegular && $this->loan->employee_type === 'Permanent';
                
                $balanceDisplay = '';
                $payBalDisplay = '';
                $payDate = '';
                $payAmt = '';

                $runningLiabilityBal = round($runningLiabilityBal - $sched->total_due, 2);

                if ($isRegular && $isHalfMonth) {
                    // Strict calculation per payment period row without monthly cell merges
                    $bal = max(0, $runningLiabilityBal);
                    $balanceDisplay = ($bal == 0) ? '-' : $bal;
                    
                    if ($payBalHeader === 'TOTAL') {
                        $pBal = $sched->total_due;
                        $payBalDisplay = ($pBal == 0) ? '-' : $pBal;
                    } else {
                        $payBalDisplay = $balanceDisplay; 
                    }

                    $paymentRecord = $this->loan->payments->where('period_covered', $sched->period_end)->first();
                    if ($paymentRecord) {
                        $payAmt = $paymentRecord->amount_paid + $paymentRecord->interest;
                        $actualPrinBal -= $paymentRecord->amount_paid;
                        $payDate = $this->formatAbbreviatedDate($paymentRecord->payment_date);
                        $totalPaymentSum += $payAmt; 
                    }

                } elseif ($isPermanent) {
                    // Original grouping logic for permanent whole_month
                    if ($index % 2 != 0) {
                        $bal = max(0, $sched->balance_after);
                        $balanceDisplay = ($bal == 0) ? '-' : $bal;
                    }
                    
                    if ($index % 2 == 0) {
                        $nextSched = $this->loan->schedules[$index + 1] ?? null;
                        
                        $pBal = $sched->total_due + ($nextSched ? $nextSched->total_due : 0);
                        $payBalDisplay = ($pBal == 0) ? '-' : $pBal;
                        
                        if ($nextSched) {
                            $this->mergedCustomCells[] = "G{$currentRow}:G" . ($currentRow + 1);
                            $this->mergedCustomCells[] = "H{$currentRow}:H" . ($currentRow + 1);
                            $this->mergedCustomCells[] = "I{$currentRow}:I" . ($currentRow + 1);
                        }

                        $monthKey = Carbon::parse($sched->period_end)->format('Y-m');
                        $paymentRecord = $this->loan->payments->where('period_covered', $monthKey)->first();

                        if ($paymentRecord) {
                            $payAmt = $paymentRecord->amount_paid + $paymentRecord->interest;
                            $payDate = $this->formatAbbreviatedDate($paymentRecord->payment_date);
                            $totalPaymentSum += $payAmt; 
                        }
                    }

                } else {
                    $showBalance = $isCasab || !$isWholeMonth || (($index + 1) % 2 == 0);
                    if ($showBalance) {
                         $bal = max(0, $sched->balance_after);
                         $balanceDisplay = ($bal == 0) ? '-' : $bal;
                    }
                    
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
                        $totalPaymentSum += $payAmt; 
                    }

                    if ($paymentRecord) {
                        if ($balanceDisplay !== '') {
                            $payBalDisplay = $balanceDisplay;
                        } else {
                            $bal = max(0, $sched->balance_after);
                            $payBalDisplay = ($bal == 0) ? '-' : $bal;
                        }
                    }
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

        $rows[] = ['TOTAL', '', $totalPrin, $totalInt, $totalSum, '', '', '', $totalPaymentSum];
        $rows[] = ['', '', '', '', '', '', '', '', '']; 
        $rows[] = ['', 'Prepared by:', '', '', 'Approved:', '', '', '', '']; 
        $rows[] = ['', '', '', '', '', '', '', '', '']; 
        $rows[] = ['', '', '', '', '', '', '', '', '']; 
        
        $committee = CommitteeSignatory::first();
        $creditName = $committee ? $committee->credit_committee_name : 'ARNEL S. ABALOS';
        $chairName = $committee ? $committee->chair_person_name : 'FRANCIS DAVE T. RAMIREZ';

        $rows[] = ['', $creditName, '', '', $chairName, '', '', '', ''];
        $rows[] = ['', 'Member-Credit Committee', '', '', 'Chair-Person Committee', '', '', '', ''];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5.08,
            'B' => 20.95,
            'C' => 12.20,
            'D' => 10.10,
            'E' => 13.40,
            'F' => 12.50,
            'G' => 12.10,
            'H' => 12.20, 
            'I' => 12.10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->array()) - 6; 
        $sigRowStart = $lastRow + 2; 
        $nameRow = $sigRowStart + 3; 
        $titleRow = $nameRow + 1; 

        $numFontSize = $this->loan->amount_granted >= 1000000 ? 10 : 11;

        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4); 
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1); 
        $sheet->getPageSetup()->setFitToHeight(0);

        $sheet->getPageMargins()->setTop(0.25);
        $sheet->getPageMargins()->setBottom(0.25);
        $sheet->getPageMargins()->setLeft(0.25);
        $sheet->getPageMargins()->setRight(0.25);
        $sheet->getPageMargins()->setHeader(0.3);
        $sheet->getPageMargins()->setFooter(0.3);

        $sheet->getRowDimension(7)->setRowHeight(30.95); 
        $sheet->getRowDimension(8)->setRowHeight(11.10); 
        $sheet->getRowDimension(12)->setRowHeight(3.75); 

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Cambria')->setSize(11);

        $sheet->mergeCells('A5:I5');
        $sheet->mergeCells('A6:I6');
        $sheet->mergeCells('A7:I7');

        $sheet->mergeCells('A9:B9'); 
        $sheet->mergeCells('C9:E9'); 
        $sheet->mergeCells('A10:B10');
        $sheet->mergeCells('C10:E10'); 
        $sheet->mergeCells('A11:B11');
        $sheet->mergeCells('C11:E11'); 
        
        $sheet->mergeCells('A13:B13');
        $sheet->mergeCells('A14:B14');

        $sheet->mergeCells('A15:I15');
        
        $sheet->mergeCells('A16:A17'); 
        $sheet->mergeCells('B16:B17'); 
        $sheet->mergeCells('C16:C17'); 
        $sheet->mergeCells('D16:D17'); 
        
        $sheet->mergeCells('F16:F17');
        
        $sheet->mergeCells('G16:I16');
        
        $sheet->mergeCells('G17:G18');
        $sheet->mergeCells('H17:H18');
        $sheet->mergeCells('I17:I18');

        $sheet->mergeCells("A{$lastRow}:B{$lastRow}");

        $sheet->mergeCells("B{$sigRowStart}:D{$sigRowStart}"); 
        $sheet->mergeCells("E{$sigRowStart}:I{$sigRowStart}"); 
        
        $sheet->mergeCells("B{$nameRow}:D{$nameRow}"); 
        $sheet->mergeCells("E{$nameRow}:I{$nameRow}"); 
        
        $sheet->mergeCells("B{$titleRow}:D{$titleRow}"); 
        $sheet->mergeCells("E{$titleRow}:I{$titleRow}"); 

        foreach ($this->mergedCustomCells as $mergeRange) {
            $sheet->mergeCells($mergeRange);
        }

        $styles = [
            'A5:I7' => [
                'font' => ['bold' => true, 'name' => 'Cambria', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            'A9:A15' => ['font' => ['name' => 'Cambria', 'size' => 10]],
            'C9:E11' => [
                'font' => ['bold' => true, 'name' => 'Cambria', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'horizontal' => ['borderStyle' => Border::BORDER_THIN],
                    'bottom' => ['borderStyle' => Border::BORDER_THIN] 
                ]
            ],
            'C13' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'numberFormat' => ['formatCode' => '#,##0.00'],
                'font' => ['name' => 'Cambria', 'size' => $numFontSize]
            ],
            'C14' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'font' => ['name' => 'Cambria', 'size' => 11]
            ],
            
            'A16:I16' => [
                'font' => ['name' => 'Cambria', 'size' => 10, 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]
            ],
            'A17:F17' => [
                'font' => ['name' => 'Cambria', 'size' => 8, 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            'G17:I17' => [
                'font' => ['name' => 'Cambria', 'size' => 8, 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            
            'E17' => [
                'font' => ['name' => 'Cambria', 'size' => 8, 'bold' => true]
            ],
            
            "A18:F18" => [
                'font' => ['name' => 'Cambria', 'size' => 11, 'bold' => true],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ],
            "C18:F18" => [
                'font' => ['name' => 'Cambria', 'size' => $numFontSize, 'bold' => true],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_RIGHT]
            ],

            "A19:A{$lastRow}" => [
                'font' => ['name' => 'Cambria', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            "B19:B{$lastRow}" => [
                'font' => ['name' => 'Cambria', 'size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ],
            "C19:G{$lastRow}" => [
                'font' => ['name' => 'Cambria', 'size' => $numFontSize],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_RIGHT] 
            ],
            "H19:H{$lastRow}" => [ 
                'font' => ['name' => 'Cambria', 'size' => 11],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            "I19:I{$lastRow}" => [
                'font' => ['name' => 'Cambria', 'size' => $numFontSize],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_RIGHT] 
            ],
            
            "A16:I{$lastRow}" => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ],
            
            "A{$lastRow}:B{$lastRow}" => [
                'font' => ['bold' => true, 'italic' => true, 'name' => 'Cambria', 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            "C{$lastRow}:I{$lastRow}" => [
                'font' => ['bold' => true, 'name' => 'Cambria', 'size' => $numFontSize],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_RIGHT]
            ],

            "B{$sigRowStart}:I{$sigRowStart}" => [
                'font' => ['name' => 'Cambria', 'size' => 11, 'bold' => false],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT] 
            ],
            "B{$nameRow}:I{$nameRow}" => [
                'font' => ['name' => 'Cambria', 'size' => 11, 'bold' => true], 
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT] 
            ],
            "B{$titleRow}:I{$titleRow}" => [
                'font' => ['name' => 'Cambria', 'size' => 11, 'italic' => true, 'bold' => false], 
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT] 
            ]
        ];
        
        $sheet->getStyle("C18:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00_-');

        return $styles;
    }
}