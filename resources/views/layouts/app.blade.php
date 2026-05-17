<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIMO COOP SYSTEM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.45);
            --glass-border: rgba(255, 255, 255, 0.6);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
            --glass-blur: blur(16px);
            --grad-blue: rgba(0, 123, 255, 0.1);
            --grad-green: rgba(40, 167, 69, 0.1);
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --apple-radius: 16px;
        }

        body {
            background-color: #f4f6f9;
            background-image: 
                radial-gradient(at 0% 0%, var(--grad-blue) 0, transparent 50%), 
                radial-gradient(at 100% 100%, var(--grad-green) 0, transparent 50%);
            background-attachment: fixed;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-primary);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        .glass-panel {
            background: linear-gradient(135deg, rgba(255,255,255,0.6), rgba(255,255,255,0.3));
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            border-radius: var(--apple-radius);
        }

        .app-layout {
            display: flex;
            height: 100vh;
            padding: 1rem;
            gap: 1.5rem;
            position: relative;
        }

        .sidebar {
            width: 280px;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            overflow-y: auto;
            transition: left 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .user-profile {
            display: flex;
            align-items: center;
            padding-bottom: 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .user-profile .bi { color: #007aff; } 

        .nav-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            font-weight: 600;
            margin-top: 1rem;
        }

        .nav-section hr {
            margin: 0.5rem 0 1rem 0;
            border-color: rgba(0,0,0,0.1);
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 0.25rem;
            position: relative;
            overflow: hidden;
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(90deg, rgba(0, 122, 255, 0.1), rgba(52, 199, 89, 0.1));
            color: #000;
            transform: translateX(5px);
        }

        .nav-link:hover i, .nav-link.active i {
            color: #34c759; 
        }

        .nav-link.logout-link:hover {
            background: rgba(255, 59, 48, 0.1); 
            color: #ff3b30;
        }
        .nav-link.logout-link:hover i { color: #ff3b30; }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            overflow-y: auto;
            padding-right: 0.5rem;
            width: 100%;
        }

        .content-container {
            flex: 1;
            padding: 2rem;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu-btn {
            display: none; 
            position: fixed;
            top: 1.25rem;
            left: 1.25rem;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            z-index: 1030;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid var(--glass-border);
            background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.6));
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .mobile-menu-btn:active {
            transform: scale(0.92); 
        }

        @media (max-width: 991.98px) {
            .app-layout {
                padding: 1rem;
                padding-top: 5.5rem; 
                flex-direction: column; 
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -320px; 
                height: 100vh;
                z-index: 1050;
                border-radius: 0 24px 24px 0; 
                box-shadow: 10px 0 30px rgba(0,0,0,0.1);
                margin: 0; 
            }

            .sidebar.active {
                left: 0;
            }

            .mobile-menu-btn {
                display: flex; 
            }

            .main-wrapper {
                padding-right: 0;
                gap: 1rem;
            }

            .content-container {
                padding: 1.25rem; 
            }
        }

        .modal-backdrop.show {
            opacity: 0.4;
            backdrop-filter: blur(8px);
        }
        .glass-modal-content {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--apple-radius);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.25); }
    </style>
</head>
<body>

    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="bi bi-list fs-2" style="margin-left: 2px;"></i>
    </button>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-layout">
        
        <aside class="sidebar glass-panel shadow-sm" id="sidebar">
            <div class="user-profile">
                <i class="bi bi-person-circle fs-2"></i>
                <div class="ms-3">
                    <div class="fw-bold">{{ Auth::user()->name ?? 'User Name' }}</div>
                    <small class="text-muted">{{ ucfirst(Auth::user()->type ?? 'Member') }}</small>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <hr>
                <ul class="nav-menu">
                    <li><a href="{{ route('finance.index') }}" class="nav-link {{ request()->routeIs('finance.*') ? 'active' : '' }}"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>

                </ul>
            </div>

            <div class="nav-section mt-3">
                <div class="nav-section-title">Team</div>
                <hr>
                <ul class="nav-menu">
                    <li><a href="{{ route('team.index') }}" class="nav-link {{ request()->routeIs('team.index', 'team.show', 'team.create') ? 'active' : '' }}"><i class="bi bi-people"></i> Members</a></li>
                    
                    @if(Auth::check() && Auth::user()->type === 'admin')
                        <li><a href="{{ route('team.logs') }}" class="nav-link {{ request()->routeIs('team.logs') ? 'active' : '' }}"><i class="bi bi-journal-text"></i> Logs</a></li>
                    @endif
                </ul>
            </div>

            <div class="mt-auto pt-3">
                <ul class="nav-menu">
                    <li>
                        <a href="#" class="nav-link logout-link text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <i class="bi bi-box-arrow-left"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <main class="main-wrapper">
            <div class="content-container glass-panel shadow-sm">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show glass-panel" style="background: rgba(40, 167, 69, 0.2); border-color: rgba(40, 167, 69, 0.4);">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-secondary pb-4">
                    Are you sure you want to securely log out of the PIMO COOP system?
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light glass-panel" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger" style="border-radius: 8px;">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            mobileMenuBtn.addEventListener('click', function () {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
            });

            sidebarOverlay.addEventListener('click', function () {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        });
    </script>
    
    @stack('scripts')
    @stack('modals')
</body>
</html>