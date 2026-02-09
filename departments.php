<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Admin');

// Fetch all distinct departments from users and projects tables
$deptQuery = $pdo->query("SELECT DISTINCT department FROM (SELECT department FROM users WHERE department IS NOT NULL AND department != '' UNION SELECT department FROM projects WHERE department IS NOT NULL AND department != '') as depts ORDER BY department");
$deptNames = $deptQuery->fetchAll(PDO::FETCH_COLUMN);

$departments = [];
foreach ($deptNames as $deptName) {
    // Count students
    $stmtStudents = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='student' AND department = ?");
    $stmtStudents->execute([$deptName]);
    $studentCount = $stmtStudents->fetchColumn();

    // Count projects
    $stmtProjects = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE department = ?");
    $stmtProjects->execute([$deptName]);
    $projectCount = $stmtProjects->fetchColumn();

    // Find coordinator
    $stmtCoord = $pdo->prepare("SELECT name FROM users WHERE role='departmentcoordinator' AND department = ? LIMIT 1");
    $stmtCoord->execute([$deptName]);
    $coordinatorName = $stmtCoord->fetchColumn();

    $departments[] = [
        'name' => $deptName,
        'student_count' => $studentCount,
        'project_count' => $projectCount,
        'coordinator_name' => $coordinatorName ?: null
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments | SPARK'26</title>
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
                    <h1>Departments</h1>
                </div>
                <div class="header-right">
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
                    <h2>Department Management</h2>
                    <button class="btn-primary">
                        <i class="ri-add-line"></i> Add Department
                    </button>
                </div>

                <div class="departments-grid">
                    <?php if (empty($departments)): ?>
                        <div class="empty-state" style="grid-column: 1/-1; text-align:center; padding:2rem;">
                            <i class="ri-building-line" style="font-size:3rem; color:var(--text-secondary);"></i>
                            <p>No departments found.</p>
                        </div>
                    <?php else: ?>
                        <?php
                        $deptIcons = ['ri-computer-line', 'ri-cpu-line', 'ri-settings-line', 'ri-building-line', 'ri-flask-line'];
                        foreach ($departments as $index => $dept):
                            $icon = $deptIcons[$index % count($deptIcons)];
                        ?>
                        <div class="department-card">
                            <div class="dept-icon">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($dept['name']); ?></h3>
                            <div class="dept-stats">
                                <span><i class="ri-user-line"></i> <?php echo (int)$dept['student_count']; ?> Students</span>
                                <span><i class="ri-folder-line"></i> <?php echo (int)$dept['project_count']; ?> Projects</span>
                            </div>
                            <div class="dept-coordinator">
                                <span>Coordinator: <?php echo $dept['coordinator_name'] ? htmlspecialchars($dept['coordinator_name']) : 'Not Assigned'; ?></span>
                            </div>
                            <div class="dept-actions">
                                <button class="btn-icon" title="Edit"><i class="ri-edit-line"></i></button>
                                <button class="btn-icon" title="View"><i class="ri-eye-line"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
