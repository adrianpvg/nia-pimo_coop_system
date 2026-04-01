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
                            <td class="text-muted">₱{{ number_format($loan->interest_rate, 2) }}</td>
                            <td class="text-muted">₱{{ number_format($loan->surcharge, 2) }}</td>
                            <td class="fw-bold text-success">₱{{ number_format($loan->net_proceeds, 2) }}</td>
                            <td class="text-muted">{{ $loan->no_of_months }}</td>
                            <td class="text-muted">{{ $loan->payment_start }}</td>
                            <td class="text-muted">{{ $loan->payment_end }}</td>
                            <td>
                                <a href="{{ route('finance.show', $loan->id) }}" class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm" style="background: rgba(255,255,255,0.8);">
                                    View
                                </a>
                            </td>
                        </tr>
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
            <form action="{{ route('finance.store') }}" method="POST">
                @csrf
                
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h4 class="modal-title fw-bold" style="background: linear-gradient(90deg, #007aff, #34c759); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        New Loan Application
                    </h4>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body px-4 py-4">
                    
                    @if($type === 'ALL' || !$type)
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="fw-semibold text-secondary small mb-1 px-1">Select Loan Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select glass-input fw-bold px-3 py-2" required>
                                    <option value="" disabled selected>Choose loan category...</option>
                                    <option value="REGULAR SALARY LOAN">Regular Salary Loan</option>
                                    <option value="SPECIAL LOAN">Special Loan</option>
                                    <option value="CASAB">CASAB Loan</option>
                                </select>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="type" value="{{ $type }}">
                    @endif

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="form-inner-panel p-4">
                                <h6 class="fw-bold mb-4 text-dark border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                                    <i class="bi bi-person-badge me-2 text-primary"></i>Applicant Details
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="small text-secondary mb-1 fw-semibold">Date of Application <span class="text-danger">*</span></label>
                                        <input type="date" name="date_of_application" id="date_applied" class="form-control glass-input px-3 py-2" value="{{ date('Y-m-d') }}" onchange="syncDate()" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="small text-secondary mb-1 fw-semibold">Office <span class="text-danger">*</span></label>
                                        <select name="office_name" class="form-select glass-input px-3 py-2" required>
                                            @foreach($availableOffices as $off)
                                                <option value="{{ $off }}">{{ $off }}</option>
                                            @endforeach
                                            @if(empty($availableOffices))
                                                <option value="PIMO">PIMO</option>
                                                <option value="RO1">RO1</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="small text-secondary mb-1 fw-semibold">Name of Applicant <span class="text-danger">*</span></label>
                                        <input type="text" name="borrower_name" class="form-control glass-input px-3 py-2" placeholder="Enter Full Name" required>
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
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; border-color: rgba(0, 122, 255, 0.4) !important; box-shadow: 0 4px 10px rgba(0, 122, 255, 0.05);">
                                            <span class="input-group-text bg-transparent border-0 text-primary ps-3 pe-2 fw-bold fs-5">₱</span>
                                            <input type="number" step="0.01" name="amount_granted" id="amount" class="form-control bg-transparent border-0 py-3 fw-bold fs-4 text-primary shadow-none" oninput="calculateAll()" placeholder="0.00" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="small text-secondary mb-1 fw-semibold">Payment Start <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_start" id="start_date" class="form-control glass-input px-3 py-2" onchange="calculateFromDates()" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-secondary mb-1 fw-semibold">Payment End <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_end" id="end_date" class="form-control glass-input px-3 py-2" onchange="calculateFromDates()" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-secondary mb-1 fw-semibold text-primary">Duration (Months) <i class="bi bi-pencil-square ms-1 small"></i></label>
                                        <input type="number" name="no_of_months" id="months" class="form-control glass-input px-3 py-2 fw-bold text-center border-primary" style="background: rgba(0, 122, 255, 0.05) !important;" oninput="calculateFromMonths()" min="1">
                                    </div>

                                    <div class="col-md-4 mt-3">
                                        <label class="small text-secondary mb-1 fw-semibold">Service Fee (0.5%)</label>
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                            <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-1 small">₱</span>
                                            <input type="number" step="0.01" name="service_fee" id="service_fee" class="form-control bg-transparent border-0 py-2 shadow-none" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-3">
                                        <label class="small text-secondary mb-1 fw-semibold">Interest (9%)</label>
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                            <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-1 small">₱</span>
                                            <input type="number" step="0.01" name="interest" id="interest" class="form-control bg-transparent border-0 py-2 shadow-none" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-3">
                                        <label class="small text-secondary mb-1 fw-semibold">Surcharge</label>
                                        <div class="input-group glass-input" style="padding: 0; overflow: hidden; background: rgba(0,0,0,0.02) !important;">
                                            <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-1 small">₱</span>
                                            <input type="number" step="0.01" name="surcharge" id="surcharge" class="form-control bg-transparent border-0 py-2 shadow-none" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <div class="net-proceeds-panel p-2 px-3 d-flex justify-content-between align-items-center">
                                            <div class="text-dark fw-bold" style="font-size: 0.95rem;">Net Amount</div>
                                            
                                            <input type="text" id="net_proceeds_display" class="form-control-plaintext text-end fw-bold text-success p-0 w-100 bg-transparent" style="font-size: 1.4rem;" readonly value="₱ 0.00">
                                            <input type="hidden" name="net_proceeds" id="net_proceeds_actual" value="0">
                                            
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div> 
                </div>
                
                <div class="modal-footer border-top-0 pt-2 px-4 pb-4 mt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7);">Cancel Application</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-5 shadow-sm fw-bold">Submit Loan Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() { 
        $('#createLoanModal').appendTo('body');

        // Only init DataTables if the table is actually rendered in the DOM
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
        
        syncDate(); 
        
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
                    maintainAspectRatio: false, // Allows the fixed 260x260px wrapper to dictate size
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 20, usePointStyle: true, boxWidth: 8 }
                        }
                    }
                }
            });
        }
    });

    function syncDate() {
        let appliedDate = document.getElementById('date_applied').value;
        if (appliedDate) {
            document.getElementById('start_date').value = appliedDate;
            calculateFromDates(); 
        }
    }

    function calculateFromDates() {
        let startInput = document.getElementById('start_date').value;
        let endInput = document.getElementById('end_date').value;
        let months = 0;

        if (startInput && endInput) {
            let start = new Date(startInput);
            let end = new Date(endInput);

            if (end >= start) {
                months = (end.getFullYear() - start.getFullYear()) * 12;
                months -= start.getMonth();
                months += end.getMonth();
                
                document.getElementById('months').value = months > 0 ? months : 0;
            }
        }
        calculateAll();
    }

    function calculateFromMonths() {
        let startInput = document.getElementById('start_date').value;
        let monthsInput = parseInt(document.getElementById('months').value) || 0;

        if (startInput && monthsInput >= 0) {
            let start = new Date(startInput);
            
            start.setMonth(start.getMonth() + monthsInput);
            
            let year = start.getFullYear();
            let month = String(start.getMonth() + 1).padStart(2, '0');
            let day = String(start.getDate()).padStart(2, '0');
            
            document.getElementById('end_date').value = `${year}-${month}-${day}`;
        }
        calculateAll();
    }

    function calculateAll() {
        let amount = parseFloat(document.getElementById('amount').value) || 0;
        let months = parseInt(document.getElementById('months').value) || 0;

        let serviceFee = amount * 0.005;
        let interest = amount * 0.09;
        
        let surcharge = 0;
        if (months > 0) {
            surcharge = amount * 0.0011 * months;
        }

        document.getElementById('service_fee').value = serviceFee.toFixed(2);
        document.getElementById('interest').value = interest.toFixed(2);
        document.getElementById('surcharge').value = surcharge.toFixed(2);

        let net = amount - (serviceFee + surcharge);
        
        if(net < 0) net = 0;

        // Injects Peso sign directly into the JS string output for a cohesive look
        document.getElementById('net_proceeds_display').value = '₱ ' + net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('net_proceeds_actual').value = net.toFixed(2);
    }
</script>
@endpush
@endsection