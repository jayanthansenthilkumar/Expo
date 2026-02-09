<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php'; // Include auth.php to access checkUserAccess function if needed, or ensuring standardization

// Initialize variables to store in session if needed, though we will set them directly before redirect code
// Validation helper functions could go here

// ==========================================
// HANDLE REGISTRATION
// ==========================================
if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $reg_no = trim($_POST['reg_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($name) || empty($username) || empty($department) || empty($year) || empty($reg_no) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'All fields are required';
        header("Location: register.php");
        exit();
    } elseif (strlen($reg_no) !== 12) {
        $_SESSION['error'] = 'Register number must be 12 characters';
        header("Location: register.php");
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email address';
        header("Location: register.php");
        exit();
    } else {
        // Check if username, email or reg_no already exists
        $checkQuery = "SELECT id FROM users WHERE username = ? OR email = ? OR reg_no = ?";
        $stmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $reg_no);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($result)) {
            $_SESSION['error'] = 'Username, Email or Register Number already exists';
            header("Location: register.php");
            exit();
        } else {
            // Insert new user
            $insertQuery = "INSERT INTO users (name, username, department, year, reg_no, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'student')";
            $stmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($stmt, "sssssss", $name, $username, $department, $year, $reg_no, $email, $password);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Registration successful! You can now login.';
                header("Location: register.php");
                exit();
            } else {
                $_SESSION['error'] = 'Registration failed. Please try again.';
                header("Location: register.php");
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// ==========================================
// HANDLE LOGIN
// ==========================================
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Please enter both username and password';
        header("Location: login.php");
        exit();
    } else {
        $query = "SELECT * FROM users WHERE username = ? AND password = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $username, $password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            // Set session variables - Updated to match auth.php requirements
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['userid'] = $user['id']; // Duplicate for compatibility with auth.php
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['login_time'] = time(); // Required for timeout check

            // Role-based redirection logic
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

            $_SESSION['success'] = 'Welcome back, ' . $user['name'] . '!';
            $_SESSION['redirect_url'] = $redirectUrl;

            header("Location: login.php");
            exit();

        } else {
            $_SESSION['error'] = 'Invalid username or password';
            header("Location: login.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// ==========================================
// HANDLE LOGOUT
// ==========================================
if (isset($_GET['logout']) || isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>