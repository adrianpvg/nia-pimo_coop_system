@extends('layouts.app')

@section('content')

<style>
    .glass-panel {
        background: linear-gradient(135deg, rgba(255,255,255,0.6), rgba(255,255,255,0.3));
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        border-radius: 16px;
    }

    .glass-input {
        background: rgba(255, 255, 255, 0.5) !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        border-radius: 12px;
        transition: all 0.3s ease;
        color: #2d3748 !important;
    }
    .glass-input:focus {
        background: rgba(255, 255, 255, 0.9) !important;
        border-color: #007aff !important;
        box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.15) !important;
    }

    .glass-input[readonly] {
        background: rgba(0,0,0,0.02) !important;
        color: var(--text-secondary) !important;
        border-color: rgba(0,0,0,0.05) !important;
    }

    /* Custom Validation Styling Fixes */
    .was-validated .glass-input:invalid,
    .glass-input.is-invalid {
        border-color: #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.03) !important;
        background-image: none !important; 
    }

    .invalid-group {
        border-color: #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.03) !important;
    }

    .invalid-feedback {
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 0.25rem;
    }

    /* MODALS */
    .modal-backdrop.show {
        opacity: 1 !important; 
        background: rgba(0, 0, 0, 0.15) !important; 
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
    }

    .glass-modal-content {
        background: rgba(255, 255, 255, 0.65) !important; 
        backdrop-filter: blur(30px);
        -webkit-backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 24px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15), inset 0 0 0 1px rgba(255,255,255,0.5);
    }

    /* Inner Form Panel for consistent nesting */
    .form-inner-panel {
        background: rgba(255, 255, 255, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    /* Table Hover overrides for Glass UI */
    table.dataTable.table-hover > tbody > tr:hover > *,
    .table-hover > tbody > tr:hover > * {
        box-shadow: inset 0 0 0 9999px rgba(0, 122, 255, 0.05);
    }

    /* Apple-style Tabs for Show Page */
    .apple-tabs {
        border-bottom: 1px solid rgba(0,0,0,0.1);
        gap: 1rem;
    }
    .apple-tabs .nav-link {
        border: none;
        color: var(--text-secondary);
        font-weight: 500;
        padding: 0.75rem 0.5rem;
        background: transparent;
        position: relative;
    }
    .apple-tabs .nav-link.active {
        color: #2d3748;
        font-weight: 600;
        background: transparent;
    }
    .apple-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #007aff, #34c759);
        border-radius: 3px 3px 0 0;
    }

    /* Improved Detail Rows to Prevent Squishing */
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
        border-bottom: 1px dashed rgba(0,0,0,0.08);
        padding-bottom: 0.35rem;
    }
    .detail-label {
        color: var(--text-secondary);
        font-size: 0.85rem;
        font-weight: 600;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    .detail-value {
        color: #2d3748;
        font-size: 0.9rem;
        font-weight: 700;
        text-align: right;
        word-break: break-word;
        flex-grow: 1;
    }
</style>

<div class="d-flex justify-content-between mb-4 gap-3">
    <div class="d-flex flex-wrap gap-2">
        <button type="button" data-bs-toggle="modal" data-bs-target="#deleteLoanModal" class="btn btn-outline-danger glass-panel rounded-pill px-4 shadow-sm d-flex align-items-center" style="font-weight: 500;">
            <i class="bi bi-trash-fill me-2"></i> Delete Loan
        </button>
        <button type="button" data-bs-toggle="modal" data-bs-target="#editLoanModal" class="btn btn-outline-primary glass-panel rounded-pill px-4 shadow-sm d-flex align-items-center" style="font-weight: 500;">
            <i class="bi bi-pencil-fill me-2"></i> Edit Details
        </button>
        <a href="{{ route('finance.export_sched', $loan->id) }}" class="btn btn-outline-success glass-panel rounded-pill px-4 shadow-sm d-flex align-items-center" style="font-weight: 500;">
            <i class="bi bi-file-earmark-excel-fill me-2"></i> Export Schedule
        </a>
    </div>
    
    <a href="{{ route('finance.index', ['type' => $loan->type]) }}" class="btn btn-light glass-panel rounded-pill px-4 shadow-sm d-flex align-items-center" style="font-weight: 500;">
        <i class="bi bi-arrow-left me-2"></i> Back
    </a>
</div>

<!-- ========================================== -->
<!-- UPDATED COMPREHENSIVE LOAN DETAILS PANEL   -->
<!-- ========================================== -->
<div class="glass-panel p-4 mb-4 shadow-sm position-relative overflow-hidden">
    <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #34c759, #007aff);"></div>
    
    @php 
        $total_principal = round($loan->amount_granted, 2);
        
        if (!is_null($loan->actual_months) && $loan->schedules->isNotEmpty()) {
            $total_interest = round($loan->schedules->sum('interest_due'), 2);
        } else {
            if ($loan->type === 'CASAB') {
                $days = \Carbon\Carbon::parse($loan->payment_start)->diffInDays(\Carbon\Carbon::parse($loan->payment_end));
                $total_interest = round($total_principal * ($days / 30) * ($loan->base_interest / 100), 2);
            } else {
                $total_interest = round($total_principal * ($loan->interest_rate / 100), 2);
            }
        }

        $total_liability = round($total_principal + $total_interest, 2);

        $paid_principal = round($loan->payments->sum('amount_paid'), 2);
        $paid_interest = round($loan->payments->sum('interest'), 2);
        
        $total_paid = round($paid_principal + $paid_interest, 2);

        $bal = round($total_liability - $total_paid, 2);
        
        $rem_principal = max(0, round($total_principal - $paid_principal, 2));
        $rem_interest = max(0, round($total_interest - $paid_interest, 2));
    @endphp

    <div class="row g-5">
        <!-- Column 1: Borrower Information -->
        <div class="col-lg-4 col-md-6">
            <div class="text-muted small text-uppercase fw-bold mb-3"><i class="bi bi-person-badge me-2 text-primary"></i>Borrower Profile</div>
            <h5 class="fw-bold text-dark mb-2">{{ $loan->borrower->name }}</h5>
            
            <div class="mb-3">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2">{{ $loan->borrower->office->name }}</span>
                @if($loan->employee_type)
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2 ms-1">{{ $loan->employee_type }}</span>
                @endif
            </div>

            <div class="detail-row">
                <span class="detail-label">Employee ID</span>
                <span class="detail-value">{{ $loan->borrower->employee_id ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Co-Maker</span>
                <span class="detail-value">{{ $loan->borrower->co_maker ? $loan->borrower->co_maker : 'None' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Application Term</span>
                <span class="detail-value text-muted">{{ fmod($loan->no_of_months, 1) !== 0.00 ? number_format($loan->no_of_months, 2) : round($loan->no_of_months) }} Months</span>
            </div>
            <div class="detail-row border-0">
                <span class="detail-label">Actual Term</span>
                <div class="detail-value d-flex align-items-center justify-content-end gap-1">
                    @if(is_null($loan->actual_months))
                        <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-1 text-dark shadow-sm" style="font-size: 0.75rem; font-weight: 600;" data-bs-toggle="modal" data-bs-target="#actualMonthsModal">
                            Set Term
                        </button>
                    @else
                        <span class="text-success">{{ fmod($loan->actual_months, 1) !== 0.00 ? number_format($loan->actual_months, 2) : round($loan->actual_months) }} Months</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle px-2 py-1 shadow-none border-0 ms-1" data-bs-toggle="modal" data-bs-target="#actualMonthsModal" title="Update Term">
                            <i class="bi bi-pencil-fill" style="font-size: 0.8rem;"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Column 2: Application Timeline -->
        <div class="col-lg-4 col-md-6">
            <div class="text-muted small text-uppercase fw-bold mb-3"><i class="bi bi-calendar-event me-2 text-info"></i>Application Info</div>
            
            <div class="mb-3">
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1">
                    {{ $loan->type }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Control No.</span>
                <span class="detail-value">{{ $loan->control_number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date Applied</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($loan->date_of_application)->format('M d, Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Start</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($loan->payment_start)->format('M d, Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment End</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($loan->payment_end)->format('M d, Y') }}</span>
            </div>
            <div class="detail-row border-0">
                <span class="detail-label">Pref. Method</span>
                <span class="detail-value">
                    @if($loan->type === 'SPECIAL LOAN') Flexible 
                    @else {{ $loan->payment_preference === 'whole_month' ? 'Monthly' : '15th & EOM' }} 
                    @endif
                </span>
            </div>
        </div>

        <!-- Column 3: Status & Balances -->
        <div class="col-lg-4 col-md-12">
            <div class="text-muted small text-uppercase fw-bold mb-3"><i class="bi bi-wallet2 me-2 text-warning"></i>Status & Balance</div>

            <div class="p-4 rounded-4 bg-white shadow-sm border" style="border-color: rgba(0,0,0,0.05) !important;">
                <div class="small text-muted fw-bold text-uppercase mb-1">Current Balance</div>
                <h2 class="fw-bold mb-3 {{ $bal > 0 ? 'text-danger' : 'text-success' }}" style="letter-spacing: -0.5px;">
                    ₱ {{ number_format($bal, 2) }}
                </h2>
                
                @if($bal <= 0)
                    <span class="badge bg-success rounded-pill px-4 py-2 shadow-sm fs-6"><i class="bi bi-check-circle me-2"></i> Fully Paid</span>
                @else
                    <div class="d-flex justify-content-between small mt-2 border-top pt-3">
                        <span class="text-muted fw-medium">Remaining Principal:<br> <span class="text-dark fw-bold fs-6">₱{{ number_format($rem_principal, 2) }}</span></span>
                        <span class="text-muted fw-medium text-end">Remaining Interest:<br> <span class="text-dark fw-bold fs-6">₱{{ number_format($rem_interest, 2) }}</span></span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Financial Breakdown Horizontal Panel -->
    <div class="mt-4 pt-4 border-top" style="border-color: rgba(0,0,0,0.08) !important;">
        <div class="text-muted small text-uppercase fw-bold mb-3"><i class="bi bi-cash-coin me-2 text-success"></i>Financial Breakdown</div>
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="small text-secondary fw-semibold mb-1">Amount Granted</div>
                <div class="fw-bold text-primary fs-5">₱ {{ number_format($loan->amount_granted, 2) }}</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-start">
                <div class="small text-secondary fw-semibold mb-1">Net Proceeds</div>
                <div class="fw-bold text-success fs-5">₱ {{ number_format($loan->net_proceeds, 2) }}</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-start">
                <div class="small text-secondary fw-semibold mb-1">Service Fee</div>
                <div class="fw-bold text-dark fs-6 mt-1">₱ {{ number_format($loan->service_fee, 2) }}</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-start">
                <div class="small text-secondary fw-semibold mb-1">Surcharge</div>
                <div class="fw-bold text-dark fs-6 mt-1">₱ {{ number_format($loan->surcharge, 2) }}</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-start">
                <div class="small text-secondary fw-semibold mb-1">Base Interest</div>
                <div class="fw-bold text-dark fs-6 mt-1">{{ $loan->base_interest }} %</div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-start">
                <div class="small text-secondary fw-semibold mb-1">Total Eff. Rate</div>
                <div class="fw-bold text-dark fs-6 mt-1">{{ $loan->interest_rate }} %</div>
            </div>
        </div>
    </div>

</div>
<!-- ========================================== -->


<ul class="nav nav-tabs apple-tabs mb-4" id="loanTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#schedule-pane">Amortization Schedule</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#history-pane">Payment History</button>
    </li>
</ul>

<div class="tab-content">

    <div class="tab-pane fade show active" id="schedule-pane">
        <div class="glass-panel p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-calendar3 me-2 text-primary"></i>Amortization Schedule</h5>
            </div>
            
            @if($loan->type === 'SPECIAL LOAN')
                <div class="alert alert-info border-0 rounded-4 shadow-sm p-4 d-flex align-items-center mb-0" style="background: rgba(0, 122, 255, 0.05);">
                    <i class="bi bi-info-circle-fill fs-2 text-primary me-4"></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Flexible Payment Structure</h6>
                        <p class="small text-muted mb-0">Special Loans do not follow a strict month-to-month schedule. Payments reflect below as they are made against the total balance.</p>
                    </div>
                </div>
                
                <div class="table-responsive mt-4">
                    <table class="table table-hover text-center align-middle mb-0">
                        <thead style="background: rgba(0, 122, 255, 0.05); border-bottom: 1px solid rgba(0, 122, 255, 0.1);">
                            <tr class="small text-uppercase text-secondary">
                                <th class="py-3">Seq. No.</th>
                                <th>Period Covered</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Total</th>
                                <th>Balance</th>
                                <th class="border-start">Payment Date</th>
                                <th>Payment Amt.</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <tr style="background: rgba(0, 0, 0, 0.02);">
                                <td></td>
                                <td class="text-start ps-4 fw-bold text-secondary">Principal</td>
                                <td></td><td></td><td></td>
                                <td class="fw-bold text-dark">₱ {{ number_format($total_principal, 2) }}</td>
                                <td class="border-start"></td><td></td>
                            </tr>
                            
                            @php 
                                $spRunBal = $total_liability; 
                                $spRemPrin = $total_principal;
                                $spRemInt = $total_interest;
                                
                                $total_prin_sched = 0;
                                $total_int_sched = 0;
                                $total_sum_sched = 0;
                            @endphp
                            
                            @foreach($loan->payments as $index => $pay)
                                @php 
                                    $payTotal = $pay->amount_paid + $pay->interest;
                                    $spRunBal -= $payTotal;
                                    $spRemPrin -= $pay->amount_paid;
                                    $spRemInt -= $pay->interest;
                                    
                                    $total_prin_sched += $pay->amount_paid;
                                    $total_int_sched += $pay->interest;
                                    $total_sum_sched += $payTotal;

                                    $payDateObj = \Carbon\Carbon::parse($pay->payment_date);
                                    $monthName = $payDateObj->format('F');
                                    $formattedPeriod = strlen($monthName) <= 5 ? $payDateObj->format('F d, Y') : $payDateObj->format('M. d, Y');
                                    if ($monthName === 'September') $formattedPeriod = 'Sept. ' . $payDateObj->format('d, Y');
                                @endphp
                                <tr>
                                    <td class="py-3 text-muted">{{ $index + 1 }}</td>
                                    <td class="text-start ps-4 fw-semibold text-dark">{{ $formattedPeriod }}</td>
                                    <td class="text-muted">₱ {{ number_format($pay->amount_paid, 2) }}</td>
                                    <td class="text-muted">₱ {{ number_format($pay->interest, 2) }}</td>
                                    <td class="fw-medium text-primary">₱ {{ number_format($payTotal, 2) }}</td>
                                    <td class="fw-bold text-dark">₱ {{ number_format($spRemPrin, 2) }}</td>
                                    <td class="border-start text-success fw-medium">{{ $formattedPeriod }}</td>
                                    <td class="text-success fw-bold">₱ {{ number_format($payTotal, 2) }}</td>
                                </tr>
                            @endforeach
                            
                            @if($spRunBal > 0)
                                @php
                                    $total_prin_sched += max(0, $spRemPrin);
                                    $total_int_sched += max(0, $spRemInt);
                                    $total_sum_sched += max(0, $spRunBal);

                                    $dueDateObj = \Carbon\Carbon::parse($loan->payment_end);
                                    $dueMonthName = $dueDateObj->format('F');
                                    $formattedDueDate = strlen($dueMonthName) <= 5 ? $dueDateObj->format('F d, Y') : $dueDateObj->format('M. d, Y');
                                    if ($dueMonthName === 'September') $formattedDueDate = 'Sept. ' . $dueDateObj->format('d, Y');
                                @endphp
                                <tr style="background: rgba(255, 193, 7, 0.05);">
                                    <td class="py-3 text-muted">{{ $loan->payments->count() + 1 }}</td>
                                    <td class="text-start ps-4 fw-bold text-danger">{{ $formattedDueDate }} <span class="badge bg-danger bg-opacity-10 text-danger ms-2">Due Date</span></td>
                                    <td class="text-muted">₱ {{ number_format(max(0, $spRemPrin), 2) }}</td>
                                    <td class="text-muted">₱ {{ number_format(max(0, $spRemInt), 2) }}</td>
                                    <td class="fw-medium text-danger">₱ {{ number_format(max(0, $spRunBal), 2) }}</td>
                                    <td class="fw-bold text-dark">₱ 0.00</td>
                                    <td class="border-start"></td><td></td>
                                </tr>
                            @endif
                            
                            <tr style="background: rgba(0, 122, 255, 0.05); border-top: 2px solid rgba(0, 122, 255, 0.2);">
                                <td colspan="2" class="text-end pe-4 fw-bold text-secondary text-uppercase" style="letter-spacing: 1px;">Totals:</td>
                                <td class="fw-bold text-dark">₱ {{ number_format($total_prin_sched, 2) }}</td>
                                <td class="fw-bold text-dark">₱ {{ number_format($total_int_sched, 2) }}</td>
                                <td class="fw-bold text-primary fs-6">₱ {{ number_format($total_sum_sched, 2) }}</td>
                                <td></td>
                                <td class="border-start"></td><td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <!-- REGULAR & CASAB LOAN SCHEDULE TABLE -->
                <div class="table-responsive">
                    <table class="table table-hover text-center align-middle mb-0">
                        <thead style="background: rgba(0, 122, 255, 0.05); border-bottom: 1px solid rgba(0, 122, 255, 0.1);">
                            <tr class="small text-uppercase text-secondary">
                                <th>Seq. No.</th>
                                <th>Period Covered</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Total</th>
                                <th>Balance</th>
                                <th class="border-start">Payment Date</th>
                                <th>Payment Amt.</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <tr style="background: rgba(0, 0, 0, 0.02);">
                                <td></td>
                                <td class="text-start ps-4 fw-bold text-secondary">Principal</td>
                                <td></td><td></td><td></td>
                                <td class="fw-bold text-dark">₱ {{ number_format($loan->amount_granted, 2) }}</td>
                                <td class="border-start"></td><td></td>
                            </tr>
                            
                            @forelse($loan->schedules as $index => $sched)
                                @php
                                    $paymentRecord = null;
                                    
                                    if ($loan->payment_preference === 'half_month' || $loan->type === 'CASAB') {
                                        $paymentRecord = $loan->payments->where('period_covered', $sched->period_end)->first();
                                        $showPaymentThisRow = true;
                                    } else {
                                        // Whole month grabs the month chunk. We only display it on the 2nd half schedule row
                                        $monthKey = \Carbon\Carbon::parse($sched->period_end)->format('Y-m');
                                        $paymentRecord = $loan->payments->where('period_covered', $monthKey)->first();
                                        $showPaymentThisRow = ($index + 1) % 2 == 0; 
                                    }

                                    $pStart = \Carbon\Carbon::parse($sched->period_start);
                                    $pEnd = \Carbon\Carbon::parse($sched->period_end);

                                    $startMonth = $pStart->format('F');
                                    $startStr = strlen($startMonth) <= 5 ? $pStart->format('F d') : $pStart->format('M. d');
                                    if ($startMonth === 'September') $startStr = 'Sept. ' . $pStart->format('d');

                                    $endMonth = $pEnd->format('F');
                                    $endStr = strlen($endMonth) <= 5 ? $pEnd->format('F d, Y') : $pEnd->format('M. d, Y');
                                    if ($endMonth === 'September') $endStr = 'Sept. ' . $pEnd->format('d, Y');

                                    $payDateStr = '';
                                    if($paymentRecord) {
                                        $payDateObj = \Carbon\Carbon::parse($paymentRecord->payment_date);
                                        $payMonth = $payDateObj->format('F');
                                        $payDateStr = strlen($payMonth) <= 5 ? $payDateObj->format('F d, Y') : $payDateObj->format('M. d, Y');
                                        if ($payMonth === 'September') $payDateStr = 'Sept. ' . $payDateObj->format('d, Y');
                                    }

                                @endphp
                                <tr>
                                    <td class="py-3 text-muted">{{ $index + 1 }}</td>
                                    <td class="text-start ps-4 fw-semibold text-dark">
                                        {{ $startStr }}-{{ $endStr }}
                                    </td>
                                    <td class="text-muted">₱ {{ number_format($sched->principal_due, 2) }}</td>
                                    <td class="text-muted">₱ {{ number_format($sched->interest_due, 2) }}</td>
                                    <td class="fw-medium text-primary">₱ {{ number_format($sched->total_due, 2) }}</td>
                                    <td class="fw-bold text-dark">
                                        {{ ($index + 1) % 2 == 0 || $loan->type === 'CASAB' ? '₱ ' . number_format($sched->balance_after, 2) : '' }}
                                    </td>
                                    
                                    <td class="border-start text-success fw-medium">
                                        {{ $paymentRecord && $showPaymentThisRow ? $payDateStr : '' }}
                                    </td>
                                    <td class="text-success fw-bold">
                                        {{ $paymentRecord && $showPaymentThisRow ? '₱ ' . number_format($paymentRecord->amount_paid + $paymentRecord->interest, 2) : '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-5 text-center text-muted">
                                        <i class="bi bi-exclamation-circle fs-3 d-block mb-2 text-warning"></i>
                                        Schedule not generated.<br>
                                        Please set the <strong>Actual Term</strong> above to generate the amortization table.
                                    </td>
                                </tr>
                            @endforelse

                            @if($loan->schedules->isNotEmpty())
                                <tr style="background: rgba(0, 122, 255, 0.05); border-top: 2px solid rgba(0, 122, 255, 0.2);">
                                    <td colspan="2" class="text-end pe-4 fw-bold text-secondary text-uppercase" style="letter-spacing: 1px;">Totals:</td>
                                    <td class="fw-bold text-dark">₱ {{ number_format(round($loan->schedules->sum('principal_due'), 2), 2) }}</td>
                                    <td class="fw-bold text-dark">₱ {{ number_format(round($loan->schedules->sum('interest_due'), 2), 2) }}</td>
                                    <td class="fw-bold text-primary fs-6">₱ {{ number_format(round($loan->schedules->sum('total_due'), 2), 2) }}</td>
                                    <td></td>
                                    <td class="border-start"></td><td></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="tab-pane fade" id="history-pane">
        <div class="glass-panel p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Payment History</h5>
                @if($bal > 0)
                <button class="btn btn-dark shadow-sm px-4" style="border-radius: 12px;" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="bi bi-plus-lg me-2"></i> Add Payment
                </button>
                @endif
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover text-center align-middle mb-0">
                    <thead style="background: rgba(0, 122, 255, 0.05); border-bottom: 1px solid rgba(0, 122, 255, 0.1);">
                        <tr class="small text-uppercase text-secondary">
                            <th class="py-3 text-start ps-4">Date Paid & Period</th>
                            <th>Service Invoice</th>
                            <th>Principal Paid</th>
                            <th>Interest Paid</th>
                            <th>Running Balance</th>
                            <th>Action</th> 
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <tr style="background: rgba(52, 199, 89, 0.05);">
                            <td colspan="4" class="text-start ps-4 fw-bold text-success">
                                <i class="bi bi-cash-stack me-2"></i>LOAN GRANTED <span class="text-muted small fw-medium ms-2">(Principal + Interest)</span>
                            </td>
                            <td class="fw-bold text-success fs-5">₱ {{ number_format($total_liability, 2) }}</td>
                            <td></td>
                        </tr>
                        
                        @php $runBal = $total_liability; @endphp
                        @forelse($loan->payments as $pay)
                            @php 
                                $runBal -= ($pay->amount_paid + $pay->interest); 
                                
                                $periodTxt = $pay->period_covered;
                                if (str_starts_with($periodTxt, 'SPECIAL-')) {
                                    $formattedPeriod = 'Flexible / Custom';
                                } elseif (strlen($periodTxt) == 10) {
                                    $pObj = \Carbon\Carbon::parse($periodTxt);
                                    $pMonth = $pObj->format('F');
                                    $formattedPeriod = strlen($pMonth) <= 5 ? $pObj->format('F d, Y') : $pObj->format('M. d, Y');
                                    if ($pMonth === 'September') $formattedPeriod = 'Sept. ' . $pObj->format('d, Y');
                                } elseif (strlen($periodTxt) == 7) {
                                    $pObj = \Carbon\Carbon::parse($periodTxt . '-01');
                                    $pMonth = $pObj->format('F');
                                    $formattedPeriod = strlen($pMonth) <= 5 ? $pObj->format('F Y') : $pObj->format('M. Y');
                                    if ($pMonth === 'September') $formattedPeriod = 'Sept. ' . $pObj->format('Y');
                                } else {
                                    $formattedPeriod = $periodTxt;
                                }
                            @endphp
                            <tr>
                                <td class="py-3 text-start ps-4">
                                    <div class="small mt-1" style="font-size: 0.8rem; line-height: 1.4;">
                                        <div class="text-dark fw-bold mb-1">{{ \Carbon\Carbon::parse($pay->payment_date)->format('M d, Y') }}</div>
                                        
                                        <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-2 px-2 py-1 mb-1">
                                            Period: {{ $formattedPeriod }}
                                        </div>

                                        <div class="text-secondary mt-1" style="font-size: 0.7rem;">
                                            Created: <span class="fw-semibold text-dark">{{ $pay->creator->name ?? 'System' }}</span>
                                        </div>
                                        @if($pay->updated_by)
                                            <div class="text-primary" style="font-size: 0.7rem;">
                                                Updated: <span class="fw-semibold">{{ $pay->updater->name ?? 'Unknown' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="fw-bold text-dark fs-6">{{ $pay->or_number }}</td>
                                <td class="text-success fw-medium">₱ {{ number_format($pay->amount_paid, 2) }}</td>
                                <td class="text-muted">₱ {{ number_format($pay->interest, 2) }}</td>
                                <td class="fw-bold {{ $runBal <= 0 ? 'text-success' : 'text-danger' }}">
                                    @if($runBal <= 0)
                                        <i class="bi bi-check-circle-fill me-1"></i> SETTLED
                                    @else
                                        ₱ {{ number_format($runBal, 2) }}
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#editPaymentModal{{ $pay->id }}" class="btn btn-sm btn-outline-primary border-0 rounded-circle shadow-sm bg-white" title="Edit Service Invoice">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#deletePaymentModal{{ $pay->id }}" class="btn btn-sm btn-outline-danger border-0 rounded-circle shadow-sm bg-white" title="Delete Payment">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-muted small">No payments have been recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>        
<!-- ========================================================================= -->
<!-- IMPORTANT: All Modals are placed in a wrapper so JS can move them easily  -->
<!-- ========================================================================= -->
@push('modals')
<div id="all-modals-container">
    <!-- EDIT LOAN MODAL -->
    <div class="modal fade" id="editLoanModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content glass-modal-content border-0">
                <form action="{{ route('finance.update', $loan->id) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h4 class="modal-title fw-bold" style="background: linear-gradient(90deg, #007aff, #34c759); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            Edit Applicant Details
                        </h4>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body px-4 py-4">
                        <div class="form-inner-panel p-4">
                            <h6 class="fw-bold mb-4 text-dark border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                                <i class="bi bi-person-badge me-2 text-primary"></i>Profile Information
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="small text-secondary mb-1 fw-semibold">Application Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_of_application" class="form-control glass-input px-3 py-2" value="{{ \Carbon\Carbon::parse($loan->date_of_application)->format('Y-m-d') }}" required>
                                    <div class="invalid-feedback">Application date is required.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="small text-secondary mb-1 fw-semibold">Office <span class="text-danger">*</span></label>
                                    <select name="office_name" class="form-select glass-input px-3 py-2" required>
                                        @foreach($availableOffices as $off)
                                            <option value="{{ $off }}" {{ $loan->borrower->office->name === $off ? 'selected' : '' }}>{{ $off }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Office is required.</div>
                                </div>
                                
                                <div class="col-md-12 mt-4">
                                    <label class="small text-secondary mb-1 fw-semibold">Name of Applicant <span class="text-danger">*</span></label>
                                    <input type="text" name="borrower_name" class="form-control glass-input px-3 py-2" value="{{ $loan->borrower->name }}" required>
                                    <div class="invalid-feedback">Applicant name is required.</div>
                                </div>
                                
                                <div class="col-md-6 mt-2">
                                    <label class="small text-secondary mb-1 fw-semibold">Employee ID <span class="text-danger">*</span></label>
                                    <input type="text" name="employee_id" class="form-control glass-input px-3 py-2 @error('employee_id') is-invalid @enderror" value="{{ old('employee_id', $loan->borrower->employee_id) }}" required>
                                    @error('employee_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Employee ID is required.</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mt-2">
                                    <label class="small text-secondary mb-1 fw-semibold">Name of Co-Maker</label>
                                    <input type="text" name="co_maker" class="form-control glass-input px-3 py-2" value="{{ $loan->borrower->co_maker }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer border-top-0 pt-2 px-4 pb-4 mt-2 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7);">Cancel</button>
                        <button type="submit" class="btn btn-dark rounded-pill px-5 shadow-sm fw-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>                            
    <!-- ACTUAL MONTHS MODAL -->
    <div class="modal fade" id="actualMonthsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal-content border-0">
                <form action="{{ route('finance.update_actual_months', $loan->id) }}" method="POST" id="actualMonthsForm" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold text-primary">
                            <i class="bi bi-calendar3-range me-2"></i> {{ filled($loan->actual_months) ? 'Update Amortization Term' : 'Set Amortization Term' }}
                        </h5>
                    </div>
                    <div class="modal-body px-4 py-3">
                        <p class="text-secondary small mb-4">
                            To accurately generate the amortization schedule, please confirm the <strong>actual timeframe</strong> the borrower will take to pay this loan.
                        </p>
                        
                        @php
                            $maxTerm = 36;
                            if ($loan->type === 'SPECIAL LOAN') $maxTerm = 6;
                        @endphp

                        @if($loan->type === 'CASAB')
                            @php
                                $days = \Carbon\Carbon::parse($loan->payment_start)->diffInDays(\Carbon\Carbon::parse($loan->payment_end));
                                $calcMonths = round($days / 30, 2);
                            @endphp
                            <div class="alert alert-info border-0 rounded-4 shadow-sm p-3 mb-3" style="background: rgba(0, 122, 255, 0.05);">
                                <i class="bi bi-info-circle-fill text-primary me-2"></i> The CASAB Term is automatically calculated as <strong>{{ $calcMonths }} Months</strong> ({{ $days }} days).
                            </div>
                            <input type="hidden" name="actual_months" value="{{ $calcMonths }}">
                        @else
                            <div class="form-inner-panel p-3 text-center mb-3">
                                <label class="small text-secondary mb-2 fw-semibold">Actual Repayment Duration (Months)</label>
                                <input type="number" name="actual_months" id="actual_months_input" class="form-control glass-input text-center fw-bold fs-4 text-primary w-50 mx-auto" value="{{ old('actual_months', $loan->actual_months ?? round($loan->no_of_months)) }}" min="1" max="{{ $maxTerm }}" required>
                                <div class="invalid-feedback mt-2" id="actual_months_error">
                                    Term cannot exceed {{ $maxTerm }} months for {{ $loan->type }}.
                                </div>
                            </div>
                        @endif

                        <!-- Payment Preference Radio Buttons (Only for Regular Loans) -->
                        @if($loan->type === 'REGULAR SALARY LOAN')
                        <div class="form-inner-panel p-3 mb-3">
                            <label class="small text-secondary mb-3 fw-semibold d-block">Payment Method</label>
                            
                            <div class="row justify-content-center text-center">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <div class="form-check d-inline-block text-start">
                                        <input class="form-check-input shadow-none" type="radio" name="payment_preference" id="pref_half" value="half_month" {{ ($loan->payment_preference ?? 'half_month') === 'half_month' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-medium text-dark" for="pref_half">Every 15th & EOM</label>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-check d-inline-block text-start">
                                        <input class="form-check-input shadow-none" type="radio" name="payment_preference" id="pref_whole" value="whole_month" {{ ($loan->payment_preference ?? '') === 'whole_month' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-medium text-dark" for="pref_whole">Monthly</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($loan->payments->count() > 0)
                            <div class="alert alert-danger d-flex align-items-center mb-0 p-3" style="border-radius: 12px; border: 1px solid rgba(220, 53, 69, 0.3); background: rgba(220, 53, 69, 0.05);">
                                <i class="bi bi-exclamation-triangle-fill fs-3 text-danger me-3"></i>
                                <div class="small text-dark">
                                    <strong>Warning: Payments Exist!</strong><br>
                                    Updating the term will completely recalculate the interest schedule. To prevent database corruption, <strong class="text-danger">all existing payment records will be permanently deleted.</strong>
                                </div>
                            </div>
                        @endif

                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4 mt-2 d-flex justify-content-between">
                        <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm text-muted" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7);">{{ filled($loan->actual_months) ? 'Cancel' : 'Skip for Now' }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD PAYMENT MODAL -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal-content border-0">
                <form action="{{ route('finance.payment.store') }}" method="POST" id="paymentForm" novalidate>
                    @csrf
                    <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                    
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h4 class="modal-title fw-bold" style="background: linear-gradient(90deg, #007aff, #34c759); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            Record Payment
                        </h4>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body px-4 py-4">
                        <div class="form-inner-panel p-4">
                            <h6 class="fw-bold mb-4 text-dark border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                                <i class="bi bi-receipt me-2 text-primary"></i>Payment Details
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="small text-secondary mb-1 fw-semibold">Service Invoice <span class="text-danger">*</span></label>
                                    <input type="text" name="or_number" id="or_number" class="form-control glass-input px-3 py-2" placeholder="e.g. 123456" required>
                                    <div class="invalid-feedback">Service Invoice is required.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-secondary mb-1 fw-semibold">Date Paid <span class="text-danger">*</span></label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control glass-input px-3 py-2" value="{{ date('Y-m-d') }}" required>
                                    <div class="invalid-feedback">Payment date is required.</div>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <label class="small text-secondary mb-1 fw-semibold text-primary">Select Billing Month <span class="text-danger">*</span></label>
                                    @php
                                        // For regular loans, payment preference dictates grouping
                                        if ($loan->payment_preference === 'half_month' || $loan->type === 'CASAB') {
                                            $groupedSchedules = $loan->schedules->groupBy(function($s) { return $s->period_end; });
                                        } else {
                                            // Combine halves into one month grouping for Permanent
                                            $groupedSchedules = $loan->schedules->groupBy(function($s) { return \Carbon\Carbon::parse($s->period_end)->format('Y-m'); });
                                        }
                                        
                                        $paidPeriods = $loan->payments->pluck('period_covered')->toArray();
                                    @endphp
                                    
                                    @if($loan->type === 'SPECIAL LOAN')
                                        <input type="hidden" name="period_covered" value="SPECIAL-PAYMENT">
                                        <input type="hidden" name="is_special_payment" value="1">
                                        <div class="alert alert-info py-2 px-3 mb-0 small rounded-3 border border-info border-opacity-25 shadow-sm">
                                            Special loans allow flexible payments against the remaining balance.
                                        </div>
                                    @else
                                        <select name="period_covered" id="period_covered" class="form-select glass-input fw-bold px-3 py-2" style="border-color: rgba(0, 122, 255, 0.4) !important;" required onchange="updatePaymentDisplay(this)">
                                            <option value="" disabled selected>Choose a billing period...</option>
                                            @foreach($groupedSchedules as $periodKey => $periodSchedules)
                                                @php
                                                    $principal = $periodSchedules->sum('principal_due');
                                                    $interest = $periodSchedules->sum('interest_due');
                                                    $total = $principal + $interest;
                                                    
                                                    if ($loan->payment_preference === 'half_month' || $loan->type === 'CASAB') {
                                                        $start = \Carbon\Carbon::parse($periodSchedules->first()->period_start)->format('M d');
                                                        $end = \Carbon\Carbon::parse($periodSchedules->first()->period_end)->format('M d, Y');
                                                        $label = "{$start} - {$end}";
                                                    } else {
                                                        $label = \Carbon\Carbon::parse($periodKey . '-01')->format('F Y');
                                                    }
                                                    
                                                    $isPaid = in_array($periodKey, $paidPeriods);
                                                @endphp
                                                <option value="{{ $periodKey }}" data-principal="{{ $principal }}" data-interest="{{ $interest }}" data-total="{{ $total }}" {{ $isPaid ? 'disabled' : '' }}>
                                                    {{ $label }} {{ $isPaid ? '(Already Paid)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a valid billing period.</div>
                                    @endif
                                </div>

                                <div class="col-12 mt-3">
                                    <div class="p-3 rounded-4" style="background: rgba(0, 122, 255, 0.05); border: 1px solid rgba(0, 122, 255, 0.2);">
                                        
                                        @if($loan->type === 'SPECIAL LOAN')
                                            <div class="mb-2">
                                                <label class="small text-secondary mb-1 fw-bold">Enter Payment Amount <span class="text-danger">*</span></label>
                                                <div class="input-group glass-input" style="padding: 0; overflow: hidden; border-color: rgba(0, 122, 255, 0.4); box-shadow: 0 4px 10px rgba(0, 122, 255, 0.05);">
                                                    <span class="input-group-text bg-transparent border-0 text-primary ps-3 pe-2 fw-bold fs-5">₱</span>
                                                    <input type="number" step="0.01" name="custom_amount_paid" class="form-control bg-transparent border-0 py-3 fw-bold fs-4 text-primary shadow-none {{ $errors->has('custom_amount_paid') ? 'is-invalid' : '' }}" placeholder="0.00" value="{{ old('custom_amount_paid') }}" max="{{ $bal }}" required>
                                                </div>
                                                @error('custom_amount_paid')
                                                    <div class="small text-danger fw-bold mt-1">{{ $message }}</div>
                                                @else
                                                    <div class="small text-muted mt-1">Maximum payable: ₱{{ number_format($bal, 2) }}</div>
                                                @enderror
                                            </div>
                                        @else
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold text-dark small" style="letter-spacing: 0.5px;">TOTAL AMOUNT DUE</span>
                                                <span class="fs-4 fw-bold text-primary" id="display_total_due">₱ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between border-top border-primary border-opacity-25 pt-2">
                                                <span class="small fw-medium text-secondary">Principal: <strong class="text-dark" id="display_principal_due">₱ 0.00</strong></span>
                                                <span class="small fw-medium text-secondary">Interest: <strong class="text-dark" id="display_interest_due">₱ 0.00</strong></span>
                                            </div>
                                        @endif
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7);">Cancel</button>
                        <button type="submit" class="btn btn-dark rounded-pill px-5 shadow-sm fw-bold">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DELETE LOAN MODAL -->
    <div class="modal fade" id="deleteLoanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger">Delete Loan Application</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-secondary pb-4">
                    Are you sure you want to completely delete this loan application?<br><br>
                    <strong class="text-danger">Warning:</strong> This will also permanently delete any payment history attached to it. This action cannot be undone.
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light glass-panel" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('finance.destroy', $loan->id) }}" method="POST" class="m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="border-radius: 8px;">Delete Loan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- DYNAMIC EDIT & DELETE PAYMENT MODALS -->
    @foreach($loan->payments as $pay)
        <!-- EDIT PAYMENT MODAL -->
        <div class="modal fade" id="editPaymentModal{{ $pay->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glass-modal-content border-0">
                    <form action="{{ route('finance.payment.update', $pay->id) }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                            <h5 class="modal-title fw-bold text-primary">Update Payment Record</h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body px-4 py-4">
                            <p class="text-muted small mb-3">You can only update the Service Invoice for this record. Editing will log your name and update the timestamp.</p>
                            
                            <div class="mb-3">
                                <label class="small text-secondary mb-1 fw-semibold">Date Paid</label>
                                <input type="text" class="form-control glass-input" value="{{ \Carbon\Carbon::parse($pay->payment_date)->format('M d, Y') }}" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="small text-secondary mb-1 fw-semibold">Amount</label>
                                <input type="text" class="form-control glass-input text-success fw-bold" value="₱ {{ number_format($pay->amount_paid + $pay->interest, 2) }}" readonly>
                            </div>

                            <div class="mb-2">
                                <label class="small text-secondary mb-1 fw-semibold">Official Receipt (OR) Number <span class="text-danger">*</span></label>
                                <input type="text" name="or_number" class="form-control glass-input px-3 py-2 fw-bold text-dark" value="{{ $pay->or_number }}" style="border-color: rgba(0, 122, 255, 0.4) !important;" required>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                            <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- DELETE PAYMENT MODAL -->
        <div class="modal fade" id="deletePaymentModal{{ $pay->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glass-modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-danger">Delete Payment Record</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-secondary pb-4 text-start">
                        Are you sure you want to delete this payment record (OR: {{ $pay->or_number }}) for <strong>₱ {{ number_format($pay->amount_paid + $pay->interest, 2) }}</strong>?
                        <br><br>
                        This action cannot be undone and the balance will be recalculated.
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light glass-panel" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ route('finance.payment.destroy', $pay->id) }}" method="POST" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="border-radius: 8px;">Delete Payment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div> 
@endpush

@push('scripts')
<script>
    $(document).ready(function() { 
        let modalsContainer = document.getElementById('all-modals-container');
        if (modalsContainer) {
            document.body.appendChild(modalsContainer);
        }

        @if($errors->has('employee_id'))
            var editModal = new bootstrap.Modal(document.getElementById('editLoanModal'));
            editModal.show();
        @endif

        @if($errors->has('custom_amount_paid'))
            var paymentModal = new bootstrap.Modal(document.getElementById('addPaymentModal'));
            paymentModal.show();
        @endif

        @if(is_null($loan->actual_months))
            var myModal = new bootstrap.Modal(document.getElementById('actualMonthsModal'));
            myModal.show();
        @endif

        
        let actualMonthsForm = document.getElementById('actualMonthsForm');
        if (actualMonthsForm) {
            actualMonthsForm.addEventListener('submit', function(event) {
                let isValid = true;
                let input = document.getElementById('actual_months_input');
                
                if (input) {
                    let val = parseFloat(input.value) || 0;
                    
                    let maxTerm = 36;
                    if ('{{ $loan->type }}' === 'SPECIAL LOAN') maxTerm = 6;

                    // CASAB is validated silently by backend math, input is hidden
                    if ('{{ $loan->type }}' !== 'CASAB') {
                        if (val < 1 || val > maxTerm) {
                            input.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    }
                }

                if (!isValid) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });

            let actualMonthsInput = document.getElementById('actual_months_input');
            if (actualMonthsInput) {
                actualMonthsInput.addEventListener('input', function() {
                    let val = parseFloat(this.value) || 0;
                    
                    let maxTerm = 36;
                    if ('{{ $loan->type }}' === 'SPECIAL LOAN') maxTerm = 6;
                    
                    if(val >= 1 && val <= maxTerm) {
                        this.classList.remove('is-invalid');
                    }
                });
            }
        }
        
        let paymentForm = document.getElementById('paymentForm');
        if (paymentForm) {
            paymentForm.addEventListener('submit', function(event) {
                let isValid = true;
                
                if(document.getElementById('or_number').value.trim() === '') {
                    document.getElementById('or_number').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('or_number').classList.remove('is-invalid');
                }
                
                let periodSelect = document.getElementById('period_covered');
                if(periodSelect && periodSelect.value === '') {
                    periodSelect.classList.add('is-invalid');
                    isValid = false;
                } else if(periodSelect) {
                    periodSelect.classList.remove('is-invalid');
                }

                let customAmtInput = document.querySelector('input[name="custom_amount_paid"]');
                if (customAmtInput) {
                    let customAmt = parseFloat(customAmtInput.value) || 0;
                    let maxBal = {{ $bal }};
                    if (customAmt <= 0 || customAmt > maxBal) {
                        customAmtInput.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        customAmtInput.classList.remove('is-invalid');
                    }
                }

                if (!isValid) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.classList.add('was-validated');
                }
            }, false);

            let customAmtInput = document.querySelector('input[name="custom_amount_paid"]');
            if (customAmtInput) {
                customAmtInput.addEventListener('input', function() {
                    let val = parseFloat(this.value) || 0;
                    if(val > 0 && val <= {{ $bal }}) {
                        this.classList.remove('is-invalid');
                    }
                });
            }
        }
    });

    function updatePaymentDisplay(selectElement) {
        let selectedOption = selectElement.options[selectElement.selectedIndex];
        
        if(selectedOption.value) {
            let principal = parseFloat(selectedOption.getAttribute('data-principal'));
            let interest = parseFloat(selectedOption.getAttribute('data-interest'));
            let total = parseFloat(selectedOption.getAttribute('data-total'));
            
            document.getElementById('display_principal_due').innerText = '₱ ' + principal.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('display_interest_due').innerText = '₱ ' + interest.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('display_total_due').innerText = '₱ ' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
            
            selectElement.classList.remove('is-invalid');
        }
    }
</script>
@endpush
@endsection