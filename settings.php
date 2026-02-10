<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userEmail = $_SESSION['email'] ?? '';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');

// Flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Settings';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="settings-container">
                    <div class="settings-section">
                        <h3><i class="ri-user-line"></i> Profile Settings</h3>
                        <form action="sparkBackend.php" method="POST">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text" id="fullName" name="fullName"
                                    value="<?php echo htmlspecialchars($userName); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo htmlspecialchars($userEmail); ?>">
                            </div>

                            <button type="submit" class="btn-primary">Save Changes</button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h3><i class="ri-lock-line"></i> Change Password</h3>
                        <form action="sparkBackend.php" method="POST">
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group">
                                <label for="currentPassword">Current Password</label>
                                <input type="password" id="currentPassword" name="currentPassword" required>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" id="newPassword" name="newPassword" required>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <input type="password" id="confirmPassword" name="confirmPassword" required>
                            </div>

                            <button type="submit" class="btn-primary">Update Password</button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h3><i class="ri-notification-line"></i> Notification Preferences</h3>
                        <div class="toggle-group">
                            <div class="toggle-item">
                                <div>
                                    <strong>Email Notifications</strong>
                                    <p>Receive email updates about your projects</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="toggle-item">
                                <div>
                                    <strong>Announcement Alerts</strong>
                                    <p>Get notified about new announcements</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="toggle-item">
                                <div>
                                    <strong>Deadline Reminders</strong>
                                    <p>Receive reminders before deadlines</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        <?php if ($success): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($success); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($error): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($error); ?>', confirmButtonColor: '#2563eb' });
        <?php endif; ?>
    </script>
</body>

</html>