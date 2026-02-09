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
$departments = ['AIDS','AIML','CSE','CSBS','CYBER','ECE','EEE','MECH','CIVIL','IT','VLSI','MBA','MCA'];

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
                <?php if ($successMsg): ?>
                    <div style="background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;margin-bottom:1rem;"><i class="ri-checkbox-circle-line"></i> <?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div style="background:#fef2f2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;"><i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

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
                        <button class="btn-primary" onclick="document.getElementById('addUserModal').style.display='flex'">
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
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn-icon" title="Toggle Status">
                                                    <i class="ri-<?php echo $u['status']==='active' ? 'lock-line' : 'lock-unlock-line'; ?>"></i>
                                                </button>
                                            </form>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete user <?php echo addslashes($u['name']); ?>?');">
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

                <!-- Add User Modal -->
                <div class="compose-modal" id="addUserModal" style="display:none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Add New User</h3>
                            <button class="btn-icon" onclick="document.getElementById('addUserModal').style.display='none'"><i class="ri-close-line"></i></button>
                        </div>
                        <form action="sparkBackend.php" method="POST">
                            <input type="hidden" name="action" value="add_user">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" required placeholder="Enter full name">
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" required placeholder="Enter username">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required placeholder="Enter email">
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" required placeholder="Enter password">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" required>
                                    <option value="student">Student</option>
                                    <option value="departmentcoordinator">Department Coordinator</option>
                                    <option value="studentaffairs">Student Affairs</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="document.getElementById('addUserModal').style.display='none'">Cancel</button>
                                <button type="submit" class="btn-primary">Add User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
