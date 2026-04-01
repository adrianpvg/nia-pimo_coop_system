@extends('layouts.app')

@section('content')

<style>
    /* =========================================
       PAGE STYLING (Clean & Light)
       ========================================= */
    .user-card {
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid rgba(255, 255, 255, 0.8);
        background: linear-gradient(135deg, rgba(255,255,255,0.85), rgba(255,255,255,0.6));
        position: relative; 
    }
    
    .user-card:hover {
        transform: translateY(-5px) scale(1.01);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255,255,255,0.8));
        border-color: rgba(0, 122, 255, 0.3);
    }

    .icon-wrapper {
        width: 56px;
        height: 56px;
        border-radius: 18px; 
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: inset 0 2px 4px rgba(255,255,255,0.5), 0 4px 10px rgba(0,0,0,0.05);
    }
    
    .icon-admin {
        background: linear-gradient(135deg, #007aff 0%, #0056b3 100%);
        color: #ffffff;
    }
    
    .icon-member {
        background: linear-gradient(135deg, #34c759 0%, #248a3d 100%);
        color: #ffffff;
    }

    .search-container {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 14px;
        transition: all 0.3s ease;
    }
    
    .search-container:focus-within {
        background: #fff;
        border-color: #007aff;
        box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.15);
    }

    /* =========================================
       KEBAB MENU & DROPDOWN UI
       ========================================= */
    .kebab-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        background: transparent;
        transition: all 0.2s ease;
        border: none;
        padding: 0;
    }
    
    .kebab-btn:hover, .kebab-btn:focus, .dropdown.show .kebab-btn {
        background: rgba(0, 0, 0, 0.06);
        color: #2d3748;
        outline: none;
    }

    .glass-dropdown {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 1);
        border-radius: 14px;
        box-shadow: 0 12px 36px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.02);
        padding: 0.5rem;
        min-width: 200px;
    }
    
    .glass-dropdown .dropdown-item {
        border-radius: 8px;
        padding: 0.6rem 1rem;
        font-weight: 500;
        color: #4a5568;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        align-items: center;
    }
    
    .glass-dropdown .dropdown-item i {
        font-size: 1.1rem;
        transition: transform 0.25s ease;
    }
    
    /* Interactive Hover States */
    .glass-dropdown .dropdown-item:hover {
        background-color: rgba(0, 122, 255, 0.08);
        color: #007aff !important;
        transform: translateX(4px);
    }
    .glass-dropdown .dropdown-item:hover i {
        transform: scale(1.15);
    }
    
    .glass-dropdown .dropdown-item.text-danger:hover {
        background-color: rgba(255, 59, 48, 0.08);
        color: #ff3b30 !important;
    }

    /* =========================================
       MODAL STYLING (Pushed Glassmorphism)
       ========================================= */
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

    .password-wrapper { position: relative; }
    .toggle-password {
        position: absolute;
        top: 50%; right: 12px;
        transform: translateY(-50%);
        cursor: pointer; color: #6c757d;
        background: none; border: none; padding: 0;
        transition: color 0.2s;
    }
    .toggle-password:hover { color: #007aff; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Team Directory</h2>
        <p class="text-muted mb-0 small">Manage system access, roles, and user accounts.</p>
    </div>
    
    <div> 
        @if(Auth::check() && Auth::user()->type === 'admin')
            <button class="btn btn-dark shadow-sm px-4" style="border-radius: 12px;" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="bi bi-person-plus-fill me-2"></i> Add Member
            </button>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show glass-panel border-success" style="background: rgba(40, 167, 69, 0.1);">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row mb-4">
    <div class="col-md-6 col-lg-4">
        <div class="search-container d-flex align-items-center px-3 shadow-sm">
            <i class="bi bi-search text-muted"></i>
            <input type="text" id="searchInput" class="form-control border-0 bg-transparent py-2 shadow-none" placeholder="Search by name or role..." onkeyup="filterUsers()">
        </div>
    </div>
</div>

<div class="row g-4" id="usersGrid">
    @foreach($users as $user)
    <div class="col-12 col-md-6 col-xl-4 user-item">
        <div class="glass-panel p-4 user-card h-100 d-flex flex-column">
            
            <div class="dropdown position-absolute top-0 end-0 mt-3 me-3 z-3">
                <button class="kebab-btn shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical fs-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end glass-dropdown border-0 mt-2">
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewUserModal{{ $user->id }}">
                            <i class="bi bi-eye me-3 text-primary"></i> View Details
                        </a>
                    </li>
                    
                    @if(Auth::check() && Auth::user()->type === 'admin')
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                <i class="bi bi-pencil me-3 text-warning"></i> Edit Account
                            </a>
                        </li>
                        <li><hr class="dropdown-divider bg-secondary opacity-25 my-2 mx-2"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteUserModal{{ $user->id }}">
                                <i class="bi bi-trash me-3"></i> Delete User
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
            
            <div class="d-flex align-items-center mt-1">
                @if($user->type === 'admin')
                    <div class="icon-wrapper icon-admin me-3 flex-shrink-0">
                        <i class="bi bi-shield-check"></i>
                    </div>
                @else
                    <div class="icon-wrapper icon-member me-3 flex-shrink-0">
                        <i class="bi bi-person"></i>
                    </div>
                @endif
                
                <div class="flex-grow-1 pe-5" style="min-width: 0;">
                    <h5 class="fw-bold mb-0 text-truncate searchable-name text-dark">{{ $user->name }}</h5>
                    <p class="text-muted small mb-2 text-truncate">{{ $user->email }}</p>
                    
                    <span class="badge {{ $user->type === 'admin' ? 'bg-primary bg-opacity-10 text-primary border border-primary' : 'bg-success bg-opacity-10 text-success border border-success' }} border-opacity-25 rounded-pill px-3 searchable-role">
                        {{ ucfirst($user->type) }}
                    </span>
                </div>
            </div>
            
        </div>
    </div>

    <div class="modal fade append-to-body" id="viewUserModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal-content border-0">
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h4 class="modal-title fw-bold text-dark"><i class="bi bi-person-badge text-primary me-2"></i> User Profile</h4>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="text-center mb-4">
                        <div class="icon-wrapper mx-auto mb-3 {{ $user->type === 'admin' ? 'icon-admin' : 'icon-member' }}" style="width: 80px; height: 80px; font-size: 2.5rem;">
                            <i class="bi {{ $user->type === 'admin' ? 'bi-shield-check' : 'bi-person' }}"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-0">{{ $user->name }}</h4>
                        <span class="badge {{ $user->type === 'admin' ? 'bg-primary' : 'bg-success' }} rounded-pill mt-2 px-3">{{ ucfirst($user->type) }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small text-secondary mb-1 fw-semibold">Email Address</label>
                        <input type="text" class="form-control glass-input px-3 py-2 fw-medium border-0" value="{{ $user->email }}" readonly style="background: rgba(0,0,0,0.03) !important;">
                    </div>
                    <div class="mb-3">
                        <label class="small text-secondary mb-1 fw-semibold">Account Created</label>
                        <input type="text" class="form-control glass-input px-3 py-2 fw-medium border-0" value="{{ $user->created_at->format('F d, Y - h:i A') }}" readonly style="background: rgba(0,0,0,0.03) !important;">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4 mt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm w-100 fw-bold" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7); color: #4a5568;">Close Profile</button>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::check() && Auth::user()->type === 'admin')
    <div class="modal fade append-to-body" id="editUserModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content glass-modal-content border-0">
                <form action="{{ route('team.update', $user->id ?? 0) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h4 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Account</h4>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4 py-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="small text-secondary mb-1 fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control glass-input px-3 py-2" value="{{ $user->name }}" required>
                            </div>
                            
                            <div class="col-md-7">
                                <label class="small text-secondary mb-1 fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control glass-input px-3 py-2" value="{{ $user->email }}" required>
                            </div>

                            <div class="col-md-5">
                                <label class="small text-secondary mb-1 fw-semibold">Assign Role <span class="text-danger">*</span></label>
                                <select name="type" class="form-select glass-input px-3 py-2 fw-medium" required>
                                    <option value="member" {{ $user->type === 'member' ? 'selected' : '' }}>Member (Standard)</option>
                                    <option value="admin" {{ $user->type === 'admin' ? 'selected' : '' }}>Admin (Full Access)</option>
                                </select>
                            </div>

                            <div class="col-12 mt-4 border-top pt-3" style="border-color: rgba(0,0,0,0.05) !important;">
                                <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i>Leave passwords blank to keep the current password.</p>
                            </div>

                            <div class="col-md-6">
                                <label class="small text-secondary mb-1 fw-semibold">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="editPassword{{ $user->id }}" class="form-control glass-input px-3 py-2 pe-5" placeholder="Optional">
                                    <button type="button" class="toggle-password" onclick="togglePassword('editPassword{{ $user->id }}', 'editEye1{{ $user->id }}')">
                                        <i class="bi bi-eye" id="editEye1{{ $user->id }}"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="small text-secondary mb-1 fw-semibold">Confirm New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password_confirmation" id="editConfirm{{ $user->id }}" class="form-control glass-input px-3 py-2 pe-5" placeholder="Optional">
                                    <button type="button" class="toggle-password" onclick="togglePassword('editConfirm{{ $user->id }}', 'editEye2{{ $user->id }}')">
                                        <i class="bi bi-eye" id="editEye2{{ $user->id }}"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4 mt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7);">Cancel</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm text-dark fw-bold">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade append-to-body" id="deleteUserModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal-content border-0">
                <form action="{{ route('team.destroy', $user->id ?? 0) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Deletion</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4 py-4 text-center">
                        <p class="mb-1">Are you sure you want to permanently delete the account for:</p>
                        <h4 class="fw-bold text-dark mt-2 mb-3">{{ $user->name }}?</h4>
                        <p class="small text-danger mb-0">This action cannot be undone. All associated access will be revoked.</p>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4 d-flex justify-content-center">
                        <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7);">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold">Yes, Delete Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @endforeach
</div>

@if(Auth::check() && Auth::user()->type === 'admin')
<div class="modal fade append-to-body" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered"> 
        <div class="modal-content glass-modal-content border-0">
            <form action="{{ route('team.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h4 class="modal-title fw-bold" style="background: linear-gradient(90deg, #007aff, #34c759); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        Create Account
                    </h4>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body px-4 py-4">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="small text-secondary mb-1 fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control glass-input px-3 py-2" placeholder="Jane" required>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-secondary mb-1 fw-semibold">M.I.</label>
                            <input type="text" name="middle_initial" class="form-control glass-input px-3 py-2" placeholder="A." maxlength="3">
                        </div>
                        <div class="col-md-5">
                            <label class="small text-secondary mb-1 fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control glass-input px-3 py-2" placeholder="Doe" required>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary mb-1 fw-semibold">Suffix</label>
                            <input type="text" name="suffix" class="form-control glass-input px-3 py-2" placeholder="Jr., Sr.">
                        </div>
                        
                        <div class="col-md-9">
                            <label class="small text-secondary mb-1 fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control glass-input px-3 py-2" placeholder="jane@example.com" required>
                        </div>
                        
                        <div class="col-md-6 mt-4">
                            <label class="small text-secondary mb-1 fw-semibold">Temporary Password <span class="text-danger">*</span></label>
                            <div class="password-wrapper">
                                <input type="password" name="password" id="newPassword" class="form-control glass-input px-3 py-2 pe-5" placeholder="Min. 6 chars" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('newPassword', 'eyeIcon1')">
                                    <i class="bi bi-eye" id="eyeIcon1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label class="small text-secondary mb-1 fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="password-wrapper">
                                <input type="password" name="password_confirmation" id="confirmPassword" class="form-control glass-input px-3 py-2 pe-5" placeholder="Retype password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', 'eyeIcon2')">
                                    <i class="bi bi-eye" id="eyeIcon2"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <label class="small text-secondary mb-1 fw-semibold">Assign Role <span class="text-danger">*</span></label>
                            <select name="type" class="form-select glass-input px-3 py-2 fw-medium" required>
                                <option value="member" selected>Member (Standard Access)</option>
                                <option value="admin">Administrator (Full Access)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4 mt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.7);">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 shadow-sm fw-bold">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    $(document).ready(function() { 
        $('.append-to-body').appendTo('body');
    });

    function togglePassword(inputId, iconId) {
        var input = document.getElementById(inputId);
        var icon = document.getElementById(iconId);
        
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }

    function filterUsers() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let items = document.getElementsByClassName('user-item');

        for (let i = 0; i < items.length; i++) {
            let name = items[i].querySelector('.searchable-name').innerText.toLowerCase();
            let role = items[i].querySelector('.searchable-role').innerText.toLowerCase();
            
            if (name.includes(input) || role.includes(input)) {
                items[i].style.display = ""; 
            } else {
                items[i].style.display = "none"; 
            }
        }
    }
</script>
@endpush

@endsection