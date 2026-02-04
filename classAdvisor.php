<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Dashboard | SPARK'26</title>
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
                <a href="classAdvisor.php" class="menu-item active"><i class="ri-grid-line"></i> Overview</a>
                <a href="advisorClassList.php" class="menu-item"><i class="ri-list-check"></i> Class List</a>
                <a href="advisorReviews.php" class="menu-item"><i class="ri-clipboard-line"></i> Project Reviews</a>
                <a href="advisorReports.php" class="menu-item"><i class="ri-bar-chart-line"></i> Reports</a>
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
                
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="welcome-text">
                        <h2>Welcome back, Professor! 🎓</h2>
                        <p>You have 5 projects pending your review for CSE Section A.</p>
                    </div>
                    <div class="welcome-decoration">
                        <i class="ri-presentation-line"></i>
                    </div>
                </div>

                <!-- Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-val">42</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val">12</div>
                        <div class="stat-label">Projects</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val" style="color: #f59e0b;">5</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val" style="color: #10b981;">7</div>
                        <div class="stat-label">Approved</div>
                    </div>
                </div>

                <!-- Project List -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 class="section-title" style="margin:0; font-size:1.5rem;">Pending Approvals</h3>
                    <button class="btn-outline">Download Report</button>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Project Title</th>
                                <th>Team Lead</th>
                                <th>Submission Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-weight: 600;">Smart Campus Navigation</td>
                                <td>Rahul Verma</td>
                                <td>Feb 4, 2026</td>
                                <td><span class="status-badge st-pending">Pending</span></td>
                                <td><a href="approveProject.php" class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</a></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Blockchain E-Voting</td>
                                <td>Sarah Jones</td>
                                <td>Feb 3, 2026</td>
                                <td><span class="status-badge st-pending">Pending</span></td>
                                <td><a href="approveProject.php" class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</a></td>
                            </tr>
                            <!-- Already Approved examples for context -->
                            <tr style="opacity: 0.7;">
                                <td style="font-weight: 600;">Automated Attendance</td>
                                <td>Mike Chen</td>
                                <td>Feb 1, 2026</td>
                                <td><span class="status-badge st-approved">Approved</span></td>
                                <td><button class="btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" disabled>View</button></td>
                            </tr>
                        </tbody>
                    </table>
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
