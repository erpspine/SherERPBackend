<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Management — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <style>
        :root {
            --brand: #1e3a5f;
            --brand-light: #2563eb;
        }

        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* ── Navbar ── */
        .top-nav {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
        }

        .nav-brand {
            font-size: 17px;
            font-weight: 700;
            color: var(--brand);
            letter-spacing: -.4px;
        }

        /* ── Cards ── */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 8px rgba(0, 0, 0, .07);
        }

        /* ── Stats ── */
        .stat-value {
            font-size: 26px;
            font-weight: 700;
            line-height: 1;
        }

        /* ── Table ── */
        .table thead th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #6b7280;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 14px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 13px 14px;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e3a5f, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
            font-weight: 600;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
            font-weight: 600;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        /* ── Buttons ── */
        .btn-brand {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-light) 100%);
            border: none;
            color: #fff;
        }

        .btn-brand:hover {
            background: linear-gradient(135deg, #1e4d8c 0%, #1d4ed8 100%);
            color: #fff;
        }

        /* ── Form ── */
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }

        .form-control,
        .form-select {
            border-color: #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--brand-light);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
        }

        /* ── Login ── */
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        .login-header {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-light) 100%);
            border-radius: 12px 12px 0 0;
            padding: 38px 40px 30px;
            text-align: center;
        }
    </style>
</head>

<body>

    {{-- ========== LOGIN SECTION ========== --}}
    <div id="loginSection" class="login-wrap" style="display:none;">
        <div class="login-card">
            <div class="card overflow-hidden">
                <div class="login-header">
                    <div
                        style="width:54px;height:54px;background:rgba(255,255,255,.15);border-radius:50%;
                            display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                        <i class="fa fa-lock text-white" style="font-size:22px;"></i>
                    </div>
                    <h4 class="text-white fw-bold mb-1">{{ config('app.name') }}</h4>
                    <p class="mb-0" style="color:#bfdbfe;font-size:13px;">Sign in to User Management</p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light" style="border-radius:8px 0 0 8px;border-right:0;">
                                <i class="fa fa-envelope text-muted" style="font-size:13px;"></i>
                            </span>
                            <input type="email" id="loginEmail" class="form-control" placeholder="you@example.com"
                                style="border-radius:0 8px 8px 0;border-left:0;" />
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light" style="border-radius:8px 0 0 8px;border-right:0;">
                                <i class="fa fa-lock text-muted" style="font-size:13px;"></i>
                            </span>
                            <input type="password" id="loginPassword" class="form-control" placeholder="••••••••"
                                style="border-radius:0 8px 8px 0;border-left:0;" />
                        </div>
                    </div>
                    <button class="btn btn-brand w-100 py-2 fw-semibold" onclick="doLogin()">
                        <i class="fa fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== APP SECTION ========== --}}
    <div id="appSection" style="display:none;">

        <!-- Navbar -->
        <nav class="top-nav d-flex align-items-center px-4" style="height:58px;">
            <span class="nav-brand me-auto">
                <i class="fa fa-cubes me-2" style="color:var(--brand-light);"></i>{{ config('app.name') }}
            </span>
            <span id="loggedInAs" class="text-muted me-3" style="font-size:13px;"></span>
            <button class="btn btn-sm btn-outline-danger fw-medium" onclick="doLogout()">
                <i class="fa fa-sign-out-alt me-1"></i>Logout
            </button>
        </nav>

        <!-- Content -->
        <div class="container-fluid px-4 py-4" style="max-width:1280px;">

            <!-- Page header -->
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold mb-1" style="color:var(--brand);">User Management</h5>
                    <p class="text-muted mb-0" style="font-size:13px;">Create, view and manage system users</p>
                </div>
                <button class="btn btn-brand px-4" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fa fa-user-plus me-2"></i>Create User
                </button>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-sm-4 col-md-2">
                    <div class="card p-3 text-center h-100">
                        <div class="stat-value" id="statTotal" style="color:var(--brand);">—</div>
                        <div class="text-muted mt-1" style="font-size:12px;">Total Users</div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <div class="card p-3 text-center h-100">
                        <div class="stat-value" id="statActive" style="color:#059669;">—</div>
                        <div class="text-muted mt-1" style="font-size:12px;">Active</div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <div class="card p-3 text-center h-100">
                        <div class="stat-value" id="statInactive" style="color:#dc2626;">—</div>
                        <div class="text-muted mt-1" style="font-size:12px;">Inactive</div>
                    </div>
                </div>
            </div>

            <!-- Table card -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-center pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fa fa-spinner fa-spin me-2"></i>Loading users…
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ========== CREATE USER MODAL ========== --}}
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px;border:none;overflow:hidden;">

                <!-- Modal Header -->
                <div style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);padding:22px 24px 18px;">
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="fa fa-user-plus me-2"></i>Create New User
                    </h5>
                    <button type="button" class="btn-close btn-close-white float-end mt-1" data-bs-dismiss="modal"
                        style="margin-top:-22px;"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="newName" class="form-control" placeholder="John Doe" />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="newEmail" class="form-control"
                                placeholder="john@example.com" />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" id="newPhone" class="form-control"
                                placeholder="+255 712 345 678" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select id="newRole" class="form-select">
                                <option value="">— Select Role —</option>
                                <option>Operations</option>
                                <option>Finance</option>
                                <option>HR</option>
                                <option>IT</option>
                                <option>Management</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="newStatus" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2 p-3 rounded-2"
                                style="background:#f0f7ff;border:1px solid #bfdbfe;font-size:13px;color:#1e40af;">
                                <i class="fa fa-lock mt-1" style="flex-shrink:0;"></i>
                                <span>A secure password will be auto-generated and emailed directly to the user.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-brand px-4 fw-semibold" onclick="submitCreateUser()">
                        <i class="fa fa-paper-plane me-2"></i>Create &amp; Send Credentials
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const API = '/api';
        let authToken = sessionStorage.getItem('auth_token');

        // ── Initialise ──────────────────────────────────────────────────────────────
        window.addEventListener('load', () => {
            authToken ? showApp() : showLogin();
        });

        function showLogin() {
            document.getElementById('loginSection').style.display = '';
            document.getElementById('appSection').style.display = 'none';
        }

        function showApp() {
            document.getElementById('loginSection').style.display = 'none';
            document.getElementById('appSection').style.display = '';
            const u = JSON.parse(sessionStorage.getItem('auth_user') || '{}');
            if (u.name) {
                document.getElementById('loggedInAs').textContent = u.name + ' (' + (u.email || '') + ')';
            }
            loadUsers();
        }

        // ── Login ────────────────────────────────────────────────────────────────────
        async function doLogin() {
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;

            if (!email || !password) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Fields',
                    text: 'Please enter your email and password.'
                });
                return;
            }

            Swal.fire({
                title: 'Signing in…',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const res = await apiFetch('/login', 'POST', {
                    email,
                    password
                }, false);
                const data = await res.json();

                if (res.ok) {
                    authToken = data.token;
                    sessionStorage.setItem('auth_token', data.token);
                    sessionStorage.setItem('auth_user', JSON.stringify(data.user));
                    Swal.close();
                    showApp();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: data.message || 'Invalid credentials.'
                    });
                }
            } catch {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not reach the server.'
                });
            }
        }

        // Allow Enter key to trigger login
        document.addEventListener('keydown', e => {
            if (e.key === 'Enter' && document.getElementById('loginSection').style.display !== 'none') doLogin();
        });

        // ── Logout ───────────────────────────────────────────────────────────────────
        async function doLogout() {
            await apiFetch('/logout', 'POST').catch(() => {});
            sessionStorage.clear();
            authToken = null;
            showLogin();
        }

        // ── Load Users ───────────────────────────────────────────────────────────────
        async function loadUsers() {
            try {
                const res = await apiFetch('/users');
                if (res.status === 401) {
                    doLogout();
                    return;
                }
                const data = await res.json();
                renderUsers(data.users || []);
            } catch {
                document.getElementById('usersTableBody').innerHTML =
                    '<tr><td colspan="8" class="text-center py-5 text-danger">' +
                    '<i class="fa fa-exclamation-triangle me-2"></i>Failed to load users.</td></tr>';
            }
        }

        function renderUsers(users) {
            const active = users.filter(u => u.status === 'Active').length;
            document.getElementById('statTotal').textContent = users.length;
            document.getElementById('statActive').textContent = active;
            document.getElementById('statInactive').textContent = users.length - active;

            if (!users.length) {
                document.getElementById('usersTableBody').innerHTML =
                    '<tr><td colspan="8" class="text-center py-5 text-muted">' +
                    '<i class="fa fa-users me-2"></i>No users found.</td></tr>';
                return;
            }

            document.getElementById('usersTableBody').innerHTML = users.map((u, i) => `
        <tr>
            <td class="ps-4" style="color:#9ca3af;font-size:13px;">${i + 1}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar">${esc(u.name).charAt(0).toUpperCase()}</div>
                    <span class="fw-medium">${esc(u.name)}</span>
                </div>
            </td>
            <td style="font-size:13px;">${esc(u.email)}</td>
            <td style="font-size:13px;">${esc(u.phone || '—')}</td>
            <td>
                <span class="badge bg-light text-dark fw-normal border px-2 py-1" style="border-radius:6px;">
                    ${esc(u.role || '—')}
                </span>
            </td>
            <td>
                <span class="badge px-3 py-2 ${u.status === 'Active' ? 'badge-active' : 'badge-inactive'}"
                      style="border-radius:20px;font-size:11px;">
                    <i class="fa fa-circle me-1" style="font-size:7px;"></i>${esc(u.status)}
                </span>
            </td>
            <td style="font-size:12px;color:#9ca3af;">${fmtDate(u.created_at)}</td>
            <td class="text-center pe-4">
                <button class="btn btn-light action-btn me-1 btn-delete" title="Delete"
                        data-id="${u.id}" data-name="${esc(u.name)}">
                    <i class="fa fa-trash text-danger" style="font-size:12px;"></i>
                </button>
            </td>
        </tr>
    `).join('');
        }

        // ── Create User ──────────────────────────────────────────────────────────────
        async function submitCreateUser() {
            const name = document.getElementById('newName').value.trim();
            const email = document.getElementById('newEmail').value.trim();
            const phone = document.getElementById('newPhone').value.trim();
            const role = document.getElementById('newRole').value;
            const status = document.getElementById('newStatus').value;

            if (!name || !email || !phone || !role || !status) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete Form',
                    text: 'Please fill in all required fields.'
                });
                return;
            }

            bootstrap.Modal.getInstance(document.getElementById('createUserModal'))?.hide();

            Swal.fire({
                title: 'Creating User…',
                html: `Sending credentials to <strong>${esc(email)}</strong>`,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const res = await apiFetch('/users', 'POST', {
                    name,
                    email,
                    phone,
                    role,
                    status
                });
                const data = await res.json();

                if (res.ok) {
                    clearCreateForm();
                    await Swal.fire({
                        icon: 'success',
                        title: 'User Created!',
                        html: `<strong>${esc(data.user.name)}</strong> has been created.<br>
                       <span style="font-size:13px;color:#6b7280;">
                         Credentials sent to <strong>${esc(data.user.email)}</strong>
                       </span>`,
                        confirmButtonColor: '#2563eb',
                        confirmButtonText: 'Done',
                    });
                    loadUsers();
                } else {
                    const errors = data.errors ?
                        Object.values(data.errors).flat().join('<br>') :
                        (data.message || 'Something went wrong.');
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed to Create User',
                        html: errors,
                        confirmButtonColor: '#dc2626'
                    });
                }
            } catch {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not reach the server.'
                });
            }
        }

        function clearCreateForm() {
            ['newName', 'newEmail', 'newPhone'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('newRole').value = '';
            document.getElementById('newStatus').value = 'Active';
        }

        // ── Delete User ──────────────────────────────────────────────────────────────
        async function deleteUser(id, name) {
            const result = await Swal.fire({
                title: 'Delete User?',
                html: `You are about to permanently delete <strong>${esc(name)}</strong>.<br>
                <span style="font-size:13px;color:#6b7280;">This action cannot be undone.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fa fa-trash me-1"></i>Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Deleting…',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const res = await apiFetch(`/users/${id}`, 'DELETE');
                const data = await res.json();

                if (res.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: `${name} has been removed.`,
                        confirmButtonColor: '#2563eb',
                        timer: 2200,
                        timerProgressBar: true,
                    });
                    loadUsers();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete Failed',
                        text: data.message || 'Could not delete user.'
                    });
                }
            } catch {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not reach the server.'
                });
            }
        }

        // ── Helpers ──────────────────────────────────────────────────────────────────
        function apiFetch(path, method = 'GET', body = null, withAuth = true) {
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
            if (withAuth && authToken) headers['Authorization'] = 'Bearer ' + authToken;
            return fetch(API + path, {
                method,
                headers,
                body: body ? JSON.stringify(body) : null
            });
        }

        // ── Delete button event delegation ─────────────────────────────────────
        document.getElementById('usersTableBody').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            deleteUser(btn.dataset.id, btn.dataset.name);
        });

        function esc(str) {
            if (str == null) return '';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function fmtDate(iso) {
            if (!iso) return '—';
            return new Date(iso).toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }
    </script>
</body>

</html>
