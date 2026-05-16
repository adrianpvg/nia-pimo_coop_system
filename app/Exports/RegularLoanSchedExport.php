<?php

namespace App\Exports;

use App\Models\Loan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Carbon\Carbon;

class RegularLoanSchedExport implements FromArray, WithStyles, WithColumnWidths, WithDrawings
{
    protected $year;
    protected $officeFilter;
    protected $employeeType;
    protected $scheduleMonth;
    protected $scheduleDate;
    protected $employeeIds;

    public function __construct($year, $officeFilter, $employeeType, $scheduleMonth, $scheduleDate, $employeeIds)
    {
        $this->year = $year;
        $this->officeFilter = $officeFilter;
        $this->employeeType = $employeeType;
        $this->scheduleMonth = $scheduleMonth;
        $this->scheduleDate = $scheduleDate;
        $this->employeeIds = $employeeIds; // Can now accept a string OR an array
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
        $drawingRight->setCoordinates('E1'); 
        $drawingRight->setOffsetX(109); 
        $drawingRight->setOffsetY(8); 
        
        return [$drawingLeft, $drawingRight];
    }

    public function array(): array
    {
        $query = Loan::with(['borrower', 'schedules', 'payments'])
            ->where('type', 'REGULAR SALARY LOAN')
            ->whereYear('date_of_application', $this->year);

        if ($this->officeFilter !== 'ALL') {
            $query->whereHas('borrower.office', function($q) {
                $q->where('name', $this->officeFilter);
            });
        }

        if ($this->employeeType) {
            $query->where('employee_type', $this->employeeType);
        }

        // --- FIXED: Safe Array vs String validation logic ---
        $idsArray = [];
        if (is_array($this->employeeIds)) {
            $idsArray = array_filter($this->employeeIds);
        } elseif (is_string($this->employeeIds) && !empty(trim($this->employeeIds))) {
            $idsArray = preg_split('/[\s,]+/', trim($this->employeeIds), -1, PREG_SPLIT_NO_EMPTY);
        }

        if (!empty($idsArray)) {
            $query->whereHas('borrower', function($q) use ($idsArray) {
                $q->whereIn('employee_id', $idsArray);
            });
        }
        // -----------------------------------------------------

        $loans = $query->get();

        $displayMonth = strtoupper(Carbon::parse($this->scheduleMonth . '-01')->format('F Y'));

        $rows = [];
        
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['', '', '', '', ''];
        
        $rows[] = ['NIA REGION 1 MULTIPURPOSE COOPERATIVE', '', '', '', ''];
        $rows[] = ['BAYAOAS, URDANETA CITY, PANGASINAN', '', '', '', ''];
        $rows[] = ['REGULAR SALARY LOAN SUMMARY FOR THE MONTH OF ' . $displayMonth . ' (' . $this->employeeType . ')', '', '', '', ''];
        $rows[] = ['', '', '', '', '']; 

        $rows[] = ['SEQ NO.', 'EMP ID NO.', 'NAME', 'TOTAL', 'BALANCE'];
        $rows[] = ['', '', '', '(Principal + Interest)', ''];

        $seq = 1;
        $targetMonth = $this->scheduleMonth;
        $targetDate = $this->scheduleDate;

        $sumTotal = 0;
        $sumBalance = 0;

        foreach ($loans as $loan) {
            $totalDue = 0;
            $hasSchedule = false;

            if ($this->employeeType === 'Permanent') {
                $monthSchedules = $loan->schedules->filter(function($s) use ($targetMonth) {
                    return substr($s->period_end, 0, 7) === $targetMonth;
                });

                if ($monthSchedules->isNotEmpty()) {
                    $hasSchedule = true;
                    
                    if ($loan->payment_preference === 'whole_month') {
                        $totalDue = $monthSchedules->sum('total_due');
                    } else {
                        $totalDue = $monthSchedules->first()->total_due;
                    }
                }
            } else {
                $targetSchedule = $loan->schedules->first(function($s) use ($targetDate) {
                    return $s->period_end === $targetDate;
                });

                if ($targetSchedule) {
                    $hasSchedule = true;
                    $totalDue = $targetSchedule->total_due;
                }
            }

            if ($hasSchedule) {
                $totalExpected = $loan->schedules->sum('total_due');
                $totalPaid = $loan->payments->sum(function($payment) {
                    return $payment->amount_paid + $payment->interest;
                });
                
                $remainingBalance = max(0, $totalExpected - $totalPaid);

                $sumTotal += $totalDue;
                $sumBalance += $remainingBalance;

                $rows[] = [
                    $seq++,
                    $loan->borrower->employee_id,
                    $loan->borrower->name,
                    $totalDue,
                    $remainingBalance
                ];
            }
        }

        $rows[] = ['TOTAL', '', '', $sumTotal, $sumBalance];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10.00,
            'B' => 15.00,
            'C' => 30.00,
            'D' => 20.00,
            'E' => 20.00,
        ];
    }

    public function styles($sheet)
    {
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4); 
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1); 
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Cambria')->setSize(11);

        $sheet->getRowDimension(7)->setRowHeight(30.95);

        $sheet->mergeCells('A5:E5');
        $sheet->mergeCells('A6:E6');
        $sheet->mergeCells('A7:E7');

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:B10');
        $sheet->mergeCells('C9:C10');
        $sheet->mergeCells('E9:E10');

        $highestRow = $sheet->getHighestRow();

        $sheet->mergeCells("A{$highestRow}:C{$highestRow}");

        $styles = [
            'A5:E7' => [
                'font' => ['bold' => true, 'name' => 'Cambria', 'size' => 11],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER, 
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            'A9:E10' => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'A9:E' . $highestRow => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
            'B11:B' . ($highestRow - 1) => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            'D11:E' . $highestRow => [
                'numberFormat' => ['formatCode' => '#,##0.00_-'],
            ],
            "A{$highestRow}:E{$highestRow}" => [
                'font' => ['bold' => true, 'italic' => false],
            ],
            "A{$highestRow}:C{$highestRow}" => [
                'font' => ['bold' => true, 'italic' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];

        return $styles;
    }
}