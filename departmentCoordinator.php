<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Department Coordinator';
$userDepartment = $_SESSION['department'] ?? 'Department';
$userInitials = strtoupper(substr($userName, 0, 2));
$userId = $_SESSION['user_id'] ?? 0;

// Department projects count
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department = ?");
mysqli_stmt_bind_param($stmt, "s", $userDepartment);
mysqli_stmt_execute($stmt);
$deptProjects = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Awaiting review
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, "s", $userDepartment);
mysqli_stmt_execute($stmt);
$pendingProjects = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Approved
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department = ? AND status = 'approved'");
mysqli_stmt_bind_param($stmt, "s", $userDepartment);
mysqli_stmt_execute($stmt);
$approvedProjects = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Department students
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users WHERE department = ? AND role = 'student'");
mysqli_stmt_bind_param($stmt, "s", $userDepartment);
mysqli_stmt_execute($stmt);
$deptStudents = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Recent submissions
$stmt = mysqli_prepare($conn, "SELECT p.*, u.name as student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.department = ? ORDER BY p.created_at DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "s", $userDepartment);
mysqli_stmt_execute($stmt);
$recentSubmissions = mysqli_stmt_get_result($stmt);

// Category breakdown
$stmt2 = mysqli_prepare($conn, "SELECT category, COUNT(*) as cnt FROM projects WHERE department = ? GROUP BY category ORDER BY cnt DESC");
mysqli_stmt_bind_param($stmt2, "s", $userDepartment);
mysqli_stmt_execute($stmt2);
$categoryBreakdown = mysqli_stmt_get_result($stmt2);

// Flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Coordinator | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>


        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="ri-menu-line"></i>
                    </button>
                    <h1><?php echo htmlspecialchars($userDepartment); ?> - Coordinator</h1>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Search projects, students...">
                    </div>
                    <div class="header-icon">
                        <i class="ri-notification-3-line"></i>
                        <span class="badge"></span>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo $userInitials; ?></div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="user-role">Dept. Coordinator</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h2>Welcome, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h2>
                    <p>Manage and review projects from your department. Ensure quality submissions for SPARK'26.</p>
                    <a href="reviewApprove.php" class="btn-light">Review Pending Projects</a>
                    <div class="welcome-decoration">
                        <i class="ri-building-line"></i>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="ri-folder-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $deptProjects; ?></h3>
                            <p>Department Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pendingProjects; ?></h3>
                            <p>Awaiting Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $approvedProjects; ?></h3>
                            <p>Approved Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-group-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $deptStudents; ?></h3>
                            <p>Department Students</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Quick Actions</h3>
                <div class="quick-actions">
                    <a href="reviewApprove.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div>
                            <h4>Review Projects</h4>
                            <p>Approve department projects</p>
                        </div>
                    </a>
                    <a href="studentList.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-group-line"></i>
                        </div>
                        <div>
                            <h4>View Students</h4>
                            <p>See registered students</p>
                        </div>
                    </a>
                    <a href="departmentStats.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-file-chart-line"></i>
                        </div>
                        <div>
                            <h4>Department Stats</h4>
                            <p>View department analytics</p>
                        </div>
                    </a>
                    <a href="messages.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-message-2-line"></i>
                        </div>
                        <div>
                            <h4>Send Message</h4>
                            <p>Contact students</p>
                        </div>
                    </a>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid" style="margin-top: 2rem;">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Submissions</h3>
                            <a href="departmentProjects.php" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <?php if (mysqli_num_rows($recentSubmissions) > 0): ?>
                                <?php while ($proj = mysqli_fetch_assoc($recentSubmissions)): ?>
                                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid var(--border);">
                                        <div>
                                            <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($proj['title']); ?></strong>
                                            <p style="color:var(--text-muted);font-size:0.8rem;margin:0;">by <?php echo htmlspecialchars($proj['student_name'] ?? 'Unknown'); ?></p>
                                        </div>
                                        <span class="status-badge <?php echo $proj['status']; ?>"><?php echo ucfirst($proj['status']); ?></span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p style="color: var(--text-muted);">No submissions from your department yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Project Categories</h3>
                            <a href="departmentProjects.php" style="color: var(--primary); font-size: 0.9rem;">View Details</a>
                        </div>
                        <div class="dash-card-body">
                            <?php if (mysqli_num_rows($categoryBreakdown) > 0): ?>
                                <?php while ($cat = mysqli_fetch_assoc($categoryBreakdown)): ?>
                                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--border);">
                                        <span><?php echo htmlspecialchars($cat['category']); ?></span>
                                        <span class="badge"><?php echo $cat['cnt']; ?> project<?php echo $cat['cnt'] != 1 ? 's' : ''; ?></span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p style="color: var(--text-muted);">No data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>