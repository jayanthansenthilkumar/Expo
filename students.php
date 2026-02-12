<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');

// Filters & pagination
$departmentFilter = $_GET['department'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Count total students
if ($departmentFilter) {
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'student' AND department = ?");
    mysqli_stmt_bind_param($countStmt, "s", $departmentFilter);
} else {
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'student'");
}
mysqli_stmt_execute($countStmt);
$totalStudents = mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
mysqli_stmt_close($countStmt);
$totalPages = max(1, ceil($totalStudents / $perPage));

// Fetch students with project count
if ($departmentFilter) {
    $stmt = mysqli_prepare($conn, "SELECT u.*, (SELECT COUNT(*) FROM projects WHERE student_id = u.id) as project_count FROM users u WHERE u.role = 'student' AND u.department = ? ORDER BY u.created_at DESC LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt, "sii", $departmentFilter, $offset, $perPage);
} else {
    $stmt = mysqli_prepare($conn, "SELECT u.*, (SELECT COUNT(*) FROM projects WHERE student_id = u.id) as project_count FROM users u WHERE u.role = 'student' ORDER BY u.created_at DESC LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt, "ii", $offset, $perPage);
}
mysqli_stmt_execute($stmt);
$students = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Get distinct departments for filter
$deptResult = mysqli_query($conn, "SELECT DISTINCT department FROM users WHERE role = 'student' AND department IS NOT NULL AND department != '' ORDER BY department ASC");
$departments = [];
while ($row = mysqli_fetch_assoc($deptResult)) {
    $departments[] = $row['department'];
}

// Flash messages
$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | SPARK'26</title>
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
            $pageTitle = 'Students';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">
                <?php if ($successMsg): ?>
                    <div class="alert alert-success"
                        style="background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <i class="ri-checkbox-circle-line"></i> <?php echo htmlspecialchars($successMsg); ?>
                    </div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-error"
                        style="background:#fef2f2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($errorMsg); ?>
                    </div>
                <?php endif; ?>

                <div class="content-header">
                    <h2>Registered Students <span
                            style="font-size:0.9rem;color:#6b7280;">(<?php echo $totalStudents; ?>)</span></h2>
                    <div class="header-actions">
                        <form method="GET" style="display:inline;">
                            <select name="department" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($departmentFilter === $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-export-bar">
                        <span class="export-label">Export</span>
                        <div class="export-btn-group" data-table="studentsTable" data-filename="All_Students">
                            <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                            <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                        </div>
                    </div>
                    <table class="data-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Projects</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['department'] ?? '—'); ?></td>
                                        <td><?php echo intval($student['project_count']); ?></td>
                                        <td>
                                            <?php $status = $student['status'] ?? 'active'; ?>
                                            <span
                                                class="badge badge-<?php echo $status === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                        <td>
                                            <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'studentaffairs'])): ?>
                                                <form action="sparkBackend.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_user_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $student['id']; ?>">
                                                    <input type="hidden" name="redirect" value="students.php">
                                                    <button type="submit"
                                                        class="btn-sm <?php echo $status === 'active' ? 'btn-danger' : 'btn-success'; ?>"
                                                        title="<?php echo $status === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                        <i
                                                            class="ri-<?php echo $status === 'active' ? 'forbid-line' : 'check-line'; ?>"></i>
                                                        <?php echo $status === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted"><?php echo ucfirst($status); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-table">
                                        <i class="ri-user-line"></i>
                                        <p>No students registered yet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?department=<?php echo urlencode($departmentFilter); ?>&page=<?php echo $page - 1; ?>"
                                class="btn-pagination">&laquo; Previous</a>
                        <?php else: ?>
                            <button class="btn-pagination" disabled>&laquo; Previous</button>
                        <?php endif; ?>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                        <?php if ($page < $totalPages): ?>
                            <a href="?department=<?php echo urlencode($departmentFilter); ?>&page=<?php echo $page + 1; ?>"
                                class="btn-pagination">Next &raquo;</a>
                        <?php else: ?>
                            <button class="btn-pagination" disabled>Next &raquo;</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/tableExport.js?v=2"></script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>