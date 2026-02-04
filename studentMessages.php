<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .dashboard-header {
            padding-top: 6rem; /* Account for fixed nav */
            padding-bottom: 2rem;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .dash-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .status-approved { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .proj-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        .proj-meta { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; }
        
        .timeline-item {
            display: flex;
            gap: 1rem;
            padding-bottom: 1.5rem;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 25px;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }
        .timeline-item:last-child::before { display: none; }
        .t-marker {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--bg-surface);
            border: 2px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
            z-index: 1;
        }
        .t-marker.done { background: var(--primary); color: white; }
    </style>
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
                <a href="studentDashboard.php" class="menu-item"><i class="ri-home-line"></i> Home</a>
                <a href="studentProjects.php" class="menu-item"><i class="ri-stack-line"></i> My Projects</a>
                <a href="studentMessages.php" class="menu-item active"><i class="ri-mail-line"></i> Messages</a>
                <a href="studentResources.php" class="menu-item"><i class="ri-book-open-line"></i> Resources</a>
            </div>
            <div style="padding: 1.5rem;">
                <a href="login.php" class="menu-item" style="color: #ef4444;"><i class="ri-logout-box-r-line"></i> Logout</a>
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
                        <span class="user-name">Alex Morgan</span>
                        <span class="user-role">Student</span>
                    </div>
                    <div class="user-avatar">AM</div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="userProfile.php" class="dropdown-item"><i class="ri-user-line"></i> My Profile</a>
                        <div class="dropdown-divider"></div>
                        <a href="login.php" class="dropdown-item" style="color: #ef4444;"><i class="ri-logout-box-r-line"></i> Logout</a>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="d-content">
                <!-- Page Header -->
                <div class="welcome-card" style="min-height: 150px;">
                    <div class="welcome-text">
                        <h2>Messages</h2>
                        <p>Manage messages here.</p>
                    </div>
                </div>

                <div style="text-align:center; padding: 4rem; color: var(--text-muted);">
                    <i class="ri-mail-line" style="font-size: 4rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                    <h3>Messages Module</h3>
                    <p>This page has been created and linked.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById('profileDropdown').classList.toggle('show');
        }
        window.onclick = function(event) {
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
