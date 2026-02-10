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
            'judging.php', 'settings.php', 'database.php', 'departmentStats.php',
            'messages.php', 'profile.php', 'logout.php', 'index.php'
        ],
        'departmentcoordinator' => [
            'departmentCoordinator.php', 'departmentStats.php', 'departmentProjects.php', 
            'reviewApprove.php', 'topProjects.php', 'studentList.php', 'teams.php', 
            'messages.php', 'announcements.php', 'profile.php', 'settings.php', 'logout.php', 'index.php'
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

    // Verify session integrity (check both keys set during login)
    if (!isset($_SESSION['userid']) || !isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
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
 * Normalizes various representations of "first year" to a canonical form.
 * Handles: "I year", "I Year", "1", "1st year", "First year", "first", "I" etc.
 * Returns the canonical year string (e.g. "I year") or the original value if no match.
 */
function normalizeYear($year) {
    $y = strtolower(trim($year));
    // Match first year variants
    if (preg_match('/^(i\s*year|i\s*$|1st?\s*(year)?|first\s*(year)?|1\s*$)/i', $y)) {
        return 'I year';
    }
    if (preg_match('/^(ii\s*year|ii\s*$|2nd?\s*(year)?|second\s*(year)?|2\s*$)/i', $y)) {
        return 'II year';
    }
    if (preg_match('/^(iii\s*year|iii\s*$|3rd?\s*(year)?|third\s*(year)?|3\s*$)/i', $y)) {
        return 'III year';
    }
    if (preg_match('/^(iv\s*year|iv\s*$|4th?\s*(year)?|fourth\s*(year)?|4\s*$)/i', $y)) {
        return 'IV year';
    }
    return $year; // Return original if no match
}

/**
 * Checks if a student is a first-year engineering student (not MBA/MCA).
 * These students are routed to the FE (Freshmen Engineering) coordinator.
 */
function isFirstYearEngineering($year, $department) {
    $normalizedYear = normalizeYear($year);
    $excludedDepts = ['MBA', 'MCA'];
    return ($normalizedYear === 'I year' && !in_array(strtoupper($department), $excludedDepts));
}

/**
 * Returns the routing department for coordinator assignment.
 * First-year students (except MBA/MCA) are routed to 'FE' coordinator.
 * All others go to their actual department coordinator.
 */
function getRoutingDepartment($year, $department) {
    if (isFirstYearEngineering($year, $department)) {
        return 'FE';
    }
    return $department;
}

/**
 * Builds a SQL WHERE clause fragment for the FE coordinator to query students.
 * FE coordinator needs: all students with year='I year' AND department NOT IN ('MBA','MCA').
 * Returns ['where' => "u.year = ? AND u.department NOT IN ('MBA','MCA')", 'types' => 's', 'values' => ['I year']]
 */
function buildFEStudentFilter() {
    return [
        'where' => "u.year = ? AND u.department NOT IN ('MBA','MCA')",
        'types' => 's',
        'values' => ['I year']
    ];
}

/**
 * Builds a SQL WHERE clause fragment for the FE coordinator to query teams.
 * Teams created by first-year students are tagged with department='FE'.
 */
function buildFETeamFilter() {
    return [
        'where' => "t.department = ?",
        'types' => 's',
        'values' => ['FE']
    ];
}

/**
 * Builds a SQL WHERE clause fragment for the FE coordinator to query projects.
 * Since projects use the student's actual department (not 'FE'), we must JOIN
 * on users to find projects submitted by first-year non-MBA/MCA students.
 * Returns conditions to be used with:
 *   SELECT p.* FROM projects p JOIN users u ON p.student_id = u.id WHERE {where}
 */
function buildFEProjectFilter() {
    return [
        'where' => "u.year = ? AND u.department NOT IN ('MBA','MCA')",
        'types' => 's',
        'values' => ['I year']
    ];
}

/**
 * For non-FE, non-MBA/MCA department coordinators: builds a WHERE clause that
 * excludes first-year students from their student list (those go to FE).
 * Usage: WHERE u.department IN ($dp) AND u.role='student' AND {exclude_where}
 */
function buildExcludeFirstYearFilter() {
    return [
        'where' => "(u.year IS NULL OR u.year != ?)",
        'types' => 's',
        'values' => ['I year']
    ];
}
?>