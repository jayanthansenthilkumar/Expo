<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php';

// Check for flash messages FIRST (before redirect check)
$error = '';
$success = false;
$successMsg = '';
$redirectUrl = '';

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success']) && isset($_SESSION['redirect_url'])) {
    $success = true;
    $successMsg = $_SESSION['success'];
    $redirectUrl = $_SESSION['redirect_url'];
    unset($_SESSION['success'], $_SESSION['redirect_url']);
}

// If already logged in AND no success message pending, redirect to dashboard
if (isset($_SESSION['user_id']) && !$success && empty($error)) {
    $role = $_SESSION['role'];
    $redirectUrl = 'studentDashboard.php';
    switch ($role) {
        case 'student':
            $redirectUrl = 'studentDashboard.php';
            break;
        case 'studentaffairs':
            $redirectUrl = 'studentAffairs.php';
            break;
        case 'departmentcoordinator':
            $redirectUrl = 'departmentCoordinator.php';
            break;
        case 'admin':
            $redirectUrl = 'sparkAdmin.php';
            break;
    }
    header("Location: $redirectUrl");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Login Form -->
    <div class="auth-container">
        <div class="auth-grid-split">
            <div class="auth-info-side">
                <a href="index.php" class="btn-back-home"><i class="ri-arrow-left-line"></i> Back to Home</a>
                <h1>SPARK <span>'26</span></h1>
                <p>Login to Access Your Dashboard</p>
            </div>
            <div class="auth-form-side">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2>Welcome Back</h2>
                        <p>Login to SPARK'26 Dashboard</p>
                    </div>

                    <form id="loginForm" method="POST" action="sparkBackend.php">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-input"
                                placeholder="Enter your username" required>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-input"
                                placeholder="Enter your password" required>
                        </div>

                        <button type="submit" name="login" class="btn-submit">
                            <i class="ri-login-box-line"></i> Login
                        </button>
                    </form>

                    <div class="auth-footer">
                        Don't have an account? <a href="register.php">Register here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show error message
        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '<?php echo addslashes($error); ?>',
                confirmButtonColor: '#D97706'
            });
        <?php endif; ?>

        // Show success and redirect
        <?php if ($success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Login Successful',
                text: '<?php echo addslashes($successMsg); ?>',
                confirmButtonColor: '#D97706',
                timer: 1500,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                sessionStorage.setItem('userData', JSON.stringify({
                    loggedIn: true,
                    username: '<?php echo addslashes($_SESSION['username'] ?? ''); ?>'
                }));
                window.location.href = '<?php echo addslashes($redirectUrl); ?>';
            });
        <?php endif; ?>
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>