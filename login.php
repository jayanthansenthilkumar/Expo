<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $query = "SELECT * FROM users WHERE username = ? AND password = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $username, $password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];

            // Role-based redirection
            $redirectUrl = '';
            switch ($user['role']) {
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
                default:
                    $redirectUrl = 'studentDashboard.php';
            }

            // Store redirect URL for JavaScript
            $success = true;
        } else {
            $error = 'Invalid username or password';
        }
        mysqli_stmt_close($stmt);
    }
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
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
                SPARK <span>'26</span>
            </a>
            <div class="nav-menu">
                <a href="index.php#about" class="nav-link">About</a>
                <a href="index.php#tracks" class="nav-link">Tracks</a>
                <a href="index.php#schedule" class="nav-link">Schedule</a>
            </div>
            <a href="register.php" class="btn-primary">Register</a>
        </div>
    </nav>

    <!-- Login Form -->
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Login to SPARK'26 Dashboard</p>
            </div>

            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="ri-login-box-line"></i> Login
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
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
            text: 'Welcome back, <?php echo addslashes($user['name']); ?>!',
            confirmButtonColor: '#2563eb',
            timer: 1500,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            window.location.href = '<?php echo $redirectUrl; ?>';
        });
        <?php endif; ?>
    </script>
</body>

</html>
