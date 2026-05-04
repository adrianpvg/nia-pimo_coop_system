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
</style>

<div class="d-flex justify-content-between mb-4">
    <div class="d-flex gap-2">
        <button type="button" data-bs-toggle="modal" data-bs-target="#deleteLoanModal" class="btn btn-outline-danger glass-panel rounded-pill px-4 shadow-sm d-flex align-items-center" style="font-weight: 500;">
            <i class="bi bi-trash-fill me-2"></i> Delete Loan
        </button>
        <a href="{{ route('finance.export_sched', $loan->id) }}" class="btn btn-outline-success glass-panel rounded-pill px-4 shadow-sm d-flex align-items-center" style="font-weight: 500;">
            <i class="bi bi-file-earmark-excel-fill me-2"></i> Export Schedule
        </a>
    </div>
    
    <a href="{{ route('finance.index', ['type' => $loan->type]) }}" class="btn btn-light glass-panel rounded-pill px-4 shadow-sm d-flex align-items-center" style="font-weight: 500;">
        <i class="bi bi-arrow-left me-2"></i> Back
    </a>
</div>

<div class="glass-panel p-4 mb-4 shadow-sm position-relative overflow-hidden">
    <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #34c759, #007aff);"></div>
    
    <div class="row align-items-center">
        <div class="col-md-5 mb-3 mb-md-0">
            <div class="text-muted small text-uppercase fw-semibold mb-1"><i class="bi bi-person-badge me-1 text-primary"></i> Borrower Profile</div>
            <h4 class="fw-bold text-dark mb-1">{{ $loan->borrower->name }}</h4>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 mb-2">{{ $loan->borrower->office->name }}</span>
            
            <div class="mt-2">
                <span class="small text-muted fw-semibold">Co-Maker:</span>
                <span class="small text-dark fw-medium ms-1">{{ $loan->borrower->co_maker ? $loan->borrower->co_maker : 'None' }}</span>
            </div>
        </div>
        
       <div class="col-md-3 mb-3 mb-md-0 text-md-center border-start border-end" style="border-color: rgba(0,0,0,0.05) !important;">
            <div class="text-muted small text-uppercase fw-semibold mb-1"><i class="bi bi-tag me-1 text-info"></i> Loan Details</div>
            <h5 class="fw-bold text-dark mb-1">{{ $loan->type }}</h5>
            <p class="small text-muted mb-0">Control No: <span class="fw-semibold text-dark">{{ $loan->control_number }}</span></p>
            <p class="small text-muted mb-0">Granted: {{ \Carbon\Carbon::parse($loan->date_of_application)->format('M d, Y') }}</p>
            
            <div class="mt-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                <p class="small text-muted mb-1">Applied Term: <span class="fw-semibold text-dark">{{ $loan->no_of_months }} Months</span></p>
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <span class="small text-muted mb-0">Actual Term:</span>
                    
                    @if(is_null($loan->actual_months))
                        <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-0 text-dark shadow-sm" style="font-size: 0.75rem; font-weight: 600;" data-bs-toggle="modal" data-bs-target="#actualMonthsModal">
                            <i class="bi bi-exclamation-circle me-1"></i> Set Now
                        </button>
                    @else
                        <span class="fw-bold text-success" style="font-size: 0.9rem;">{{ $loan->actual_months }} Months</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle px-1 py-0 shadow-none border-0 ms-1" data-bs-toggle="modal" data-bs-target="#actualMonthsModal" title="Update Term">
                            <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4 text-md-end">
            <div class="text-muted small text-uppercase fw-semibold mb-1"><i class="bi bi-wallet2 me-1 text-success"></i> Current Balance</div>
            
            @php 
                $total_principal = round($loan->amount_granted, 2);
                
                // Use round() to eliminate floating-point drift from the Collection sum
                if (!is_null($loan->actual_months) && $loan->schedules->isNotEmpty()) {
                    $total_interest = round($loan->schedules->sum('interest_due'), 2);
                } else {
                    $total_interest = round($total_principal * ($loan->interest_rate / 100), 2);
                }

                // Round all subsequent calculations to strictly enforce 2 decimal places
                $total_liability = round($total_principal + $total_interest, 2);

                $paid_principal = round($loan->payments->sum('amount_paid'), 2);
                $paid_interest = round($loan->payments->sum('interest'), 2);
                
                $total_paid = round($paid_principal + $paid_interest, 2);

                $bal = round($total_liability - $total_paid, 2);
                
                // Calculate remaining specific amounts
                $rem_principal = max(0, round($total_principal - $paid_principal, 2));
                $rem_interest = max(0, round($total_interest - $paid_interest, 2));
            @endphp

            <h2 class="fw-bold mb-0 {{ $bal > 0 ? 'text-danger' : 'text-success' }}" style="letter-spacing: -0.5px;">
                ₱ {{ number_format($bal, 2) }}
            </h2>
            @if($bal <= 0)
                <span class="badge bg-success rounded-pill px-3 mt-2 shadow-sm"><i class="bi bi-check-circle me-1"></i> Fully Paid</span>
            @else
                <div class="d-flex justify-content-end gap-2 mt-2">
                    <span class="badge bg-light text-dark border shadow-sm px-2 py-1"><span class="text-muted fw-normal me-1">Principal:</span> ₱{{ number_format($rem_principal, 2) }}</span>
                    <span class="badge bg-light text-dark border shadow-sm px-2 py-1"><span class="text-muted fw-normal me-1">Interest:</span> ₱{{ number_format($rem_interest, 2) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>

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
            
            <div class="table-responsive">
                <table class="table table-hover text-center align-middle mb-0">
                    <thead style="background: rgba(0, 122, 255, 0.05); border-bottom: 1px solid rgba(0, 122, 255, 0.1);">
                        <tr class="small text-uppercase text-secondary">
                            <th class="py-3">Seq. No.</th>
                            <th>Period Covered</th>
                            <th>Principal</th>
                            <th>Interest</th>
                            <th>Total</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <tr style="background: rgba(0, 0, 0, 0.02);">
                            <td></td>
                            <td class="text-start ps-4 fw-bold text-secondary">Principal</td>
                            <td></td><td></td><td></td>
                            <td class="fw-bold text-dark">₱ {{ number_format($loan->amount_granted, 2) }}</td>
                        </tr>
                        
                        @forelse($loan->schedules as $index => $sched)
                            <tr>
                                <td class="py-3 text-muted">{{ $index + 1 }}</td>
                                <td class="text-start ps-4 fw-semibold text-dark">
                                    {{ \Carbon\Carbon::parse($sched->period_start)->format('M d') }}-{{ \Carbon\Carbon::parse($sched->period_end)->format('d, Y') }}
                                </td>
                                <td class="text-muted">₱ {{ number_format($sched->principal_due, 4) }}</td>
                                <td class="text-muted">₱ {{ number_format($sched->interest_due, 4) }}</td>
                                <td class="fw-medium text-primary">₱ {{ number_format($sched->total_due, 4) }}</td>
                                <td class="fw-bold text-dark">
                                    {{ ($index + 1) % 2 == 0 ? '₱ ' . number_format($sched->balance_after, 2) : '' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-5 text-center text-muted">
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
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
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
                            <th class="py-3 text-start ps-4">Date Paid & Encoder</th>
                            <th>OR Number</th>
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
                            @php $runBal -= ($pay->amount_paid + $pay->interest); @endphp
                            <tr>
                                <td class="py-3 text-start ps-4">
                                    <!-- THIS IS THE NEW AUDIT TRAIL DISPLAY -->
                                    <div class="small mt-1" style="font-size: 0.75rem; line-height: 1.4;">
                                        <div class="text-dark fw-bold">{{ \Carbon\Carbon::parse($pay->payment_date)->format('M d, Y') }}</div>
                                        <div class="text-secondary">
                                            Created: <span class="fw-semibold text-dark">{{ $pay->creator->name ?? 'System' }}</span>
                                        </div>
                                        @if($pay->updated_by)
                                            <div class="text-primary">
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
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#editPaymentModal{{ $pay->id }}" class="btn btn-sm btn-outline-primary border-0 rounded-circle shadow-sm bg-white" title="Edit OR Number">
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

    <!-- ACTUAL MONTHS MODAL -->
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
                            $maxTerm = ($loan->type === 'REGULAR SALARY LOAN') ? 36 : 36;
                        @endphp

                        <div class="form-inner-panel p-3 text-center mb-3">
                            <label class="small text-secondary mb-2 fw-semibold">Actual Repayment Duration (Months)</label>
                            <input type="number" name="actual_months" id="actual_months_input" class="form-control glass-input text-center fw-bold fs-4 text-primary w-50 mx-auto" value="{{ old('actual_months', $loan->actual_months ?? $loan->no_of_months) }}" min="1" max="{{ $maxTerm }}" required>
                            <div class="invalid-feedback mt-2" id="actual_months_error">
                                Term cannot exceed {{ $maxTerm }} months for {{ $loan->type }}.
                            </div>
                        </div>

                        <!-- NEW: Warning Box if payments exist -->
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
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">Save Term</button>
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
                                    <label class="small text-secondary mb-1 fw-semibold">OR Number <span class="text-danger">*</span></label>
                                    <input type="text" name="or_number" id="or_number" class="form-control glass-input px-3 py-2" placeholder="e.g. 123456" required>
                                    <div class="invalid-feedback">OR Number is required.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-secondary mb-1 fw-semibold">Date Paid <span class="text-danger">*</span></label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control glass-input px-3 py-2" value="{{ date('Y-m-d') }}" required>
                                    <div class="invalid-feedback">Payment date is required.</div>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <label class="small text-secondary mb-1 fw-semibold text-primary">Select Billing Month <span class="text-danger">*</span></label>
                                    @php
                                        // Group schedules by month and track paid periods
                                        $groupedSchedules = $loan->schedules->groupBy(function($s) { return \Carbon\Carbon::parse($s->period_end)->format('Y-m'); });
                                        $paidPeriods = $loan->payments->pluck('period_covered')->toArray();
                                        $nextUnpaidFound = false;
                                    @endphp
                                    
                                    <select name="period_covered" id="period_covered" class="form-select glass-input fw-bold px-3 py-2" style="border-color: rgba(0, 122, 255, 0.4) !important;" required onchange="updatePaymentDisplay(this)">
                                        <option value="" disabled selected>Choose a month to pay...</option>
                                        @foreach($groupedSchedules as $monthKey => $monthSchedules)
                                            @php
                                                $principal = $monthSchedules->sum('principal_due');
                                                $interest = $monthSchedules->sum('interest_due');
                                                $total = $principal + $interest;
                                                $label = \Carbon\Carbon::parse($monthKey . '-01')->format('F Y');
                                                
                                                $isPaid = in_array($monthKey, $paidPeriods);
                                                $isDisabled = false;
                                                
                                                if ($isPaid) {
                                                    $isDisabled = true;
                                                    $label .= ' (Already Paid)';
                                                } else if (!$nextUnpaidFound) {
                                                    $nextUnpaidFound = true; // This is the exact next unpaid month! (Enabled)
                                                } else {
                                                    $isDisabled = true; // Future unpaid month (Disabled)
                                                    $label .= ' (Pay previous month first)';
                                                }
                                            @endphp
                                            <option value="{{ $monthKey }}" data-principal="{{ $principal }}" data-interest="{{ $interest }}" data-total="{{ $total }}" {{ $isDisabled ? 'disabled' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a valid billing month.</div>
                                </div>

                                <div class="col-12 mt-3">
                                    <div class="p-3 rounded-4" style="background: rgba(0, 122, 255, 0.05); border: 1px solid rgba(0, 122, 255, 0.2);">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-dark small" style="letter-spacing: 0.5px;">TOTAL AMOUNT DUE</span>
                                            <span class="fs-4 fw-bold text-primary" id="display_total_due">₱ 0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-top border-primary border-opacity-25 pt-2">
                                            <span class="small fw-medium text-secondary">Principal: <strong class="text-dark" id="display_principal_due">₱ 0.00</strong></span>
                                            <span class="small fw-medium text-secondary">Interest: <strong class="text-dark" id="display_interest_due">₱ 0.00</strong></span>
                                        </div>
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
                            <p class="text-muted small mb-3">You can only update the OR Number for this record. Editing will log your name and update the timestamp.</p>
                            
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
</div> <!-- End all-modals-container -->
@endpush

@push('scripts')
<script>
    $(document).ready(function() { 
        // 1. THIS IS THE MAGIC FIX: Move the ENTIRE container of modals to the body
        // This ensures they are completely immune to the Glassmorphism CSS blur.
        let modalsContainer = document.getElementById('all-modals-container');
        if (modalsContainer) {
            document.body.appendChild(modalsContainer);
        }

        // Show Actual Months Modal automatically if missing
        @if(is_null($loan->actual_months))
            var myModal = new bootstrap.Modal(document.getElementById('actualMonthsModal'));
            myModal.show();
        @endif

        // Validation for Actual Months Form
        let actualMonthsForm = document.getElementById('actualMonthsForm');
        if (actualMonthsForm) {
            actualMonthsForm.addEventListener('submit', function(event) {
                let isValid = true;
                let input = document.getElementById('actual_months_input');
                let val = parseInt(input.value) || 0;
                let maxTerm = {{ $loan->type === 'REGULAR SALARY LOAN' ? 36 : 36 }}; 

                if (val < 1 || val > maxTerm) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }

                if (!isValid) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });

            document.getElementById('actual_months_input').addEventListener('input', function() {
                let val = parseInt(this.value) || 0;
                let maxTerm = {{ $loan->type === 'REGULAR SALARY LOAN' ? 36 : 36 }}; 
                if(val >= 1 && val <= maxTerm) {
                    this.classList.remove('is-invalid');
                }
            });
        }
        
        // Intercept Add Payment Form
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
                
                if(document.getElementById('period_covered').value === '') {
                    document.getElementById('period_covered').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('period_covered').classList.remove('is-invalid');
                }

                if (!isValid) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.classList.add('was-validated');
                }
            }, false);
        }
    });

    // Update dynamic amount display when a billing month is chosen
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