<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Student Affairs';
$userInitials = strtoupper(substr($userName, 0, 2));
$userId = $_SESSION['user_id'] ?? 0;

// Total projects
$totalProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects"))['cnt'];

// Pending review
$pendingReview = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending'"))['cnt'];

// Approved
$approvedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];

// Registered students
$totalStudents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'student'"))['cnt'];

// Recent submissions
$recentResult = mysqli_query($conn, "SELECT p.*, u.name as student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id ORDER BY p.created_at DESC LIMIT 5");

// Department overview
$deptOverview = mysqli_query($conn, "SELECT department, COUNT(*) as cnt FROM projects WHERE department != '' GROUP BY department ORDER BY cnt DESC");

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
    <title>Student Affairs | SPARK'26</title>
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
                    <h1>Student Affairs Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Search students, projects...">
                    </div>
                    <div class="header-icon">
                        <i class="ri-notification-3-line"></i>
                        <span class="badge"></span>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo $userInitials; ?></div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="user-role">Student Affairs</span>
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
                    <p>Manage student projects, review submissions, and coordinate with departments for SPARK'26.</p>
                    <a href="approvals.php" class="btn-light">View Pending Approvals</a>
                    <div class="welcome-decoration">
                        <i class="ri-user-star-line"></i>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="ri-folder-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalProjects; ?></h3>
                            <p>Total Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pendingReview; ?></h3>
                            <p>Pending Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $approvedCount; ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-group-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalStudents; ?></h3>
                            <p>Registered Students</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Quick Actions</h3>
                <div class="quick-actions">
                    <a href="approvals.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div>
                            <h4>Review Projects</h4>
                            <p>Approve or reject submissions</p>
                        </div>
                    </a>
                    <a href="announcements.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-megaphone-line"></i>
                        </div>
                        <div>
                            <h4>Post Announcement</h4>
                            <p>Notify all students</p>
                        </div>
                    </a>
                    <a href="students.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-group-line"></i>
                        </div>
                        <div>
                            <h4>View Students</h4>
                            <p>Browse all students</p>
                        </div>
                    </a>
                    <a href="schedule.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-calendar-check-line"></i>
                        </div>
                        <div>
                            <h4>Schedule Event</h4>
                            <p>Manage event timeline</p>
                        </div>
                    </a>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid" style="margin-top: 2rem;">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Submissions</h3>
                            <a href="approvals.php" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <?php if (mysqli_num_rows($recentResult) > 0): ?>
                                <?php while ($proj = mysqli_fetch_assoc($recentResult)): ?>
                                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid var(--border);">
                                        <div>
                                            <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($proj['title']); ?></strong>
                                            <p style="color:var(--text-muted);font-size:0.8rem;margin:0;">by <?php echo htmlspecialchars($proj['student_name'] ?? 'Unknown'); ?> &bull; <?php echo htmlspecialchars($proj['department']); ?></p>
                                        </div>
                                        <span class="status-badge <?php echo $proj['status']; ?>"><?php echo ucfirst($proj['status']); ?></span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p style="color: var(--text-muted);">No submissions yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Department Overview</h3>
                            <a href="departments.php" style="color: var(--primary); font-size: 0.9rem;">View Details</a>
                        </div>
                        <div class="dash-card-body">
                            <?php if (mysqli_num_rows($deptOverview) > 0): ?>
                                <?php while ($dept = mysqli_fetch_assoc($deptOverview)): ?>
                                    <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--border);">
                                        <span><?php echo htmlspecialchars($dept['department']); ?></span>
                                        <span class="badge"><?php echo $dept['cnt']; ?> project<?php echo $dept['cnt'] != 1 ? 's' : ''; ?></span>
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