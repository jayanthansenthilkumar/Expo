<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judging | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .dashboard-header {
            padding-top: 6rem;
            padding-bottom: 2rem;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-val { font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-label { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .manage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .action-card:hover { border-color: var(--primary); }
        .ac-icon {
            width: 48px;
            height: 48px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
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
                <a href="expoCoordinator.php" class="menu-item"><i class="ri-grid-line"></i> Dashboard</a>
                <a href="coordSchedule.php" class="menu-item"><i class="ri-calendar-line"></i> Schedule</a>
                <a href="coordAnnouncements.php" class="menu-item"><i class="ri-bullhorn-line"></i> Announcements</a>
                <a href="coordJudging.php" class="menu-item active"><i class="ri-award-line"></i> Judging</a>
                <a href="coordUsers.php" class="menu-item"><i class="ri-user-settings-line"></i> Users</a>
                <a href="coordReports.php" class="menu-item"><i class="ri-file-export-line"></i> Reports</a>
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
                    <input type="text" placeholder="Search projects, students...">
                </div>
                <!-- Profile -->
                <div class="header-profile" onclick="toggleDropdown()">
                    <div class="user-info">
                        <span class="user-name">Dr. Alan Grant</span>
                        <span class="user-role">Coordinator</span>
                    </div>
                    <div class="user-avatar">AG</div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="userProfile.php" class="dropdown-item"><i class="ri-user-line"></i> My Profile</a>
                        <a href="#" class="dropdown-item"><i class="ri-cog-line"></i> Settings</a>
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
                        <h2>Judging</h2>
                        <p>Manage judging here.</p>
                    </div>
                </div>

                <div style="text-align:center; padding: 4rem; color: var(--text-muted);">
                    <i class="ri-award-line" style="font-size: 4rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                    <h3>Judging Module</h3>
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
