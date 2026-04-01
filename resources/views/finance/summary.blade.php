<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Unit - Summary Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        .table-header-custom { background-color: #ffc107; color: #000; font-weight: bold; }
        .card-custom { border: none; box-shadow: 0 0 20px rgba(0,0,0,0.08); }
        .navbar-brand { font-weight: bold; letter-spacing: 1px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-bank2 me-2"></i> PIMO COOP SYSTEM
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#">Summary</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="card card-custom mb-4">
            <div class="card-body text-center py-4">
                <h2 class="h4 text-uppercase fw-bold text-success mb-1">
                    NIA Region 1 Employees Multi-Purpose Cooperative
                </h2>
                <h3 class="h5 text-muted text-uppercase">
                    Pangasinan Irrigation Management Office
                </h3>
                <div class="mt-3">
                    <span class="badge bg-secondary">
                        <i class="bi bi-calendar-event me-1"></i> As of {{ now()->format('F d, Y') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="m-0 fw-bold text-dark">
                    <i class="bi bi-table me-2 text-warning"></i> Special Loan
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 align-middle">
                        <thead class="table-header-custom text-center text-uppercase small">
                            <tr>
                                <th class="py-3">No.</th>
                                <th class="py-3 text-start">Name of Member</th>
                                <th class="py-3">Office</th>
                                <th class="py-3">Date of Loan</th>
                                <th class="py-3">Period (Start - End)</th>
                                <th class="py-3 text-end">Amount Granted</th>
                                <th class="py-3 text-end">Current Balance</th>
                                <th class="py-3 text-start">Co-Maker</th>
                                <th class="py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loans as $key => $loan)
                            <tr>
                                <td class="text-center fw-bold text-secondary">{{ $key + 1 }}</td>
                                <td class="fw-bold text-primary">{{ $loan->borrower->name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark">{{ $loan->borrower->office->name }}</span>
                                </td>
                                <td class="text-center text-muted">{{ $loan->date_of_loan }}</td>
                                <td class="text-center small">
                                    {{ $loan->payment_start }} <br> 
                                    <i class="bi bi-arrow-down-short"></i> <br> 
                                    {{ $loan->payment_end }}
                                </td>
                                <td class="text-end">₱ {{ number_format($loan->amount_granted, 2) }}</td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $loan->balance > 0 ? 'text-danger' : 'text-success' }}">
                                        ₱ {{ number_format($loan->balance, 2) }}
                                    </span>
                                </td>
                                <td class="fst-italic text-secondary">{{ $loan->borrower->co_maker }}</td>
                                <td class="text-center">
                                    <a href="{{ route('finance.show', $loan->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">No loan records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>