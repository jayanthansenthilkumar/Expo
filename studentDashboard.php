<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'student') {
    header("Location: login.php");
    exit();
}
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

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="d-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo">
                    <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
                    SPARK <span>'26</span>
                </a>
            </div>
            <div class="sidebar-menu">
                <a href="studentDashboard.php" class="menu-item active"><i class="ri-home-line"></i> Home</a>
                <a href="studentProjects.php" class="menu-item"><i class="ri-stack-line"></i> My Projects</a>
                <a href="studentMessages.php" class="menu-item"><i class="ri-mail-line"></i> Messages</a>
                <a href="studentResources.php" class="menu-item"><i class="ri-book-open-line"></i> Resources</a>
            </div>
            <div style="padding: 1.5rem;">
                <a href="login.php" class="menu-item" style="color: #ef4444;"><i class="ri-logout-box-r-line"></i>
                    Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="d-main">
            <!-- Header -->
            <header class="d-header">
                <div class="header-search">
                    <i class="ri-search-line"></i>
                    <input type="text" placeholder="Search resources...">
                </div>
                <!-- Profile -->
                <div class="header-profile" onclick="toggleDropdown()">
                    <div class="user-info">
                        <span class="user-name">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>
                        <span class="user-role">Student</span>
                    </div>
                    <div class="user-avatar">AM</div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="userProfile.php" class="dropdown-item"><i class="ri-user-line"></i> My Profile</a>
                        <div class="dropdown-divider"></div>
                        <a href="login.php" class="dropdown-item" style="color: #ef4444;"><i
                                class="ri-logout-box-r-line"></i> Logout</a>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="d-content">

                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="welcome-text">
                        <h2>Hello,
                            <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>! 👋
                        </h2>
                        <p>You have 1 project pending review. Check the feedback from your advisor.</p>
                    </div>
                    <div class="welcome-decoration">
                        <i class="ri-lightbulb-line"></i>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h3 class="section-title" style="margin:0; font-size:1.5rem;">My Projects</h3>
                    <button class="btn-primary">+ New Project</button>
                </div>

                <div class="dashboard-grid">
                    <!-- Project Card 1 -->
                    <div class="dash-card">
                        <div
                            style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <span class="status-badge status-pending">Pending Review</span>
                            <i class="ri-ellipsis-h-line" style="color: var(--text-muted); cursor: pointer;"></i>
                        </div>
                        <h3 class="proj-title">AI-Driven Traffic Control</h3>
                        <p class="proj-meta">Submitted on Feb 2, 2026</p>
                        <p style="margin-bottom: 1.5rem; font-size: 0.95rem;">
                            An adaptive traffic light control system using computer vision to optimize traffic flow in
                            real-time.
                        </p>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn-outline" style="width: 100%; font-size: 0.9rem;">View Details</button>
                            <button class="btn-outline" style="width: 100%; font-size: 0.9rem;">Edit</button>
                        </div>
                    </div>

                    <!-- Timeline / Status -->
                    <div class="dash-card">
                        <h4 style="margin-bottom: 1.5rem;">Submission Timeline</h4>
                        <div class="timeline" style="margin:0; max-width: none;">
                            <div class="timeline-item">
                                <div class="t-marker done"
                                    style="position:relative; width: 24px; height: 24px; top:0; left:0; border: none; font-size: 0.8rem;">
                                    ✓</div>
                                <div>
                                    <div style="font-weight: 600;">Project Registered</div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);">Feb 2, 2026</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="t-marker"
                                    style="position:relative; width: 24px; height: 24px; top:0; left:0; border: 2px solid var(--accent); color: var(--accent); background:white; display: flex; align-items: center; justify-content: center; font-size:1.2rem;">
                                    •</div>
                                <div>
                                    <div style="font-weight: 600;">Advisor Approval</div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);">Pending</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="t-marker"
                                    style="position:relative; width: 24px; height: 24px; top:0; left:0; border: 2px solid var(--border); background:white;">
                                </div>
                                <div>
                                    <div style="font-weight: 600;">HOD Verification</div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);">waiting...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById('profileDropdown').classList.toggle('show');
        }
        window.onclick = function (event) {
            if (!event.target.closest('.header-profile')) {
                var dropdowns = document.getElementsByClassName("profile-dropdown");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>

</html>