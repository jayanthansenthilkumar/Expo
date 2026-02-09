<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SPARK'26</title>
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
                    <h1>Admin Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Search anything...">
                    </div>
                    <div class="header-icon">
                        <i class="ri-notification-3-line"></i>
                        <span class="badge"></span>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar" style="background: #ef4444;"><?php echo $userInitials; ?></div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="user-role">Administrator</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Card -->
                <div class="welcome-card" style="background: linear-gradient(135deg, #1e40af, #7c3aed);">
                    <h2>Welcome, Admin! 🛡️</h2>
                    <p>Full control over SPARK'26. Manage users, projects, departments, and event settings from here.
                    </p>
                    <a href="#" class="btn-light">System Settings</a>
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
                            <h3>0</h3>
                            <p>Total Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-user-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>0</h3>
                            <p>Registered Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-building-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>13</h3>
                            <p>Departments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-calendar-event-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>6</h3>
                            <p>Days to Event</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Quick Actions</h3>
                <div class="quick-actions">
                    <div class="action-card">
                        <div class="action-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                            <i class="ri-user-add-line"></i>
                        </div>
                        <div>
                            <h4>Add User</h4>
                            <p>Create new user account</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-megaphone-line"></i>
                        </div>
                        <div>
                            <h4>Announcement</h4>
                            <p>Broadcast to all users</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-file-chart-line"></i>
                        </div>
                        <div>
                            <h4>Reports</h4>
                            <p>Generate system reports</p>
                        </div>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="ri-shield-check-line"></i>
                        </div>
                        <div>
                            <h4>Permissions</h4>
                            <p>Manage user roles</p>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid" style="margin-top: 2rem;">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Activity</h3>
                            <a href="#" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <p style="color: var(--text-muted);">No recent activity.</p>
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

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
    </script>
</body>

</html>