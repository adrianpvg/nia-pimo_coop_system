@extends('layouts.app')

@section('content')

<style>
    /* =========================================
       GLASSMORPHISM THEME STYLES
       ========================================= */
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

    /* MISSING CSS ADDED: Inner Form Panel for consistent nesting */
    .form-inner-panel {
        background: rgba(255, 255, 255, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    /* Table Hover & Search overrides for Glass UI */
    table.dataTable.table-hover > tbody > tr:hover > *,
    .table-hover > tbody > tr:hover > * {
        box-shadow: inset 0 0 0 9999px rgba(0, 122, 255, 0.05);
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.1);
        padding: 0.25rem 0.5rem;
        background: rgba(255,255,255,0.5);
    }
</style>

<div class="d-flex justify-content-end mb-4">
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
        </div>
        
        <div class="col-md-4 text-md-end">
            <div class="text-muted small text-uppercase fw-semibold mb-1"><i class="bi bi-wallet2 me-1 text-success"></i> Current Balance</div>
            @php $bal = $loan->amount_granted - $loan->payments->sum('amount_paid'); @endphp
            <h2 class="fw-bold mb-0 {{ $bal > 0 ? 'text-danger' : 'text-success' }}" style="letter-spacing: -0.5px;">
                ₱ {{ number_format($bal, 2) }}
            </h2>
            @if($bal <= 0)
                <span class="badge bg-success rounded-pill px-3 mt-2 shadow-sm"><i class="bi bi-check-circle me-1"></i> Fully Paid</span>
            @endif
        </div>
    </div>
</div>

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
                    <th class="py-3">Date Paid</th>
                    <th>OR Number</th>
                    <th>Principal Paid</th>
                    <th>Interest</th>
                    <th>Running Balance</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <tr style="background: rgba(52, 199, 89, 0.05);">
                    <td colspan="4" class="text-start ps-4 fw-bold text-success"><i class="bi bi-cash-stack me-2"></i>LOAN GRANTED</td>
                    <td class="fw-bold text-success fs-5">₱ {{ number_format($loan->amount_granted, 2) }}</td>
                </tr>
                
                @php $runBal = $loan->amount_granted; @endphp
                @foreach($loan->payments as $pay)
                    @php $runBal -= $pay->amount_paid; @endphp
                    <tr>
                        <td class="py-3 text-muted">{{ \Carbon\Carbon::parse($pay->payment_date)->format('M d, Y') }}</td>
                        <td class="fw-semibold text-dark">{{ $pay->or_number }}</td>
                        <td class="text-success fw-medium">₱ {{ number_format($pay->amount_paid, 2) }}</td>
                        <td class="text-muted">₱ {{ number_format($pay->interest, 2) }}</td>
                        <td class="fw-bold {{ $runBal <= 0 ? 'text-success' : 'text-danger' }}">
                            @if($runBal <= 0)
                                <i class="bi bi-check-circle-fill me-1"></i> SETTLED
                            @else
                                ₱ {{ number_format($runBal, 2) }}
                            @endif
                        </td>
                    </tr>
                @endforeach

                @if($loan->payments->isEmpty())
                    <tr>
                        <td colspan="5" class="py-4 text-muted small">No payments have been recorded yet.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal-content border-0">
            <form action="{{ route('finance.payment.store') }}" method="POST">
                @csrf
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h4 class="modal-title fw-bold" style="background: linear-gradient(90deg, #007aff, #34c759); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        Record Payment
                    </h4>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body px-4 py-4">
                    
                    <div class="p-3 mb-4 rounded-4" style="background: rgba(52, 199, 89, 0.1); border: 1px solid rgba(52, 199, 89, 0.3);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-success text-uppercase small" style="letter-spacing: 0.5px;">Outstanding Balance</span>
                            <span class="fs-4 fw-bold text-success">₱ {{ number_format($bal, 2) }}</span>
                        </div>
                    </div>

                    <div class="form-inner-panel p-4">
                        <h6 class="fw-bold mb-4 text-dark border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                            <i class="bi bi-receipt me-2 text-primary"></i>Payment Details
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-secondary mb-1 fw-semibold">OR Number <span class="text-danger">*</span></label>
                                <input type="text" name="or_number" class="form-control glass-input px-3 py-2" placeholder="e.g. 123456" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-secondary mb-1 fw-semibold">Date Paid <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control glass-input px-3 py-2" value="{{ date('Y-m-d') }}" required>
                            </div>
                            
                            <div class="col-md-6 mt-4">
                                <label class="small text-secondary mb-1 fw-semibold text-primary">Principal Amount <span class="text-danger">*</span></label>
                                <div class="input-group glass-input" style="padding: 0; overflow: hidden; border-color: rgba(0, 122, 255, 0.4) !important; box-shadow: 0 4px 10px rgba(0, 122, 255, 0.05);">
                                    <span class="input-group-text bg-transparent border-0 text-primary ps-3 pe-2 fw-bold">₱</span>
                                    <input type="number" step="0.01" name="amount_paid" id="payment_amount" oninput="calculatePaymentInterest()" class="form-control bg-transparent border-0 py-2 fw-bold text-primary shadow-none" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6 mt-4">
                                <label class="small text-secondary mb-1 fw-semibold">Interest Paid (9%)</label>
                                <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                    <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2">₱</span>
                                    <input type="number" step="0.01" name="interest" id="payment_interest" class="form-control bg-transparent border-0 py-2 shadow-none" value="0.00" readonly>
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

@push('scripts')
<script>
    $(document).ready(function() { 
        if ($('#addPaymentModal').length) {
            $('#addPaymentModal').appendTo('body');
        }
    });

    function calculatePaymentInterest() {
        let amount = parseFloat(document.getElementById('payment_amount').value) || 0;
        let interest = amount * 0.09;
        document.getElementById('payment_interest').value = interest.toFixed(2);
    }
</script>
@endpush
@endsection