<?php
function checkUserAccess($isPublic = false)
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', 3600);
        session_set_cookie_params(0);
        session_start();
    }

    // If public page and NOT logged in, just return (allow access)
    if ($isPublic && !isset($_SESSION['userid'])) {
        return;
    }

    // If protected page and NOT logged in, redirect to login
    if (!isset($_SESSION['userid'])) {
        header('Location: login.php');
        exit();
    }

    // Check session timeout (30 minutes = 1800 seconds)
    // Using sliding expiration: Update time on every activity
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
        // Session has expired
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
    // Update last activity time to current time to keep session alive
    $_SESSION['login_time'] = time();

    // Get current page and user info
    $current_page = basename($_SERVER['PHP_SELF']);
    $user_role = $_SESSION['role'] ?? '';

    // Define allowed pages for each role
    // Adapting to existing project roles: 'admin', 'departmentcoordinator', 'studentaffairs', 'student'
    // Including index.php as valid for logged in users as well.
    $allowed_pages = [
        'admin' => ['sparkAdmin.php', 'logout.php', 'index.php'],
        'departmentcoordinator' => ['departmentCoordinator.php', 'logout.php', 'index.php'],
        'studentaffairs' => ['studentAffairs.php', 'logout.php', 'index.php'],
        'student' => ['studentDashboard.php', 'logout.php', 'index.php']
    ];

    // Check access rights
    if (array_key_exists($user_role, $allowed_pages)) {
        if (!in_array($current_page, $allowed_pages[$user_role])) {
            // Unauthorized page for this role -> DESTROY SESSION or Redirect
            // If specific page is not allowed, maybe just redirect to dashboard?
            // User snippet says destroy session.
            session_unset();
            session_destroy();
            header("Location: index.php");
            exit();
        }
    } else {
        // Invalid role
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Verify sessionStorage data exists (Frontend Check)
    echo "<script>
        if (!sessionStorage.getItem('userData')) {
             // If manual clearing happens or mismatch, we might want to logout
             // window.location.href = 'logout.php'; 
        }
    </script>";
}

// Prevent caching for all authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>