<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Admin');

// Filter
$filterRole = $_GET['role'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
$types = "";
if ($filterRole) {
    $where .= " AND role = ?";
    $params[] = $filterRole;
    $types .= "s";
}

$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM users $where");
if ($types) mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$totalRows = mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
mysqli_stmt_close($countStmt);
$totalPages = max(1, ceil($totalRows / $perPage));

$query = "SELECT u.*, (SELECT COUNT(*) FROM projects WHERE student_id = u.id) as project_count FROM users u $where ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}
mysqli_stmt_close($stmt);

// Departments list for add user form
$departments = ['FE','AIDS','AIML','CSE','CSBS','CYBER','ECE','EEE','MECH','CIVIL','IT','VLSI','MBA','MCA'];

$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="ri-menu-line"></i>
                    </button>
                    <h1>User Management</h1>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Search users...">
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo $userInitials; ?></div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">

                <div class="content-header">
                    <h2>All Users</h2>
                    <div class="header-actions">
                        <form method="GET" style="display:flex;gap:0.5rem;">
                            <select class="filter-select" name="role" onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <option value="student" <?php if($filterRole==='student') echo 'selected'; ?>>Students</option>
                                <option value="admin" <?php if($filterRole==='admin') echo 'selected'; ?>>Admins</option>
                                <option value="departmentcoordinator" <?php if($filterRole==='departmentcoordinator') echo 'selected'; ?>>Coordinators</option>
                                <option value="studentaffairs" <?php if($filterRole==='studentaffairs') echo 'selected'; ?>>Student Affairs</option>
                            </select>
                        </form>
                        <button class="btn-primary" onclick="showAddUser()">
                            <i class="ri-add-line"></i> Add User
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="empty-table">
                                    <i class="ri-user-line"></i>
                                    <p>No users found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span style="padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;background:var(--bg-surface);">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['department'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span style="padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;
                                            <?php echo $u['status']==='active' ? 'background:#dcfce7;color:#166534;' : 'background:#fef2f2;color:#991b1b;'; ?>">
                                            <?php echo ucfirst($u['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:0.5rem;">
                                            <button type="button" class="btn-icon" title="Edit" onclick="openEditUser(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>', '<?php echo addslashes($u['email']); ?>', '<?php echo $u['role']; ?>', '<?php echo addslashes($u['department'] ?? ''); ?>')" style="color:#6366f1;">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn-icon" title="Toggle Status">
                                                    <i class="ri-<?php echo $u['status']==='active' ? 'lock-line' : 'lock-unlock-line'; ?>"></i>
                                                </button>
                                            </form>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;" class="confirm-delete-form">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn-icon" title="Delete" style="color:#ef4444;"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page-1); ?>&role=<?php echo $filterRole; ?>" class="btn-pagination" <?php if($page<=1) echo 'disabled'; ?>>&laquo; Previous</a>
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <a href="?page=<?php echo min($totalPages, $page+1); ?>&role=<?php echo $filterRole; ?>" class="btn-pagination" <?php if($page>=$totalPages) echo 'disabled'; ?>>Next &raquo;</a>
                </div>

                <!-- User modals handled via SweetAlert -->
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
    const departments = <?php echo json_encode($departments); ?>;

    function getDeptOptions(selected) {
        return '<option value="">Select Department</option>' + departments.map(d =>
            `<option value="${d}" ${d === selected ? 'selected' : ''}>${d}</option>`
        ).join('');
    }

    function showAddUser() {
        Swal.fire({
            title: 'Add New User',
            html: `
                <div style="text-align:left;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Full Name *</label>
                    <input id="swal-name" class="swal2-input" placeholder="Enter full name" style="margin:0 0 0.75rem 0;width:100%;box-sizing:border-box;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Username *</label>
                    <input id="swal-username" class="swal2-input" placeholder="Enter username" style="margin:0 0 0.75rem 0;width:100%;box-sizing:border-box;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Email *</label>
                    <input id="swal-email" type="email" class="swal2-input" placeholder="Enter email" style="margin:0 0 0.75rem 0;width:100%;box-sizing:border-box;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Password *</label>
                    <input id="swal-password" type="password" class="swal2-input" placeholder="Enter password" style="margin:0 0 0.75rem 0;width:100%;box-sizing:border-box;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Role</label>
                    <select id="swal-role" class="swal2-select" style="margin:0 0 0.75rem 0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                        <option value="student">Student</option>
                        <option value="departmentcoordinator">Department Coordinator</option>
                        <option value="studentaffairs">Student Affairs</option>
                        <option value="admin">Admin</option>
                    </select>
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Department</label>
                    <select id="swal-dept" class="swal2-select" style="margin:0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                        ${getDeptOptions('')}
                    </select>
                </div>
            `,
            confirmButtonText: '<i class="ri-user-add-line"></i> Add User',
            confirmButtonColor: '#2563eb',
            showCancelButton: true,
            cancelButtonColor: '#6b7280',
            width: '500px',
            focusConfirm: false,
            preConfirm: () => {
                const name = document.getElementById('swal-name').value.trim();
                const username = document.getElementById('swal-username').value.trim();
                const email = document.getElementById('swal-email').value.trim();
                const password = document.getElementById('swal-password').value;
                if (!name || !username || !email || !password) {
                    Swal.showValidationMessage('Name, username, email, and password are required');
                    return false;
                }
                return {
                    name, username, email, password,
                    role: document.getElementById('swal-role').value,
                    department: document.getElementById('swal-dept').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const d = result.value;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'sparkBackend.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="add_user">
                    <input type="hidden" name="name" value="${escapeHtml(d.name)}">
                    <input type="hidden" name="username" value="${escapeHtml(d.username)}">
                    <input type="hidden" name="email" value="${escapeHtml(d.email)}">
                    <input type="hidden" name="password" value="${escapeHtml(d.password)}">
                    <input type="hidden" name="role" value="${d.role}">
                    <input type="hidden" name="department" value="${escapeHtml(d.department)}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function openEditUser(id, name, email, role, department) {
        Swal.fire({
            title: 'Edit User',
            html: `
                <div style="text-align:left;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Full Name *</label>
                    <input id="swal-editName" class="swal2-input" value="${escapeHtml(name)}" style="margin:0 0 0.75rem 0;width:100%;box-sizing:border-box;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Email *</label>
                    <input id="swal-editEmail" type="email" class="swal2-input" value="${escapeHtml(email)}" style="margin:0 0 0.75rem 0;width:100%;box-sizing:border-box;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Role</label>
                    <select id="swal-editRole" class="swal2-select" style="margin:0 0 0.75rem 0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                        <option value="student" ${role==='student'?'selected':''}>Student</option>
                        <option value="departmentcoordinator" ${role==='departmentcoordinator'?'selected':''}>Department Coordinator</option>
                        <option value="studentaffairs" ${role==='studentaffairs'?'selected':''}>Student Affairs</option>
                        <option value="admin" ${role==='admin'?'selected':''}>Admin</option>
                    </select>
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Department</label>
                    <select id="swal-editDept" class="swal2-select" style="margin:0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                        ${getDeptOptions(department)}
                    </select>
                </div>
            `,
            confirmButtonText: '<i class="ri-save-line"></i> Save Changes',
            confirmButtonColor: '#2563eb',
            showCancelButton: true,
            cancelButtonColor: '#6b7280',
            width: '500px',
            focusConfirm: false,
            preConfirm: () => {
                const editName = document.getElementById('swal-editName').value.trim();
                const editEmail = document.getElementById('swal-editEmail').value.trim();
                if (!editName || !editEmail) {
                    Swal.showValidationMessage('Name and email are required');
                    return false;
                }
                return {
                    name: editName,
                    email: editEmail,
                    role: document.getElementById('swal-editRole').value,
                    department: document.getElementById('swal-editDept').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const d = result.value;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'sparkBackend.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" value="${id}">
                    <input type="hidden" name="name" value="${escapeHtml(d.name)}">
                    <input type="hidden" name="email" value="${escapeHtml(d.email)}">
                    <input type="hidden" name="role" value="${d.role}">
                    <input type="hidden" name="department" value="${escapeHtml(d.department)}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    <?php if ($successMsg): ?>
    Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#2563eb' });
    <?php endif; ?>

    document.querySelectorAll('.confirm-delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formEl = this;
            Swal.fire({
                title: 'Delete User?',
                text: 'This action cannot be undone. All associated data will be removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete!'
            }).then((result) => {
                if (result.isConfirmed) formEl.submit();
            });
        });
    });
    </script>
</body>

</html>
