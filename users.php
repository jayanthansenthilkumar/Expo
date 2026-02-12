<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Admin');

// Filter Logic
$filterRole = $_GET['role'] ?? 'departmentcoordinator'; // Default to coordinator

// Security: Prevent studentaffairs from seeing admins
if ($_SESSION['role'] === 'studentaffairs' && $filterRole === 'admin') {
    $filterRole = 'departmentcoordinator';
}

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

// Extra safety: Always exclude admin for studentaffairs even if filter is somehow bypassed or empty
if ($_SESSION['role'] === 'studentaffairs') {
    $where .= " AND role != 'admin'";
}

$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM users $where");
if ($types)
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
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
$departments = ['FE', 'AIDS', 'AIML', 'CSE', 'CSBS', 'CYBER', 'ECE', 'EEE', 'MECH', 'CIVIL', 'IT', 'VLSI', 'MBA', 'MCA'];

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
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.2/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'User Management';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="content-header stacked">
                    <div class="header-top">
                        <h2>All Users</h2>
                        <button class="btn-primary" onclick="showAddUser()">
                            <i class="ri-add-line"></i> Add User
                        </button>
                    </div>
                </div>

                <div class="tabs-container">
                    <a href="?role=departmentcoordinator"
                        class="tab-link <?php echo $filterRole === 'departmentcoordinator' ? 'active' : ''; ?>">
                        <i class="ri-user-star-line"></i> Coordinators
                    </a>
                    <a href="?role=student" class="tab-link <?php echo $filterRole === 'student' ? 'active' : ''; ?>">
                        <i class="ri-user-line"></i> Students
                    </a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="?role=admin" class="tab-link <?php echo $filterRole === 'admin' ? 'active' : ''; ?>">
                            <i class="ri-shield-user-line"></i> Admin
                        </a>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="?role=studentaffairs"
                            class="tab-link <?php echo $filterRole === 'studentaffairs' ? 'active' : ''; ?>">
                            Student Affairs
                        </a>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                    <div class="table-export-bar">
                        <span class="export-label">Export</span>
                        <div class="export-btn-group" data-table="usersTable" data-filename="Users_List">
                            <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                            <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                        </div>
                    </div>
                    <table class="data-table" id="usersTable">
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
                                            <span
                                                style="padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;background:var(--bg-surface);">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['department'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span
                                                style="padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;
                                            <?php echo $u['status'] === 'active' ? 'background:#dcfce7;color:#166534;' : 'background:#fef2f2;color:#991b1b;'; ?>">
                                                <?php echo ucfirst($u['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:0.5rem;">
                                                <button type="button" class="btn-icon" title="Edit"
                                                    onclick="openEditUser(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>', '<?php echo addslashes($u['email']); ?>', '<?php echo $u['role']; ?>', '<?php echo addslashes($u['department'] ?? ''); ?>')"
                                                    style="color:#D97706;">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                    <form action="sparkBackend.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_user_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                        <button type="submit" class="btn-icon" title="Toggle Status">
                                                            <i
                                                                class="ri-<?php echo $u['status'] === 'active' ? 'lock-line' : 'lock-unlock-line'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    <form action="sparkBackend.php" method="POST" style="display:inline;"
                                                        class="confirm-delete-form">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                        <button type="submit" class="btn-icon" title="Delete"
                                                            style="color:#ef4444;"><i class="ri-delete-bin-line"></i></button>
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
                    <a href="?page=<?php echo max(1, $page - 1); ?>&role=<?php echo $filterRole; ?>"
                        class="btn-pagination" <?php if ($page <= 1)
                            echo 'disabled'; ?>>&laquo; Previous</a>
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>&role=<?php echo $filterRole; ?>"
                        class="btn-pagination" <?php if ($page >= $totalPages)
                            echo 'disabled'; ?>>Next &raquo;</a>
                </div>

                <!-- User modals handled via SweetAlert -->
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/tableExport.js?v=2"></script>
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
                confirmButtonColor: '#D97706',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                width: Math.min(500, window.innerWidth - 40) + 'px',
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
                        <option value="student" ${role === 'student' ? 'selected' : ''}>Student</option>
                        <option value="departmentcoordinator" ${role === 'departmentcoordinator' ? 'selected' : ''}>Department Coordinator</option>
                        <option value="studentaffairs" ${role === 'studentaffairs' ? 'selected' : ''}>Student Affairs</option>
                        <option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option>
                    </select>
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Department</label>
                    <select id="swal-editDept" class="swal2-select" style="margin:0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                        ${getDeptOptions(department)}
                    </select>
                </div>
            `,
                confirmButtonText: '<i class="ri-save-line"></i> Save Changes',
                confirmButtonColor: '#D97706',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                width: Math.min(500, window.innerWidth - 40) + 'px',
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
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#D97706', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#D97706' });
        <?php endif; ?>

        document.querySelectorAll('.confirm-delete-form').forEach(form => {
            form.addEventListener('submit', function (e) {
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

    <?php include 'includes/bot.php'; ?>
</body>

</html>