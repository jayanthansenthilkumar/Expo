<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* Specific Styles for HOD Cards (Can be moved to style.css if needed globally) */
        .approval-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .project-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }
        .pc-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .pc-advisor-status {
            font-size: 0.8rem;
            color: #15803d;
            background: #f0fdf4;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        .pc-actions {
            margin-top: auto;
            padding-top: 1rem;
            display: flex;
            gap: 1rem;
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
                <a href="hodDepartment.php" class="menu-item active"><i class="ri-pie-chart-line"></i> Overview</a>
                <a href="hodFaculty.php" class="menu-item"><i class="ri-user-settings-line"></i> Faculty</a>
                <a href="hodProjects.php" class="menu-item"><i class="ri-node-tree"></i> All Projects</a>
                <a href="hodReports.php" class="menu-item"><i class="ri-file-list-3-line"></i> Dept Reports</a>
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
                    <input type="text" placeholder="Search department data...">
                </div>
                <!-- Profile -->
                <div class="header-profile" onclick="toggleDropdown()">
                    <div class="user-info">
                        <span class="user-name">Dr. Alan Turing</span>
                        <span class="user-role">HOD - CSE</span>
                    </div>
                    <div class="user-avatar">AT</div>
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
                        <h2>Department Overview</h2>
                        <p>Computer Science Engineering • HOD Panel</p>
                    </div>
                    <div class="welcome-decoration">
                        <i class="ri-building-line"></i>
                    </div>
                </div>

                <!-- Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-val">120</div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val" style="color: #f59e0b;">15</div>
                        <div class="stat-label">Needing HOD Approval</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val" style="color: #10b981;">85</div>
                        <div class="stat-label">Expo Ready</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val">98%</div>
                        <div class="stat-label">Participation</div>
                    </div>
                </div>

                <!-- Approval Section -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 class="section-title" style="margin:0; font-size:1.5rem;">Projects Awaiting Final Nod</h3>
                    <div>
                        <select style="padding: 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <option>All Sections</option>
                            <option>Section A</option>
                            <option>Section B</option>
                        </select>
                    </div>
                </div>

                <div class="approval-grid">
                    <!-- Project Card 1 -->
                    <div class="project-card">
                        <div class="pc-header">
                            <span class="pc-advisor-status"><i class="ri-checkbox-circle-line"></i> Advisor Approved</span>
                            <span style="font-size: 0.8rem; color: #64748b;">#CS-104</span>
                        </div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Decentralized Health Records</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; flex-grow: 1;">
                            Using Hyperledger Fabric to secure patient data sharing between hospitals.
                        </p>
                        <div style="font-size: 0.85rem; margin-bottom: 1rem; color: var(--text-dark);">
                            <strong>Team:</strong> Cryptonites (Lead: Arjun K)<br>
                            <strong>Advisor:</strong> Prof. Smitha
                        </div>
                        <div class="pc-actions">
                            <button class="btn-outline" style="flex:1;">View Details</button>
                            <a href="approveProject.php?role=hod" class="btn-primary" style="flex:1; text-align: center;">Approve</a>
                        </div>
                    </div>

                    <!-- Project Card 2 -->
                    <div class="project-card">
                        <div class="pc-header">
                            <span class="pc-advisor-status"><i class="ri-checkbox-circle-line"></i> Advisor Approved</span>
                            <span style="font-size: 0.8rem; color: #64748b;">#CS-109</span>
                        </div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">AI Crop Disease Detection</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; flex-grow: 1;">
                            Image processing model to detect early signs of crop diseases using drone imagery.
                        </p>
                        <div style="font-size: 0.85rem; margin-bottom: 1rem; color: var(--text-dark);">
                            <strong>Team:</strong> GreenTech (Lead: Mary L)<br>
                            <strong>Advisor:</strong> Prof. John
                        </div>
                        <div class="pc-actions">
                            <button class="btn-outline" style="flex:1;">View Details</button>
                            <a href="approveProject.php?role=hod" class="btn-primary" style="flex:1; text-align: center;">Approve</a>
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
