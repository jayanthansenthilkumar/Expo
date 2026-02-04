<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .profile-header-bg {
            height: 200px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            margin-top: 60px; /* Nav offseet */
        }
        
        .profile-card-layout {
            max-width: 900px;
            margin: -60px auto 4rem; /* Overlap header */
            padding: 0 1.5rem;
            position: relative;
        }

        .main-profile-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .avatar-lg {
            width: 120px;
            height: 120px;
            background: white;
            padding: 4px;
            border-radius: 50%;
            margin-top: -80px; /* Pull up */
            margin-bottom: 1rem;
            position: relative;
        }
        .avatar-lg img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border);
        }

        .profile-tabs {
            display: flex;
            justify-content: center;
            gap: 2rem;
            border-bottom: 1px solid var(--border);
            margin-top: 2rem;
            width: 100%;
        }
        .p-tab {
            padding: 1rem;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: 500;
        }
        .p-tab.active {
            color: var(--primary);
            border-color: var(--primary);
        }

        .p-content {
            width: 100%;
            text-align: left;
            padding: 2rem 0;
        }

        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 600px) { .info-row { grid-template-columns: 1fr; } }

        .info-label { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.25rem; }
        .info-val { font-size: 1rem; color: var(--text-main); font-weight: 500; }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
                SPARK <span>'26</span>
            </a>
            <div class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
            </div>
            <a href="login.php" class="btn-outline">Logout</a>
        </div>
    </nav>

    <!-- Colored Background header -->
    <div class="profile-header-bg"></div>

    <!-- Main Card -->
    <div class="profile-card-layout">
        <div class="main-profile-card">
            
            <div class="avatar-lg">
                <!-- Placeholder for user avatar -->
                <div style="width:100%; height:100%; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:2rem; color:#64748b;">
                    <i class="ri-user-line"></i>
                </div>
            </div>

            <h1 style="font-size: 1.8rem; margin-bottom: 0.25rem;">Alex Morgan</h1>
            <p style="color: var(--text-muted); margin-bottom: 1rem;">Computer Science Student • Class of 2026</p>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn-outline" style="padding: 0.4rem 1rem; font-size: 0.9rem;">Edit Profile</button>
            </div>

            <!-- Tabs -->
            <div class="profile-tabs">
                <div class="p-tab active">About</div>
                <div class="p-tab">Activity</div>
            </div>

            <!-- Content -->
            <div class="p-content">
                <h3 style="margin-bottom: 1.5rem;">Personal Information</h3>
                
                <div class="info-row">
                    <div>
                        <div class="info-label">Email Address</div>
                        <div class="info-val">alex.morgan@student.college.edu</div>
                    </div>
                    <div>
                        <div class="info-label">Phone</div>
                        <div class="info-val">+1 (555) 123-4567</div>
                    </div>
                </div>
                
                <div class="info-row">
                    <div>
                        <div class="info-label">Student ID</div>
                        <div class="info-val">CS-2022-045</div>
                    </div>
                    <div>
                        <div class="info-label">Department</div>
                        <div class="info-val">Computer Science & Engineering</div>
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1.5rem;">Badges & Achievements</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <span style="padding: 0.5rem 1rem; background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
                            🏆 Hackathon Winner 2025
                        </span>
                        <span style="padding: 0.5rem 1rem; background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
                            ✨ Early Bird
                        </span>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="text-center" style="font-size: 0.9rem; color: #64748b; padding-bottom: 2rem;">
                &copy; 2026 College Innovation Council. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
