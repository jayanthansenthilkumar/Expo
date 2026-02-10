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
        'admin' => [
            'sparkAdmin.php', 'analytics.php', 'allProjects.php', 'users.php', 
            'departments.php', 'coordinators.php', 'schedule.php', 'announcements.php', 
            'judging.php', 'settings.php', 'database.php', 'profile.php',
            'logout.php', 'index.php'
        ],
        'departmentcoordinator' => [
            'departmentCoordinator.php', 'departmentStats.php', 'departmentProjects.php', 
            'reviewApprove.php', 'topProjects.php', 'studentList.php', 'teams.php', 
            'profile.php', 'settings.php', 'logout.php', 'index.php'
        ],
        'studentaffairs' => [
            'studentAffairs.php', 'analytics.php', 'allProjects.php', 'approvals.php', 
            'students.php', 'announcements.php', 'messages.php', 'profile.php', 
            'settings.php', 'logout.php', 'index.php',
            'users.php', 'departments.php', 'coordinators.php', 'schedule.php',
            'judging.php', 'database.php', 'guidelines.php'
        ],
        'student' => [
            'studentDashboard.php', 'myProjects.php', 'submitProject.php', 'myTeam.php',
            'schedule.php', 'guidelines.php', 'announcements.php', 'messages.php', 'profile.php', 'settings.php', 
            'logout.php', 'index.php'
        ]
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

    // Verify session integrity
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

// Prevent caching for all authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/**
 * Returns the list of departments a coordinator manages.
 * AIDS and AIML share a single coordinator ('sparkai'), so both are returned together.
 */
function getUserDepartments($department) {
    $sharedDepts = ['AIDS', 'AIML'];
    if (in_array(strtoupper($department), $sharedDepts)) {
        return $sharedDepts;
    }
    return [$department];
}

/**
 * Builds a SQL IN clause placeholder and flat param array for multi-department queries.
 * Returns ['placeholders' => '?,?', 'types' => 'ss', 'values' => ['AIDS','AIML']]
 */
function buildDeptFilter($department) {
    $depts = getUserDepartments($department);
    $placeholders = implode(',', array_fill(0, count($depts), '?'));
    $types = str_repeat('s', count($depts));
    return ['placeholders' => $placeholders, 'types' => $types, 'values' => $depts];
}

/**
 * For the FE (Freshmen Engineering) coordinator, students are identified by year='I year'
 * rather than by department, since students register under their actual dept (CSE, ECE, etc.).
 * Returns ['sql' => 'condition', 'types' => '...', 'values' => [...]]
 */
function buildStudentFilter($department) {
    if (strtoupper(trim($department)) === 'FE') {
        return ['sql' => "year = ?", 'types' => 's', 'values' => ['I year']];
    }
    $depts = getUserDepartments($department);
    $placeholders = implode(',', array_fill(0, count($depts), '?'));
    $types = str_repeat('s', count($depts));
    return ['sql' => "department IN ($placeholders)", 'types' => $types, 'values' => $depts];
}
?>