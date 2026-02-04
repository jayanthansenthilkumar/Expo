<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | SPARK'26</title>
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
        }
        .stat-val { font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-label { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; }

        .table-container {
            background: white;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-top: 2rem;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--bg-surface); font-weight: 600; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: var(--bg-surface); }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .st-pending { background: #fff7ed; color: #c2410c; }
        .st-approved { background: #f0fdf4; color: #15803d; }
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
                <a href="classAdvisor.php" class="menu-item"><i class="ri-grid-line"></i> Overview</a>
                <a href="advisorClassList.php" class="menu-item"><i class="ri-list-check"></i> Class List</a>
                <a href="advisorReviews.php" class="menu-item"><i class="ri-clipboard-line"></i> Project Reviews</a>
                <a href="advisorReports.php" class="menu-item active"><i class="ri-bar-chart-line"></i> Reports</a>
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
                    <input type="text" placeholder="Search students, projects...">
                </div>
                <!-- Profile -->
                <div class="header-profile" onclick="toggleDropdown()">
                    <div class="user-info">
                        <span class="user-name">Prof. Sarah Connor</span>
                        <span class="user-role">Class Advisor</span>
                    </div>
                    <div class="user-avatar">SC</div>
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
                        <h2>Reports</h2>
                        <p>Manage reports here.</p>
                    </div>
                </div>

                <div style="text-align:center; padding: 4rem; color: var(--text-muted);">
                    <i class="ri-bar-chart-line" style="font-size: 4rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                    <h3>Reports Module</h3>
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
