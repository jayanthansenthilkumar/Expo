<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Department Coordinator';
$userDepartment = $_SESSION['department'] ?? 'Department';
$userInitials = strtoupper(substr($userName, 0, 2));
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
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h2>Welcome, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h2>
                    <p>Manage and review projects from your department. Ensure quality submissions for SPARK'26.</p>
                    <a href="#" class="btn-light">Review Pending Projects</a>
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
                            <h3>0</h3>
                            <p>Department Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>0</h3>
                            <p>Awaiting Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>0</h3>
                            <p>Approved Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-group-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>0</h3>
                            <p>Department Students</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Quick Actions</h3>
                <div class="quick-actions">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div>
                            <h4>Review Projects</h4>
                            <p>Approve department projects</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-group-line"></i>
                        </div>
                        <div>
                            <h4>View Students</h4>
                            <p>See registered students</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-file-chart-line"></i>
                        </div>
                        <div>
                            <h4>Generate Report</h4>
                            <p>Export department data</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-message-2-line"></i>
                        </div>
                        <div>
                            <h4>Send Message</h4>
                            <p>Contact students</p>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid" style="margin-top: 2rem;">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Submissions</h3>
                            <a href="#" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <p style="color: var(--text-muted);">No submissions from your department yet.</p>
                        </div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Project Categories</h3>
                            <a href="#" style="color: var(--primary); font-size: 0.9rem;">View Details</a>
                        </div>
                        <div class="dash-card-body">
                            <p style="color: var(--text-muted);">No data available.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
    </script>
</body>

</html>