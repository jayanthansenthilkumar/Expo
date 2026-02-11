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
        <?php include 'includes/sidebar.php'; ?>


        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php
            $pageTitle = 'Student Affairs Dashboard';
            include 'includes/header.php';
            ?>

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
                            <h4>Review Approvals</h4>
                            <p>Approve or reject submissions</p>
                        </div>
                    </a>
                    <a href="allProjects.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-folder-line"></i>
                        </div>
                        <div>
                            <h4>All Projects</h4>
                            <p>Browse project database</p>
                        </div>
                    </a>
                    <a href="users.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-user-settings-line"></i>
                        </div>
                        <div>
                            <h4>User Management</h4>
                            <p>Manage students & staff</p>
                        </div>
                    </a>
                    <a href="announcements.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-megaphone-line"></i>
                        </div>
                        <div>
                            <h4>Announcements</h4>
                            <p>Post updates & notices</p>
                        </div>
                    </a>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid" style="margin-top: 2rem;">
                    <!-- Recent Projects Card -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Projects</h3>
                            <a href="allProjects.php" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <?php if (mysqli_num_rows($recentResult) > 0): ?>
                                <?php
                                // Reset pointer if improved logic needed, but here simple iteration works
                                // Note: result pointer might be exhausted if reused. RecentResult is created at top.
                                mysqli_data_seek($recentResult, 0); 
                                while ($proj = mysqli_fetch_assoc($recentResult)): ?>
                                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 0;border-bottom:1px solid var(--border);">
                                        <div style="flex-grow:1;">
                                            <strong style="display:block;font-size:0.95rem;margin-bottom:0.1rem;"><?php echo htmlspecialchars($proj['title']); ?></strong>
                                            <p style="color:var(--text-muted);font-size:0.8rem;margin:0;">
                                                <i class="ri-user-line" style="margin-right:0.2rem;"></i> <?php echo htmlspecialchars($proj['student_name'] ?? 'Unknown'); ?> 
                                                <span style="margin:0 0.3rem;">&bull;</span> 
                                                <i class="ri-building-line" style="margin-right:0.2rem;"></i> <?php echo htmlspecialchars($proj['department']); ?>
                                            </p>
                                        </div>
                                        <span class="status-badge <?php echo 'status-' . $proj['status']; ?>" style="font-size:0.75rem;padding:0.2rem 0.5rem;border-radius:4px;background:<?php echo $proj['status']=='approved' ? '#dcfce7' : ($proj['status']=='rejected' ? '#fef2f2' : '#fef3c7'); ?>;color:<?php echo $proj['status']=='approved' ? '#166534' : ($proj['status']=='rejected' ? '#991b1b' : '#92400e'); ?>;">
                                            <?php echo ucfirst($proj['status']); ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state small">
                                    <i class="ri-folder-open-line"></i>
                                    <p>No projects submitted yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Announcements Card (Replacing Department Overview since Departments page isn't in sidebar) -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Announcements</h3>
                            <a href="announcements.php" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <?php
                            $recentAnnouncements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
                            if (mysqli_num_rows($recentAnnouncements) > 0): ?>
                                <?php while ($ann = mysqli_fetch_assoc($recentAnnouncements)): ?>
                                    <div style="padding:0.75rem 0;border-bottom:1px solid var(--border);">
                                        <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem;">
                                            <span class="badge" style="background:#eff6ff;color:var(--primary);font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:4px;">
                                                <?php echo ucfirst($ann['target_role']); ?>
                                            </span>
                                            <span style="font-size:0.75rem;color:var(--text-muted);">
                                                <?php echo date('M d', strtotime($ann['created_at'])); ?>
                                            </span>
                                        </div>
                                        <h4 style="font-size:0.95rem;margin:0 0 0.2rem 0;"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state small">
                                    <i class="ri-notification-off-line"></i>
                                    <p>No announcements.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>