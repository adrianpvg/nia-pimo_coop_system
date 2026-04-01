@extends('layouts.app')

@section('content')

<style>
    /* Glass Panel Styling */
    .glass-panel {
        background: linear-gradient(135deg, rgba(255,255,255,0.7), rgba(255,255,255,0.4));
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        border-radius: 16px;
    }

    /* DataTables Transparent Overrides */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.1);
        padding: 0.25rem 0.5rem;
        background: rgba(255,255,255,0.7);
    }
    table.dataTable.table-hover > tbody > tr:hover > * {
        box-shadow: inset 0 0 0 9999px rgba(0, 122, 255, 0.05);
    }
    
    .agent-text {
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">System Logs</h2>
        <p class="text-muted mb-0 small">Monitor active sessions and recent user activity.</p>
    </div>
</div>

<div class="glass-panel p-4 shadow-sm">
    <div class="table-responsive">
        <table id="logsTable" class="table table-hover align-middle" style="width:100%">
            <thead style="border-bottom: 2px solid rgba(0,0,0,0.05);">
                <tr class="text-secondary small text-uppercase">
                    <th>User</th>
                    <th>Role</th>
                    <th>IP Address</th>
                    <th>Device / Browser</th>
                    <th>Last Activity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                @foreach($logs as $log)
                <tr>
                    <td>
                        <div class="fw-bold text-dark">{{ $log->name }}</div>
                        <div class="small text-muted">{{ $log->email }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $log->type === 'admin' ? 'bg-primary text-primary' : 'bg-success text-success' }} bg-opacity-10 border {{ $log->type === 'admin' ? 'border-primary' : 'border-success' }} border-opacity-25 rounded-pill px-3">
                            {{ ucfirst($log->type) }}
                        </span>
                    </td>
                    <td class="font-monospace text-muted small">{{ $log->ip_address ?? 'Unknown' }}</td>
                    <td class="small text-muted agent-text" title="{{ $log->user_agent }}">
                        <i class="bi bi-display me-1"></i> {{ $log->user_agent ?? 'Unknown Device' }}
                    </td>
                    <td>
                        <div class="fw-medium text-dark">{{ $log->formatted_activity }}</div>
                    </td>
                    <td>
                        <span class="badge bg-success rounded-pill px-3 shadow-sm">Active</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() { 
        $('#logsTable').DataTable({
            "order": [[ 4, "desc" ]], 
            "language": {
                "search": "",
                "searchPlaceholder": "Search logs..."
            },
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });
    });
</script>
@endpush

@endsection