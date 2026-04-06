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
</style>

<div class="d-flex justify-content-between mb-4">
    <button type="button" data-bs-toggle="modal" data-bs-target="#deleteLoanModal" class="btn btn-outline-danger glass-panel rounded-pill px-4 shadow-sm d-flex align-items-center" style="font-weight: 500;">
        <i class="bi bi-trash-fill me-2"></i> Delete Loan
    </button>
    
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
            
            @php 
                $total_principal = $loan->amount_granted;
                $total_interest = $total_principal * ($loan->interest_rate / 100);
                $total_liability = $total_principal + $total_interest;

                $paid_principal = $loan->payments->sum('amount_paid');
                $paid_interest = $loan->payments->sum('interest');
                
                $total_paid = $paid_principal + $paid_interest;

                $bal = $total_liability - $total_paid;
                
                // Calculate remaining specific amounts
                $rem_principal = max(0, $total_principal - $paid_principal);
                $rem_interest = max(0, $total_interest - $paid_interest);
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
                @foreach($loan->payments as $pay)
                    @php $runBal -= ($pay->amount_paid + $pay->interest); @endphp
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
                        <td>
                            <button type="button" data-bs-toggle="modal" data-bs-target="#deletePaymentModal{{ $pay->id }}" class="btn btn-sm btn-outline-danger border-0 rounded-circle shadow-sm bg-white" title="Delete Payment">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>
                    
                    <div class="modal fade payment-delete-modal" id="deletePaymentModal{{ $pay->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content glass-modal-content">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold text-danger">Delete Payment Record</h5>
                                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-secondary pb-4 text-start">
                                    Are you sure you want to delete this payment record (OR: {{ $pay->or_number }}) for <strong>₱ {{ number_format($pay->amount_paid, 2) }}</strong>?
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

                @if($loan->payments->isEmpty())
                    <tr>
                        <td colspan="6" class="py-4 text-muted small">No payments have been recorded yet.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

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
                    
                    <div class="p-3 mb-4 rounded-4" style="background: rgba(52, 199, 89, 0.1); border: 1px solid rgba(52, 199, 89, 0.3);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success text-uppercase small" style="letter-spacing: 0.5px;">Total Outstanding</span>
                            <span class="fs-4 fw-bold text-success">₱ {{ number_format($bal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top border-success border-opacity-25 pt-2">
                            <span class="small fw-medium text-success text-opacity-75">Principal: <strong class="text-success">₱ {{ number_format($rem_principal, 2) }}</strong></span>
                            <span class="small fw-medium text-success text-opacity-75">Interest: <strong class="text-success">₱ {{ number_format($rem_interest, 2) }}</strong></span>
                        </div>
                    </div>

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
                            
                            <div class="col-md-6 mt-4">
                                <label class="small text-secondary mb-1 fw-semibold text-primary">Principal Amount <span class="text-danger">*</span></label>
                                <div class="input-group glass-input" id="payment_group" style="padding: 0; overflow: hidden; border-color: rgba(0, 122, 255, 0.4); box-shadow: 0 4px 10px rgba(0, 122, 255, 0.05);">
                                    <span class="input-group-text bg-transparent border-0 text-primary ps-3 pe-2 fw-bold">₱</span>
                                    
                                    <input type="text" id="payment_amount_display" oninput="cleanPaymentCurrencyInput(this)" onblur="formatPaymentCurrencyInput(this)" onfocus="unformatPaymentCurrencyInput(this)" class="form-control bg-transparent border-0 py-2 fw-bold text-primary shadow-none" placeholder="0.00" required>
                                    
                                    <input type="hidden" name="amount_paid" id="payment_amount" value="">
                                </div>
                                <div class="invalid-feedback" id="payment_error" style="display: none;">Enter a valid amount.</div>
                            </div>
                            <div class="col-md-6 mt-4">
                                <label class="small text-secondary mb-1 fw-semibold">Interest Paid ({{ $loan->interest_rate + 0 }}%)</label>
                                <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                    <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2">₱</span>
                                    
                                    <input type="text" id="payment_interest_display" class="form-control bg-transparent border-0 py-2 shadow-none" value="0.00" readonly>
                                    <input type="hidden" name="interest" id="payment_interest" value="0">
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
    const maxPrincipal = {{ $rem_principal }};

    $(document).ready(function() { 
        // Ensure all modals are moved to body to avoid z-index layering issues with glassmorphism
        $('#addPaymentModal').appendTo('body');
        $('#deleteLoanModal').appendTo('body');
        $('.payment-delete-modal').appendTo('body');

        // Custom Validations for Payment Form on Submit
        document.getElementById('paymentForm').addEventListener('submit', function(event) {
            let isValid = true;
            
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                isValid = false;
            }

            let amount = parseFloat(document.getElementById('payment_amount').value) || 0;
            
            if (amount <= 0) {
                document.getElementById('payment_amount_display').classList.add('is-invalid');
                document.getElementById('payment_group').classList.add('invalid-group');
                document.getElementById('payment_error').innerText = "Payment amount must be greater than 0.";
                document.getElementById('payment_error').style.display = 'block';
                isValid = false;
            } else if (amount > maxPrincipal && maxPrincipal > 0) {
                document.getElementById('payment_amount_display').classList.add('is-invalid');
                document.getElementById('payment_group').classList.add('invalid-group');
                document.getElementById('payment_error').innerText = "Amount cannot exceed remaining principal (₱" + maxPrincipal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ").";
                document.getElementById('payment_error').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('payment_amount_display').classList.remove('is-invalid');
                document.getElementById('payment_group').classList.remove('invalid-group');
                document.getElementById('payment_error').style.display = 'none';
            }

            if(document.getElementById('or_number').value.trim() === '') {
                document.getElementById('or_number').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('or_number').classList.remove('is-invalid');
            }

            if(document.getElementById('payment_date').value.trim() === '') {
                document.getElementById('payment_date').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('payment_date').classList.remove('is-invalid');
            }

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                this.classList.add('was-validated');
            }
        }, false);
    });

    // Formatting Functions for Payment Inputs
    function cleanPaymentCurrencyInput(input) {
        let val = input.value.replace(/[^0-9.]/g, '');
        let parts = val.split('.');
        if (parts.length > 2) {
            parts.pop();
            val = parts.join('.');
        }
        
        let numVal = parseFloat(val) || 0;
        
        document.getElementById('payment_amount').value = val; 
        input.value = val;
        
        if (numVal > 0 && numVal <= maxPrincipal) {
            input.classList.remove('is-invalid');
            document.getElementById('payment_group').classList.remove('invalid-group');
            document.getElementById('payment_error').style.display = 'none';
        } else if (numVal > maxPrincipal) {
            input.classList.add('is-invalid');
            document.getElementById('payment_group').classList.add('invalid-group');
            document.getElementById('payment_error').innerText = "Amount cannot exceed remaining principal (₱" + maxPrincipal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ").";
            document.getElementById('payment_error').style.display = 'block';
        }

        calculatePaymentInterest();
    }

    function formatPaymentCurrencyInput(input) {
        let val = parseFloat(input.value);
        if (!isNaN(val) && val > 0) {
            input.value = val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    function unformatPaymentCurrencyInput(input) {
        let val = input.value.replace(/,/g, '');
        input.value = val;
    }

    function calculatePaymentInterest() {
        let amount = parseFloat(document.getElementById('payment_amount').value) || 0;
        let rate = {{ $loan->interest_rate ?? 0 }} / 100; 
        let interest = amount * rate;
        
        document.getElementById('payment_interest_display').value = interest.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('payment_interest').value = interest.toFixed(2);
    }
</script>
@endpush
@endsection