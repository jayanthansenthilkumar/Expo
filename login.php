<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    $redirectUrl = 'studentDashboard.php'; // Default
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

$error = '';
$success = false;
$redirectUrl = '';
$userName = ''; // For welcome message

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = true;
    $userName = isset($_SESSION['name']) ? $_SESSION['name'] : '';
    // Typically the success message itself is stored in $_SESSION['success'] as a string,
    // but the original code used $success = true boolean and pulled name from $user array.
    // The sparkBackend sets $_SESSION['success'] string and $_SESSION['redirect_url'].
    // We need to adapt.

    // sparkBackend.php logic: $_SESSION['success'] = "Welcome back..."; $_SESSION['redirect_url'] = "...";
    // We should use those.
    $successMsg = $_SESSION['success']; // String
    $redirectUrl = $_SESSION['redirect_url'];

    unset($_SESSION['success']);
    unset($_SESSION['redirect_url']);
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
                confirmButtonColor: '#2563eb'
            });
        <?php endif; ?>

        // Show success and redirect
        <?php if (isset($success) && $success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Login Successful',
                text: '<?php echo isset($successMsg) ? addslashes($successMsg) : "Welcome back!"; ?>',
                confirmButtonColor: '#2563eb',
                timer: 1500,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                // Set session storage as required by auth check
                sessionStorage.setItem('userData', JSON.stringify({
                    loggedIn: true,
                    username: '<?php echo isset($_SESSION['username']) ? addslashes($_SESSION['username']) : ""; ?>'
                }));
                window.location.href = '<?php echo $redirectUrl; ?>';
            });
        <?php endif; ?>
    </script>
</body>

</html>