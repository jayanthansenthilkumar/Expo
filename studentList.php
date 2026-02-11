<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$department = $_SESSION['department'] ?? '';
$isFE = (strtoupper($department) === 'FE');

// Multi-department support (AIDS & AIML share one coordinator)
$deptFilter = buildDeptFilter($department);
$dp = $deptFilter['placeholders'];
$dt = $deptFilter['types'];
$dv = $deptFilter['values'];

// Pagination
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$isMbaOrMca = in_array(strtoupper($department), ['MBA', 'MCA']);

// Count students in department (FE coordinator: all first-year non-MBA/MCA students)
if ($isFE) {
    $feFilter = buildFEStudentFilter();
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users u WHERE " . $feFilter['where'] . " AND u.role = 'student'");
    mysqli_stmt_bind_param($countStmt, $feFilter['types'], ...$feFilter['values']);
} elseif ($isMbaOrMca) {
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users WHERE department IN ($dp) AND role = 'student'");
    mysqli_stmt_bind_param($countStmt, $dt, ...$dv);
} else {
    $excl = buildExcludeFirstYearFilter();
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users u WHERE u.department IN ($dp) AND u.role = 'student' AND " . $excl['where']);
    $allTypes = $dt . $excl['types'];
    $allValues = array_merge($dv, $excl['values']);
    mysqli_stmt_bind_param($countStmt, $allTypes, ...$allValues);
}
mysqli_stmt_execute($countStmt);
$totalStudents = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['cnt'];
mysqli_stmt_close($countStmt);
$totalPages = max(1, ceil($totalStudents / $perPage));

// Fetch students with project count
if ($isFE) {
    $feFilter = buildFEStudentFilter();
    $fetchTypes = $feFilter['types'] . 'ii';
    $fetchParams = array_merge($feFilter['values'], [$perPage, $offset]);
    $stmt = mysqli_prepare($conn, "
        SELECT u.*, 
            (SELECT COUNT(*) FROM projects WHERE student_id = u.id) AS project_count
        FROM users u
        WHERE " . $feFilter['where'] . " AND u.role = 'student'
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
} elseif ($isMbaOrMca) {
    $fetchTypes = $dt . 'ii';
    $fetchParams = array_merge($dv, [$perPage, $offset]);
    $stmt = mysqli_prepare($conn, "
        SELECT u.*, 
            (SELECT COUNT(*) FROM projects WHERE student_id = u.id) AS project_count
        FROM users u
        WHERE u.department IN ($dp) AND u.role = 'student'
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
} else {
    $excl = buildExcludeFirstYearFilter();
    $fetchTypes = $dt . $excl['types'] . 'ii';
    $fetchParams = array_merge($dv, $excl['values'], [$perPage, $offset]);
    $stmt = mysqli_prepare($conn, "
        SELECT u.*, 
            (SELECT COUNT(*) FROM projects WHERE student_id = u.id) AS project_count
        FROM users u
        WHERE u.department IN ($dp) AND u.role = 'student' AND " . $excl['where'] . "
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
}
mysqli_stmt_bind_param($stmt, $fetchTypes, ...$fetchParams);
mysqli_stmt_execute($stmt);
$stuResult = mysqli_stmt_get_result($stmt);
$students = [];
while ($row = mysqli_fetch_assoc($stuResult)) {
    $students[] = $row;
}
mysqli_stmt_close($stmt);

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
    <title>Student List | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            $pageTitle = 'Student List';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="content-header">
                    <h2>Students in Your Department - <span class="badge"><?php echo $totalStudents; ?></span></h2>
                    <div class="header-actions">
                        <a href="sparkBackend.php?action=export_students" class="btn-secondary"
                            style="text-decoration:none;">
                            <i class="ri-download-line"></i> Export List
                        </a>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-export-bar">
                        <span class="export-label">Export</span>
                        <div class="export-btn-group" data-table="studentListTable" data-filename="Student_List">
                            <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                            <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                        </div>
                    </div>
                    <table class="data-table" id="studentListTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Year</th>
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
                                        <td><?php echo htmlspecialchars($student['year'] ?? '—'); ?></td>
                                        <td><?php echo (int) $student['project_count']; ?></td>
                                        <td>
                                            <span
                                                class="badge badge-<?php echo ($student['status'] === 'active') ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($student['status'] ?? 'inactive')); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                        <td>
                                            <a href="profile.php?id=<?php echo (int) $student['id']; ?>" class="btn-icon"
                                                title="View Profile">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-table">
                                        <i class="ri-user-line"></i>
                                        <p>No students in your department yet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn-pagination">&laquo; Previous</a>
                    <?php else: ?>
                        <button class="btn-pagination" disabled>&laquo; Previous</button>
                    <?php endif; ?>
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn-pagination">Next &raquo;</a>
                    <?php else: ?>
                        <button class="btn-pagination" disabled>Next &raquo;</button>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/tableExport.js"></script>
    <script>
        <?php if ($successMsg): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#2563eb' });
        <?php endif; ?>
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>