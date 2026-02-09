<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>

    <?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    ?>

    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
                SPARK <span>'26</span>
            </a>
            <div class="nav-menu">
                <a href="studentDashboard.php" class="nav-link">Dashboard</a>
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
                <div
                    style="width:100%; height:100%; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:2rem; color:#64748b;">
                    <i class="ri-user-line"></i>
                </div>
            </div>

            <h1 id="pName" style="font-size: 1.8rem; margin-bottom: 0.25rem;">Loading...</h1>
            <p id="pRole" style="color: var(--text-muted); margin-bottom: 1rem;">...</p>
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
                        <div class="info-val" id="pEmail">-</div>
                    </div>
                    <div>
                        <div class="info-label">Phone</div>
                        <div class="info-val">+1 (555) 000-0000</div>
                    </div>
                </div>

                <div class="info-row">
                    <div>
                        <div class="info-label">College</div>
                        <div class="info-val" id="pCollege">-</div>
                    </div>
                    <div>
                        <div class="info-label">Department</div>
                        <div class="info-val">Computer Science & Engineering</div>
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1.5rem;">Badges & Achievements</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <span
                            style="padding: 0.5rem 1rem; background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
                            🏆 Hackathon Participant
                        </span>
                        <span
                            style="padding: 0.5rem 1rem; background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $.ajax({
                url: 'api/get_profile.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        const user = response.data;
                        $('#pName').text(user.first_name + ' ' + user.last_name);
                        $('#pRole').text(user.role.charAt(0).toUpperCase() + user.role.slice(1) + ' • ' + (user.college || 'College'));
                        $('#pEmail').text(user.email);
                        $('#pCollege').text(user.college || 'Not set');
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    console.error('Error fetching profile');
                }
            });
        });
    </script>

</body>

</html>