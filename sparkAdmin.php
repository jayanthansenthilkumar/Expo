<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));

// Fetch admin stats
$totalProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects"))['cnt'];
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
$totalDepartments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT department) as cnt FROM users WHERE department IS NOT NULL AND department != ''"))['cnt'];
$pendingProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending'"))['cnt'];

// Days to event (read from settings table)
$eventDateRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'event_date'"));
$eventDate = $eventDateRow ? $eventDateRow['setting_value'] : '2026-02-15';
$daysToEvent = max(0, (int) ((strtotime($eventDate) - time()) / 86400));

// Recent activity (last 5 projects)
$recentProjects = [];
$result = mysqli_query($conn, "SELECT p.title, p.status, p.created_at, u.name as student_name FROM projects p JOIN users u ON p.student_id = u.id ORDER BY p.created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    $recentProjects[] = $row;
}

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
    <title>Admin Dashboard | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>


        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php
            $pageTitle = 'Admin Dashboard';
            include 'includes/header.php';
            ?>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <?php if ($success): ?>
                    <div class="alert alert-success"
                        style="background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <i class="ri-checkbox-circle-line"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"
                        style="background:#fef2f2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <!-- Welcome Card -->
                <div class="welcome-card" style="background: #D97706;">
                    <h2>Welcome, Admin! 🛡️</h2>
                    <p>Full control over SPARK'26. Manage users, projects, departments, and event settings from here.
                    </p>
                    <a href="settings.php" class="btn-light">System Settings</a>
                    <div class="welcome-decoration">
                        <i class="ri-shield-star-line"></i>
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
                        <div class="stat-icon green">
                            <i class="ri-user-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalUsers; ?></h3>
                            <p>Registered Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-building-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalDepartments; ?></h3>
                            <p>Departments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-calendar-event-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $daysToEvent; ?></h3>
                            <p>Days to Event</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Quick Actions</h3>
                <div class="quick-actions">
                    <a href="users.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                            <i class="ri-user-add-line"></i>
                        </div>
                        <div>
                            <h4>Add User</h4>
                            <p>Create new user account</p>
                        </div>
                    </a>
                    <a href="announcements.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-megaphone-line"></i>
                        </div>
                        <div>
                            <h4>Announcement</h4>
                            <p>Broadcast to all users</p>
                        </div>
                    </a>
                    <a href="analytics.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-file-chart-line"></i>
                        </div>
                        <div>
                            <h4>Reports</h4>
                            <p>Generate system reports</p>
                        </div>
                    </a>
                    <a href="users.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-shield-check-line"></i>
                        </div>
                        <div>
                            <h4>Permissions</h4>
                            <p>Manage user roles</p>
                        </div>
                    </a>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid" style="margin-top: 2rem;">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Activity</h3>
                            <a href="#" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <?php if (empty($recentProjects)): ?>
                                <p style="color: var(--text-muted);">No recent activity.</p>
                            <?php else: ?>
                                <?php foreach ($recentProjects as $rp): ?>
                                    <div
                                        style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid var(--border);">
                                        <div>
                                            <span
                                                style="font-size:0.9rem;font-weight:500;"><?php echo htmlspecialchars($rp['title']); ?></span>
                                            <p style="font-size:0.8rem;color:var(--text-muted);">by
                                                <?php echo htmlspecialchars($rp['student_name']); ?></p>
                                        </div>
                                        <span style="padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;
                                        <?php if ($rp['status'] === 'approved')
                                            echo 'background:#dcfce7;color:#166534;';
                                        elseif ($rp['status'] === 'rejected')
                                            echo 'background:#fef2f2;color:#991b1b;';
                                        else
                                            echo 'background:#fef3c7;color:#92400e;'; ?>">
                                            <?php echo ucfirst($rp['status']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>System Status</h3>
                            <span
                                style="background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">All
                                Systems Normal</span>
                        </div>
                        <div class="dash-card-body">
                            <div
                                style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                                <span>Database</span>
                                <span style="color: #22c55e;">● Online</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                                <span>File Storage</span>
                                <span style="color: #22c55e;">● Online</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                                <span>Email Service</span>
                                <span style="color: #22c55e;">● Online</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        <?php if ($success): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($success); ?>', confirmButtonColor: '#D97706', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($error): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($error); ?>', confirmButtonColor: '#D97706' });
        <?php endif; ?>
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>