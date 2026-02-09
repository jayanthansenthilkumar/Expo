<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Student';
$userInitials = strtoupper(substr($userName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | SPARK'26</title>
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
                    <h1>Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Search...">
                    </div>
                    <div class="header-icon">
                        <i class="ri-notification-3-line"></i>
                        <span class="badge"></span>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo $userInitials; ?></div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="user-role">Student</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h2>
                    <p>Ready to showcase your innovation? Submit your project and compete with the best minds on campus.
                    </p>
                    <a href="#" class="btn-light">Submit Project</a>
                    <div class="welcome-decoration">
                        <i class="ri-rocket-2-line"></i>
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
                            <p>Projects Submitted</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>0</h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>0</h3>
                            <p>Pending Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-calendar-event-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>6</h3>
                            <p>Days to Expo</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Quick Actions</h3>
                <div class="quick-actions">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-add-line"></i>
                        </div>
                        <div>
                            <h4>New Project</h4>
                            <p>Submit a new project</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-team-line"></i>
                        </div>
                        <div>
                            <h4>Find Team</h4>
                            <p>Join or create a team</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-book-open-line"></i>
                        </div>
                        <div>
                            <h4>View Guidelines</h4>
                            <p>Read submission rules</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-customer-service-line"></i>
                        </div>
                        <div>
                            <h4>Get Help</h4>
                            <p>Contact support</p>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid" style="margin-top: 2rem;">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Announcements</h3>
                            <a href="#" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <p style="color: var(--text-muted);">No announcements yet.</p>
                        </div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Upcoming Deadlines</h3>
                            <a href="#" style="color: var(--primary); font-size: 0.9rem;">View Schedule</a>
                        </div>
                        <div class="dash-card-body">
                            <div
                                style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                                <div
                                    style="width: 50px; height: 50px; background: var(--bg-surface); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <span style="font-size: 1.25rem; font-weight: 800; line-height: 1;">15</span>
                                    <span style="font-size: 0.7rem; color: var(--text-muted);">FEB</span>
                                </div>
                                <div>
                                    <h4 style="font-size: 0.95rem;">SPARK'26 Expo Day</h4>
                                    <p style="font-size: 0.8rem; color: var(--text-muted);">Main Event</p>
                                </div>
                            </div>
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