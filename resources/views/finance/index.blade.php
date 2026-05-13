@extends('layouts.app')

@section('content')

<style>
    /* Dashboard Specific Glass Styles */
    .glass-filter {
        background: rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        padding: 0.35rem; 
        border-radius: 50px;
        display: flex;
        width: 100%;
        overflow-x: auto; 
        -ms-overflow-style: none;  
        scrollbar-width: none;  
    }
    
    .glass-filter::-webkit-scrollbar {
        display: none;
    }
    
    .glass-filter .btn {
        border-radius: 50px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        color: var(--text-secondary);
        border: none;
        transition: all 0.3s ease;
        flex: 1; 
        text-align: center;
        white-space: nowrap; 
    }
    
    .glass-filter .btn.active, .glass-filter .btn:hover {
        background: linear-gradient(90deg, rgba(0, 122, 255, 0.8), rgba(52, 199, 89, 0.8));
        color: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* Apple-style Tabs */
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

    /* Glass Form Inputs (General) */
    .glass-input {
        background: rgba(255, 255, 255, 0.5) !important;
        border: 1px solid rgba(255, 255, 255, 0.8);
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
    .glass-input.is-invalid,
    .was-validated .form-select:invalid {
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

    /* DataTables Transparent Overrides */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.1);
        padding: 0.25rem 0.5rem;
        background: rgba(255,255,255,0.5);
    }
    table.dataTable.table-hover > tbody > tr:hover > * {
        box-shadow: inset 0 0 0 9999px rgba(0, 122, 255, 0.05);
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
    
    .form-inner-panel {
        background: rgba(255, 255, 255, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        height: 100%;
    }

    .net-proceeds-panel {
        background: linear-gradient(135deg, rgba(52, 199, 89, 0.15), rgba(40, 167, 69, 0.05));
        border: 2px solid rgba(52, 199, 89, 0.4);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(52, 199, 89, 0.1);
        position: relative;
        overflow: hidden;
    }
    .net-proceeds-panel::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, #34c759, #007aff);
    }

    /* SUMMARY TAB CARDS */
    .metric-card {
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.9);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
    }
    .metric-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .metric-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-primary);
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            {{ $type === 'ALL' || !$type ? 'COOP Overview' : $type}}
        </h2>
    </div>
    
    <div class="d-flex gap-2 flex-wrap"> 
        
        <form method="GET" action="{{ route('finance.index', ['type' => $type ?? 'ALL']) }}" id="filterForm" class="m-0 d-flex align-items-center glass-panel px-2 py-1 shadow-sm" style="border-radius: 12px; border: 1px solid rgba(0, 122, 255, 0.3);">
            <i class="bi bi-funnel-fill text-primary ms-2 me-1"></i>
            
            <select name="year" class="form-select form-select-sm bg-transparent border-0 fw-bold text-dark shadow-none cursor-pointer" onchange="document.getElementById('filterForm').submit();" style="min-width: 80px; cursor: pointer;">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            
            <div class="vr mx-1 bg-secondary opacity-25" style="width: 2px;"></div>
            
            <select name="office" class="form-select form-select-sm bg-transparent border-0 fw-bold text-dark shadow-none cursor-pointer" onchange="document.getElementById('filterForm').submit();" style="min-width: 110px; cursor: pointer;">
                <option value="ALL" {{ $officeFilter === 'ALL' ? 'selected' : '' }}>Office</option>
                @foreach($availableOffices as $off)
                    <option value="{{ $off }}" {{ $officeFilter === $off ? 'selected' : '' }}>{{ $off }}</option>
                @endforeach
            </select>
        </form>

        <button class="btn btn-dark shadow-sm px-4" style="border-radius: 12px;" data-bs-toggle="modal" data-bs-target="#createLoanModal">
            <i class="bi bi-plus-lg me-2"></i> New Application
        </button>

        <a href="{{ route('finance.export', ['type' => $type ?? 'ALL', 'year' => $year, 'office' => $officeFilter]) }}" class="btn btn-outline-secondary glass-panel px-4" style="border-radius: 12px;">
            <i class="bi bi-cloud-arrow-down me-2"></i> Export
        </a>
    </div>
</div>

<div class="mb-4">
    <div class="glass-filter shadow-sm">
        <a href="{{ route('finance.index', ['type' => 'ALL', 'year' => $year, 'office' => $officeFilter]) }}" class="btn {{ ($type === 'ALL' || !$type) ? 'active' : '' }}">Overview</a>
        <a href="{{ route('finance.index', ['type' => 'REGULAR SALARY LOAN', 'year' => $year, 'office' => $officeFilter]) }}" class="btn {{ $type === 'REGULAR SALARY LOAN' ? 'active' : '' }}">Regular</a>
        <a href="{{ route('finance.index', ['type' => 'SPECIAL LOAN', 'year' => $year, 'office' => $officeFilter]) }}" class="btn {{ $type === 'SPECIAL LOAN' ? 'active' : '' }}">Special</a>
        <a href="{{ route('finance.index', ['type' => 'CASAB', 'year' => $year, 'office' => $officeFilter]) }}" class="btn {{ $type === 'CASAB' ? 'active' : '' }}">CASAB</a>
    </div>
</div>

<ul class="nav nav-tabs apple-tabs mb-4" id="myTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="ops-tab" data-bs-toggle="tab" data-bs-target="#ops-pane">Operations</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-pane">Summary Report</button>
    </li>
</ul>

<div class="tab-content glass-panel p-4 shadow-sm" style="border-radius: 16px;">
    
    <div class="tab-pane fade show active" id="ops-pane">
        @if($loans->count() > 0)
            <div class="table-responsive">
                <table id="loansTable" class="table table-hover align-middle text-nowrap" style="width:100%">
                    <thead style="border-bottom: 2px solid rgba(0,0,0,0.05);">
                        <tr class="text-secondary small text-uppercase">
                            <th>Control No.</th> 
                            <th>App. Date</th>
                            <th>Office</th>
                            <th>Name</th>
                            <th>Co-Maker</th>
                            <th>Type</th> 
                            <th>Principal</th>
                            <th>Service Fee</th>
                            <th>Interest</th>
                            <th>Surcharge</th>
                            <th>Net Amount</th>
                            <th>Months</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @foreach($loans as $loan)
                        <tr>
                            <td class="fw-semibold text-primary">{{ $loan->control_number }}</td>
                            <td class="text-muted">{{ $loan->date_of_application }}</td>
                            <td class="text-muted">{{ $loan->borrower->office->name ?? 'N/A' }}</td>
                            <td class="fw-bold">{{ $loan->borrower->name }}</td>
                            <td class="small text-muted">{{ $loan->borrower->co_maker ?? '-' }}</td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill">
                                    {{ $loan->type ?? 'Loan' }}
                                </span>
                            </td>
                            <td class="fw-medium">₱{{ number_format($loan->amount_granted, 2) }}</td>
                            <td class="text-muted">₱{{ number_format($loan->service_fee, 2) }}</td>
                            <td class="text-muted">₱{{ number_format($loan->interest_rate*.01*$loan->amount_granted, 2) }}</td>
                            <td class="text-muted">₱{{ number_format($loan->surcharge, 2) }}</td>
                            <td class="fw-bold text-success">₱{{ number_format($loan->net_proceeds, 2) }}</td>
                            <td class="text-muted">
                                {{ fmod($loan->no_of_months, 1) !== 0.00 ? number_format($loan->no_of_months, 2) : round($loan->no_of_months) }}
                            </td>
                            <td class="text-muted">{{ $loan->payment_start }}</td>
                            <td class="text-muted">{{ $loan->payment_end }}</td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <a href="{{ route('finance.show', $loan->id) }}" class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm" style="background: rgba(255,255,255,0.8);">
                                        View
                                    </a>
                                    
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#deleteLoanModal{{ $loan->id }}" class="btn btn-sm btn-outline-danger border-0 rounded-circle shadow-sm bg-white" title="Delete Loan Record">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade loan-delete-modal" id="deleteLoanModal{{ $loan->id }}" tabindex="-1" aria-hidden="true" style="white-space: normal;">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content glass-modal-content">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold text-danger">Delete Loan Application</h5>
                                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-secondary pb-4 text-start">
                                        Are you sure you want to completely delete the loan application for <strong class="text-dark">{{ $loan->borrower->name }}</strong> (Control No: {{ $loan->control_number }})?<br><br>
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

                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-folder-x display-1 text-secondary opacity-50 mb-3 d-block"></i>
                <h5 class="fw-bold text-dark">No Records Found</h5>
                <p class="text-muted">There are no applications matching your selected filters for this view.</p>
            </div>
        @endif
    </div>

    <div class="tab-pane fade" id="summary-pane">
        
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="metric-card">
                    <span class="metric-title"><i class="bi bi-file-earmark-text text-primary me-2"></i> Total Records</span>
                    <span class="metric-value">{{ number_format($summary['total_loans']) }}</span>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="metric-card">
                    <span class="metric-title"><i class="bi bi-cash text-success me-2"></i> Total Principal</span>
                    <span class="metric-value text-success">₱{{ number_format($summary['total_principal'], 2) }}</span>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="metric-card">
                    <span class="metric-title"><i class="bi bi-bank text-info me-2"></i> Net Amount</span>
                    <span class="metric-value">₱{{ number_format($summary['total_net'], 2) }}</span>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="metric-card">
                    <span class="metric-title"><i class="bi bi-exclamation-circle text-danger me-2"></i> Total Outstanding</span>
                    <span class="metric-value text-danger">₱{{ number_format($summary['total_balance'], 2) }}</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="metric-card h-100 text-center d-flex flex-column justify-content-center align-items-center p-4">
                    @if(!empty($summary['chart_data']))
                        <div style="position: relative; height: 260px; width: 260px; margin: 0 auto;">
                            <canvas id="loanDistributionChart"></canvas>
                        </div>
                    @else
                        <div class="text-muted p-4">
                            <i class="bi bi-pie-chart fs-1 mb-2 d-block text-secondary opacity-50"></i>
                            <h6 class="fw-bold">No Data Available</h6>
                            <small>No records exist for this filter.</small>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="metric-card h-100 p-0 overflow-hidden">
                    <div class="table-responsive m-0">
                        <table class="table table-hover table-sm text-center align-middle m-0">
                            
                            @if($type === 'ALL')
                                <thead style="background: rgba(0, 122, 255, 0.05); border-bottom: 1px solid rgba(0, 122, 255, 0.1);">
                                    <tr class="small text-uppercase text-secondary">
                                        <th class="py-3 text-start ps-4">Loan Category</th>
                                        <th>Total Principal</th>
                                        <th>Total Net Amount</th>
                                        <th>Total Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($summary['table_data'] as $loanType => $data)
                                    <tr>
                                        <td class="text-start fw-bold text-dark ps-4 py-3">{{ $loanType }}</td>
                                        <td class="fw-medium text-primary">₱{{ number_format($data['principal'], 2) }}</td>
                                        <td class="fw-medium text-success">₱{{ number_format($data['net'], 2) }}</td>
                                        <td class="fw-bold {{ $data['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                            ₱{{ number_format($data['balance'], 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="py-4 text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            @else
                                <thead style="background: rgba(0, 122, 255, 0.05); border-bottom: 1px solid rgba(0, 122, 255, 0.1);">
                                    <tr class="small text-uppercase text-secondary">
                                        <th class="py-3">No.</th>
                                        <th>Name</th>
                                        <th>Period</th>
                                        <th>Principal</th>
                                        <th>Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($loans as $key => $loan)
                                    <tr>
                                        <td class="text-muted py-3">{{ $key + 1 }}</td>
                                        <td class="text-start fw-bold text-dark">{{ $loan->borrower->name }}</td>
                                        <td class="small text-muted">{{ \Carbon\Carbon::parse($loan->payment_end)->format('M Y') }}</td>
                                        <td class="text-end fw-medium">₱{{ number_format($loan->amount_granted, 2) }}</td>
                                        <td class="text-end fw-bold {{ $loan->balance > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $loan->balance > 0 ? '₱'.number_format($loan->balance, 2) : 'Settled' }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="py-4 text-muted">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            @endif

                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="createLoanModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content glass-modal-content border-0">
            <form action="{{ route('finance.store') }}" method="POST" id="loanForm" novalidate>
                @csrf
                
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h4 class="modal-title fw-bold" style="background: linear-gradient(90deg, #007aff, #34c759); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        New Loan Application
                    </h4>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body px-4 py-4">
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="fw-semibold text-secondary small mb-1 px-1">Select Loan Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select glass-input fw-bold px-3 py-2" required>
                                <option value="" disabled selected>Choose loan category...</option>
                                <option value="REGULAR SALARY LOAN">Regular Salary Loan</option>
                                <option value="SPECIAL LOAN">Special Loan</option>
                                <option value="CASAB">CASAB Loan</option>
                            </select>
                            <div class="invalid-feedback ps-2">Please select a loan type.</div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="form-inner-panel p-4">
                                <h6 class="fw-bold mb-4 text-dark border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                                    <i class="bi bi-person-badge me-2 text-primary"></i>Applicant Details
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="small text-secondary mb-1 fw-semibold">Date of Application <span class="text-danger">*</span></label>
                                        <input type="date" name="date_of_application" id="date_applied" class="form-control glass-input px-3 py-2" value="{{ date('Y-m-d') }}" min="2026-01-01" onchange="syncDate()" required>
                                        <div class="invalid-feedback">Date must be from 2026 onwards.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="small text-secondary mb-1 fw-semibold">Office <span class="text-danger">*</span></label>
                                        <select name="office_name" class="form-select glass-input px-3 py-2" required>
                                            <option value="" disabled selected>Choose an office...</option>
                                            
                                            @foreach($availableOffices as $off)
                                                <option value="{{ $off }}">{{ $off }}</option>
                                            @endforeach
                                            
                                            @if(empty($availableOffices))
                                                <option value="ASRIS">PIMO</option>
                                                <option value="ADRIS">PIMO</option>
                                                <option value="LARIS">PIMO</option>
                                                <option value="PIMO">PIMO</option>
                                                <option value="RO1">RO1</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="small text-secondary mb-1 fw-semibold">Name of Applicant <span class="text-danger">*</span></label>
                                        <input type="text" name="borrower_name" class="form-control glass-input px-3 py-2" placeholder="Enter Full Name" required>
                                        <div class="invalid-feedback">Applicant name is required.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="small text-secondary mb-1 fw-semibold">Name of Co-Maker</label>
                                        <input type="text" name="co_maker" class="form-control glass-input px-3 py-2" placeholder="Optional">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="form-inner-panel p-4">
                                <h6 class="fw-bold mb-4 text-dark border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                                    <i class="bi bi-cash-stack me-2 text-success"></i>Financial Breakdown
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-12 mb-2">
                                        <label class="small text-secondary mb-1 fw-semibold">Principal Amount Granted <span class="text-danger">*</span></label>
                                        <div class="input-group glass-input" id="amount_group" style="padding: 0; overflow: hidden; border-color: rgba(0, 122, 255, 0.4); box-shadow: 0 4px 10px rgba(0, 122, 255, 0.05);">
                                            <span class="input-group-text bg-transparent border-0 text-primary ps-3 pe-2 fw-bold fs-5">₱</span>
                                            
                                            <input type="text" id="amount_display" class="form-control bg-transparent border-0 py-3 fw-bold fs-4 text-primary shadow-none" 
                                                   placeholder="0.00" 
                                                   oninput="cleanCurrencyInput(this)" 
                                                   onblur="formatCurrencyInput(this)"
                                                   onfocus="unformatCurrencyInput(this)" required>
                                            <input type="hidden" name="amount_granted" id="amount" value="0">
                                        </div>
                                        <div class="invalid-feedback" id="amount_error" style="display: none;">Amount must be greater than zero.</div>
                                    </div>

                                    <!-- CASAB BONUS SELECTION -->
                                    <div class="col-md-4" id="casab_date_col" style="display: none;">
                                        <label class="small text-secondary mb-1 fw-semibold text-success">Select Bonus Deduction Date <span class="text-danger">*</span></label>
                                        <select id="casab_date_select" class="form-select glass-input px-3 py-2 fw-bold text-success border-success" onchange="syncCasabDate()"></select>
                                    </div>

                                    <div class="col-md-4" id="start_date_col">
                                        <label class="small text-secondary mb-1 fw-semibold">Payment Start <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_start" id="start_date" class="form-control glass-input px-3 py-2" value="{{ date('Y-m-d') }}" min="2026-01-01" onchange="calculateFromDates()" required>
                                        <div class="invalid-feedback">Date must be from 2026 onwards.</div>
                                    </div>
                                    <div class="col-md-4" id="end_date_col">
                                        <label class="small text-secondary mb-1 fw-semibold">Payment End <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_end" id="end_date" class="form-control glass-input px-3 py-2" value="{{ \Carbon\Carbon::now()->addMonths(6)->format('Y-m-d') }}" min="2026-01-01" onchange="calculateFromDates()" required>
                                        <div class="invalid-feedback" id="end_date_error">Date must be logically after Start Date.</div>
                                    </div>
                                    <div class="col-md-4" id="months_col">
                                        <label class="small text-secondary mb-1 fw-semibold text-primary">Duration (Months) <i class="bi bi-pencil-square ms-1 small"></i></label>
                                        <input type="number" name="no_of_months" id="months" class="form-control glass-input px-3 py-2 fw-bold text-center border-primary" style="background: rgba(0, 122, 255, 0.05) !important;" oninput="calculateFromMonths()" min="1" max="36" step="1" required>
                                        <div class="invalid-feedback" id="months_error">Enter a valid term length.</div>
                                    </div>

                                    <div class="col-md-2 mt-3">
                                        <label class="small text-secondary mb-1 fw-semibold">Service <small>(0.5%)</small></label>
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                            <span class="input-group-text bg-transparent border-0 text-muted ps-2 pe-1 small">₱</span>
                                            <input type="text" id="service_fee_display" class="form-control bg-transparent border-0 py-2 px-1 shadow-none" readonly>
                                            <input type="hidden" name="service_fee" id="service_fee">
                                        </div>
                                    </div>

                                    <div class="col-md-2 mt-3">
                                        <label class="small text-secondary mb-1 fw-semibold text-dark">Base Interest</label>
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; border-color: rgba(0,0,0,0.1);">
                                            <input type="number" step="0.01" name="base_interest" id="interest_rate_input" class="form-control bg-transparent border-0 py-2 px-2 shadow-none text-center fw-bold" value="1.5" min="0" oninput="calculateAll()" required>
                                            <span class="input-group-text bg-transparent border-0 text-muted ps-1 pe-3">%</span>
                                        </div>
                                        <div class="invalid-feedback">Enter a valid rate.</div>
                                    </div>

                                    <div class="col-md-2 mt-3">
                                        <label class="small text-secondary mb-1 fw-semibold text-primary">Interest Rate</label>
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                            <input type="text" name="interest_rate" id="total_rate_display" class="form-control bg-transparent border-0 py-2 px-1 shadow-none text-center fw-bold text-primary" readonly>
                                            <span class="input-group-text bg-transparent border-0 text-primary ps-1 pe-2">%</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3 mt-3">
                                        <label class="small text-secondary mb-1 fw-semibold">Interest Amount</label>
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                            <span class="input-group-text bg-transparent border-0 text-muted ps-2 pe-1 small">₱</span>
                                            <input type="text" id="interest_display" class="form-control bg-transparent border-0 py-2 px-1 shadow-none fw-bold text-dark" readonly>
                                            <input type="hidden" name="interest" id="interest">
                                        </div>
                                    </div>

                                    <div class="col-md-3 mt-3">
                                        <label class="small text-secondary mb-1 fw-semibold">Surcharge</label>
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                            <span class="input-group-text bg-transparent border-0 text-muted ps-2 pe-1 small">₱</span>
                                            <input type="text" id="surcharge_display" class="form-control bg-transparent border-0 py-2 px-1 shadow-none" readonly>
                                            <input type="hidden" name="surcharge" id="surcharge">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <div class="net-proceeds-panel p-2 px-3 d-flex justify-content-between align-items-center">
                                            <div class="text-dark fw-bold" style="font-size: 0.95rem;">Net Amount</div>
                                            <input type="text" id="net_proceeds_display" class="form-control-plaintext text-end fw-bold text-success p-0 w-100 bg-transparent" style="font-size: 1.4rem;" readonly value="₱ 0.00">
                                            <input type="hidden" name="net_proceeds" id="net_proceeds_actual" value="0">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <div class="net-proceeds-panel p-2 px-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.15), rgba(0, 122, 255, 0.05)); border: 2px solid rgba(0, 122, 255, 0.4); border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 122, 255, 0.1);">
                                            <div class="text-dark fw-bold" style="font-size: 0.95rem;">Total to Pay</div>
                                            <input type="text" id="total_pay_display" class="form-control-plaintext text-end fw-bold text-primary p-0 w-100 bg-transparent" style="font-size: 1.4rem;" readonly value="₱ 0.00">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div> 
                </div>
                
                <div class="modal-footer border-top-0 pt-2 px-4 pb-4 mt-2 d-flex justify-content-between">
                    <div>
                        <button type="button" onclick="printLoanSchedule()" class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center" style="background: rgba(255,255,255,0.6); border-color: rgba(0, 122, 255, 0.5);">
                            <i class="bi bi-printer-fill me-2"></i> Print Loan Schedule
                        </button>
                    </div>
                    
                    <div>
                        <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm me-2" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7);">Cancel Application</button>
                        <button type="submit" class="btn btn-dark rounded-pill px-5 shadow-sm fw-bold" id="submitBtn">Submit Loan Record</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/loan-printer.js') }}"></script>

<script>
    $(document).ready(function() { 
        $('#createLoanModal').appendTo('body');
        $('.loan-delete-modal').appendTo('body');

        if ($('#loansTable').length) {
            $('#loansTable').DataTable({
                "destroy": true,
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search records..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-end'f>>" +
                       "<'row'<'col-sm-12'tr>>" +
                       "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
        }

        // Reset Form on Modal Close
        $('#createLoanModal').on('hidden.bs.modal', function () {
            $('#loanForm')[0].reset();
            
            $('#loanForm').removeClass('was-validated');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-group').removeClass('invalid-group');
            $('#amount_error').hide();

            $('#amount_display').val('');
            $('#service_fee_display').val('');
            $('#interest_display').val('');
            $('#surcharge_display').val('');
            $('#net_proceeds_display').val('₱ 0.00');
            $('#total_pay_display').val('₱ 0.00');

            updateLoanTypeConstraints();
        });
        
        syncDate(); 

        let loanTypeSelect = document.querySelector('select[name="type"]');
        let appDateInput = document.getElementById('date_applied');

        function addDaysToDateStr(dateStr, days) {
            let d = new Date(dateStr);
            d.setDate(d.getDate() + days);
            let year = d.getFullYear();
            let month = String(d.getMonth() + 1).padStart(2, '0');
            let day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function updateLoanTypeConstraints() {
            if (!loanTypeSelect) return;
            let type = loanTypeSelect.value;
            let monthsInput = document.getElementById('months');
            let monthsCol = document.getElementById('months_col');
            let startDateCol = document.getElementById('start_date_col');
            let endDateCol = document.getElementById('end_date_col');
            let casabDateCol = document.getElementById('casab_date_col');
            let errorText = document.getElementById('months_error');
            
            let appDateStr = appDateInput.value;
            if (!appDateStr) appDateStr = new Date().toISOString().slice(0,10);
            let appDate = new Date(appDateStr);
            let year = appDate.getFullYear();

            if (type === 'CASAB') {
                monthsInput.value = 1;
                
                if (monthsCol) monthsCol.style.display = 'none';
                
                // Show dates but make them readonly
                startDateCol.style.display = 'block';
                endDateCol.style.display = 'block';
                document.getElementById('start_date').setAttribute('readonly', true);
                document.getElementById('end_date').setAttribute('readonly', true);
                document.getElementById('start_date').classList.add('bg-light');
                document.getElementById('end_date').classList.add('bg-light');
                
                // Auto calc CASAB payment start: App Date + 1 Day
                document.getElementById('start_date').value = addDaysToDateStr(appDateStr, 1);

                if(casabDateCol) casabDateCol.style.display = 'block';
                
                let select = document.getElementById('casab_date_select');
                if(select) {
                    select.innerHTML = '';
                    
                    let options = [];
                    let midYear1 = new Date(year, 4, 16); 
                    let yearEnd1 = new Date(year, 10, 16);
                    let midYear2 = new Date(year + 1, 4, 16); 
                    let yearEnd2 = new Date(year + 1, 10, 16);
                    
                    if (midYear1 >= appDate) options.push({ date: midYear1, label: "Mid-Year May 16, " + year });
                    if (yearEnd1 >= appDate) options.push({ date: yearEnd1, label: "Year-End Nov 16, " + year });
                    options.push({ date: midYear2, label: "Mid-Year May 16, " + (year + 1) }); 
                    options.push({ date: yearEnd2, label: "Year-End Nov 16, " + (year + 1) });

                    options.slice(0, 2).forEach(opt => {
                        let yearStr = opt.date.getFullYear();
                        let monthStr = String(opt.date.getMonth() + 1).padStart(2, '0');
                        let dayStr = String(opt.date.getDate()).padStart(2, '0');
                        
                        let element = document.createElement('option');
                        element.value = `${yearStr}-${monthStr}-${dayStr}`;
                        element.text = opt.label;
                        select.appendChild(element);
                    });
                    
                    window.syncCasabDate(); 
                }
                
            } else if (type === 'SPECIAL LOAN') {
                if (monthsCol) monthsCol.style.display = 'block';
                monthsInput.removeAttribute('readonly');
                monthsInput.classList.remove('bg-light');
                monthsInput.setAttribute('max', '6');
                if(errorText) errorText.innerText = "Special loans max term is 6 months.";
                
                if(parseInt(monthsInput.value) > 6) {
                    monthsInput.value = 6;
                }
                
                startDateCol.style.display = 'block';
                endDateCol.style.display = 'block';
                document.getElementById('start_date').removeAttribute('readonly');
                document.getElementById('end_date').removeAttribute('readonly');
                document.getElementById('start_date').classList.remove('bg-light');
                document.getElementById('end_date').classList.remove('bg-light');
                
                if(casabDateCol) casabDateCol.style.display = 'none';
                syncDate();
            } else {
                if (monthsCol) monthsCol.style.display = 'block';
                monthsInput.removeAttribute('readonly');
                monthsInput.classList.remove('bg-light');
                monthsInput.setAttribute('max', '36');
                if(errorText) errorText.innerText = "Regular loans max term is 36 months.";
                
                if(parseInt(monthsInput.value) > 36) {
                    monthsInput.value = 36;
                }
                
                startDateCol.style.display = 'block';
                endDateCol.style.display = 'block';
                document.getElementById('start_date').removeAttribute('readonly');
                document.getElementById('end_date').removeAttribute('readonly');
                document.getElementById('start_date').classList.remove('bg-light');
                document.getElementById('end_date').classList.remove('bg-light');

                if(casabDateCol) casabDateCol.style.display = 'none';
                syncDate();
            }
            calculateAll();
        }

        window.syncCasabDate = function() {
            let select = document.getElementById('casab_date_select');
            if(select && select.value) {
                document.getElementById('end_date').value = select.value;
                calculateAll();
            }
        };

        if(loanTypeSelect) loanTypeSelect.addEventListener('change', updateLoanTypeConstraints);
        if(appDateInput) appDateInput.addEventListener('change', updateLoanTypeConstraints);
        
        updateLoanTypeConstraints();
        
        if(document.getElementById('loanDistributionChart')) {
            const ctx = document.getElementById('loanDistributionChart').getContext('2d');
            const chartDataRaw = @json($summary['chart_data'] ?? []);
            const isOverview = "{{ $type === 'ALL' }}" === "1";
            
            let bgColors = isOverview ? 
                ['rgba(0, 122, 255, 0.8)', 'rgba(52, 199, 89, 0.8)', 'rgba(255, 149, 0, 0.8)'] : 
                ['rgba(52, 199, 89, 0.8)', 'rgba(255, 59, 48, 0.8)'];
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(chartDataRaw),
                    datasets: [{
                        data: Object.values(chartDataRaw),
                        backgroundColor: bgColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 20, usePointStyle: true, boxWidth: 8 }
                        }
                    }
                }
            });
        }

        document.getElementById('loanForm').addEventListener('submit', function(event) {
            let isValid = true;
            
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                isValid = false;
            }

            let amount = parseFloat(document.getElementById('amount').value) || 0;
            if (amount <= 0) {
                document.getElementById('amount_display').classList.add('is-invalid');
                document.getElementById('amount_group').classList.add('invalid-group');
                document.getElementById('amount_error').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('amount_display').classList.remove('is-invalid');
                document.getElementById('amount_group').classList.remove('invalid-group');
                document.getElementById('amount_error').style.display = 'none';
            }

            let type = loanTypeSelect ? loanTypeSelect.value : '';
            let maxTerm = 36;
            if (type === 'SPECIAL LOAN') maxTerm = 6;
            if (type === 'CASAB') maxTerm = 1;

            let months = parseInt(document.getElementById('months').value) || 0;
            if (months < 1 || months > maxTerm) {
                document.getElementById('months').classList.add('is-invalid');
                isValid = false;
            } else {
                document.getElementById('months').classList.remove('is-invalid');
            }

            let dates = ['date_applied', 'start_date', 'end_date'];
            dates.forEach(function(id) {
                let el = document.getElementById(id);
                // Allow specific dynamic skips 
                if (type === 'CASAB' && (id === 'start_date' || id === 'end_date')) return;
                
                if (el.value < '2026-01-01') {
                    el.classList.add('is-invalid');
                    isValid = false;
                } else {
                    el.classList.remove('is-invalid');
                }
            });

            if(!isValid) {
                event.preventDefault();
                event.stopPropagation();
                this.classList.add('was-validated');
            }
        }, false);
    });

    function cleanCurrencyInput(input) {
        let val = input.value.replace(/[^0-9.]/g, '');
        let parts = val.split('.');
        if (parts.length > 2) {
            parts.pop();
            val = parts.join('.');
        }
        
        document.getElementById('amount').value = val || 0;
        input.value = val;
        
        if(parseFloat(val) > 0) {
            input.classList.remove('is-invalid');
            document.getElementById('amount_group').classList.remove('invalid-group');
            document.getElementById('amount_error').style.display = 'none';
        }
        
        calculateAll();
    }

    function formatCurrencyInput(input) {
        let val = parseFloat(input.value);
        if (!isNaN(val) && val > 0) {
            input.value = val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    function unformatCurrencyInput(input) {
        let val = input.value.replace(/,/g, '');
        input.value = val;
    }

    function syncDate() {
        let appliedDate = document.getElementById('date_applied').value;
        if (appliedDate) {
            if(appliedDate < '2026-01-01') {
                document.getElementById('date_applied').value = '2026-01-01';
                appliedDate = '2026-01-01';
            }
            document.getElementById('date_applied').classList.remove('is-invalid');
            
            let typeSelect = document.querySelector('select[name="type"]');
            if (typeSelect && typeSelect.value === 'CASAB') {
                // For CASAB start date is applied date + 1
                document.getElementById('start_date').value = addDaysToDateStr(appliedDate, 1);
                window.syncCasabDate(); 
            } else {
                document.getElementById('start_date').value = appliedDate;
                calculateFromDates(); 
            }
        }
    }

    function calculateFromDates() {
        let startEl = document.getElementById('start_date');
        let endEl = document.getElementById('end_date');
        
        if (startEl.value < '2026-01-01') startEl.value = '2026-01-01';

        startEl.classList.remove('is-invalid');
        endEl.classList.remove('is-invalid');

        let startInput = startEl.value;
        let endInput = endEl.value;

        if (startInput && endInput) {
            let sParts = startInput.split('-');
            let eParts = endInput.split('-');
            
            let sYear = parseInt(sParts[0]);
            let sMonth = parseInt(sParts[1]);
            let sDay = parseInt(sParts[2]);
            
            let eYear = parseInt(eParts[0]);
            let eMonth = parseInt(eParts[1]);
            let eDay = parseInt(eParts[2]);

            let typeSelect = document.querySelector('select[name="type"]');
            if (typeSelect && typeSelect.value === 'CASAB') {
                calculateAll();
                return;
            }

            if (endInput <= startInput) {
                document.getElementById('months').value = 1;
                calculateFromMonths(); 
                return; 
            } else {
                let months = (eYear - sYear) * 12 + (eMonth - sMonth);
                
                if (eDay < sDay) {
                    let eDaysInMonth = new Date(eYear, eMonth, 0).getDate();
                    if (eDay < eDaysInMonth) {
                        months--;
                    }
                }
                
                let maxTerm = 36;
                if (typeSelect && typeSelect.value === 'SPECIAL LOAN') maxTerm = 6;

                if (months < 1) months = 1;
                if (months > maxTerm) months = maxTerm;
                
                document.getElementById('months').value = months;
                document.getElementById('months').classList.remove('is-invalid');
            }
        }
        calculateAll();
    }

    function calculateFromMonths() {
        let startInput = document.getElementById('start_date').value;
        let monthsField = document.getElementById('months');
        
        monthsField.value = monthsField.value.replace(/[^0-9]/g, '');

        if(monthsField.value === "") {
            calculateAll();
            return;
        }

        let monthsInput = parseInt(monthsField.value);
        let typeSelect = document.querySelector('select[name="type"]');
        let maxTerm = 36;

        if (typeSelect) {
            if(typeSelect.value === 'SPECIAL LOAN') maxTerm = 6;
            if(typeSelect.value === 'CASAB') {
                calculateAll();
                return; // Month is locked to 1
            }
        }

        if (monthsInput > maxTerm) {
            monthsField.value = maxTerm;
            monthsInput = maxTerm;
        }

        if (monthsInput < 1) {
            monthsField.value = 1;
            monthsInput = 1;
        }

        if (startInput && monthsInput >= 1) {
            let parts = startInput.split('-');
            let year = parseInt(parts[0]);
            let month = parseInt(parts[1]);
            let day = parseInt(parts[2]);
            
            month += monthsInput;
            year += Math.floor((month - 1) / 12);
            month = ((month - 1) % 12) + 1;
            
            let daysInMonth = new Date(year, month, 0).getDate();
            if (day > daysInMonth) day = daysInMonth;
            
            let endStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            if (endStr < '2026-01-01') endStr = '2026-01-01';
            
            document.getElementById('end_date').value = endStr;
            monthsField.classList.remove('is-invalid');
            document.getElementById('end_date').classList.remove('is-invalid');
        }
        calculateAll();
    }

    function calculateAll() {
        let amount = Math.max(0, parseFloat(document.getElementById('amount').value) || 0);
        let months = Math.max(0, parseInt(document.getElementById('months').value) || 0);
        
        let monthlyRatePercentage = Math.max(0, parseFloat(document.getElementById('interest_rate_input').value) || 0);
        let monthlyRate = monthlyRatePercentage / 100; 

        let serviceFee = amount * 0.005;
        
        let totalExpectedInterest = 0;
        let totalPeriods = months * 2; 
        let halfMonthRate = monthlyRate / 2;
        
        let typeSelect = document.querySelector('select[name="type"]');
        let type = typeSelect ? typeSelect.value : '';

        // DYNAMIC INTEREST CALCULATION
        if (type === 'CASAB') {
            // CASAB FORMULA: Principal * (total days covered / 30) * base_interest
            let appDateStr = document.getElementById('start_date').value;
            let bonusDateStr = document.getElementById('end_date').value;
            
            let appDate = new Date(appDateStr);
            let bonusDate = new Date(bonusDateStr);
            
            let timeDiff = bonusDate.getTime() - appDate.getTime();
            let totalDays = timeDiff > 0 ? Math.ceil(timeDiff / (1000 * 3600 * 24)) : 0;
            
            totalExpectedInterest = amount * (totalDays / 30) * monthlyRate;

        } else if (type === 'SPECIAL LOAN') {
            totalExpectedInterest = amount * monthlyRate * months;
        } else {
            if (amount > 0 && totalPeriods > 0) {
                let basePrincipalDue = amount / totalPeriods;
                let runningBalance = amount;
                let monthlyBalance = amount; 

                for (let i = 1; i <= totalPeriods; i++) {
                    let isFirstHalf = (i % 2 !== 0);
                    let interestDue = monthlyBalance * halfMonthRate;
                    
                    totalExpectedInterest += interestDue;
                    runningBalance -= basePrincipalDue;

                    if (!isFirstHalf) {
                        monthlyBalance = runningBalance;
                    }
                }
            }
        }
        
        let interest = totalExpectedInterest;

        let effectiveRate = 0;
        if (amount > 0) {
            effectiveRate = (interest / amount) * 100;
        }
        
        document.getElementById('total_rate_display').value = effectiveRate.toFixed(2);

        let surcharge = 0;
        if (months > 0) {
            surcharge = amount * 0.0011 * months;
        }

        document.getElementById('service_fee').value = serviceFee.toFixed(2);
        document.getElementById('interest').value = interest.toFixed(2);
        document.getElementById('surcharge').value = surcharge.toFixed(2);

        let net = amount - (serviceFee + surcharge + interest); 
        if(net < 0) net = 0;

        let totalToPay = amount + interest;

        document.getElementById('service_fee_display').value = serviceFee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('interest_display').value = interest.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('surcharge_display').value = surcharge.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        document.getElementById('net_proceeds_display').value = '₱ ' + net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('net_proceeds_actual').value = net.toFixed(2);

        document.getElementById('total_pay_display').value = '₱ ' + totalToPay.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
</script>
@endpush
@endsection