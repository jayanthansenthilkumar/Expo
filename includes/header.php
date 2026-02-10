<?php
// includes/header.php

// Ensure session is started (usually done in auth.php, but safety check)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure DB connection if not present
if (!isset($conn)) {
    $rootPath = dirname(__DIR__);
    if (file_exists($rootPath . '/db.php')) {
        require_once $rootPath . '/db.php';
    }
}

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = $_SESSION['role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? 0;

// Default Notification Data
$notificationCount = 0;
$notificationTitle = "No new notifications";

// Use existing pendingInviteCount if available (from parent page)
if (isset($pendingInviteCount)) {
    $notificationCount = $pendingInviteCount;
    if ($notificationCount > 0) {
        $notificationTitle = $notificationCount . ' pending invitation(s)';
    }
} elseif (isset($conn) && $userId) {
    if ($userRole === 'student') {
        // Fallback Logic for Student: Check pending team invitations if not provided
        $teamCheckHeader = mysqli_prepare($conn, "SELECT id FROM team_members WHERE user_id = ?");
        if ($teamCheckHeader) {
            mysqli_stmt_bind_param($teamCheckHeader, "i", $userId);
            mysqli_stmt_execute($teamCheckHeader);
            mysqli_stmt_store_result($teamCheckHeader);
            $hasTeam = mysqli_stmt_num_rows($teamCheckHeader) > 0;
            mysqli_stmt_close($teamCheckHeader);

            if (!$hasTeam) {
                // Check pending invitations
                $invStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM team_invitations WHERE invited_user_id = ? AND status = 'pending'");
                if ($invStmt) {
                    mysqli_stmt_bind_param($invStmt, "i", $userId);
                    mysqli_stmt_execute($invStmt);
                    $res = mysqli_stmt_get_result($invStmt);
                    if ($row = mysqli_fetch_assoc($res)) {
                        $count = (int) $row['cnt'];
                        if ($count > 0) {
                            $notificationCount = $count;
                            $notificationTitle = $count . ' pending invitation(s)';
                        }
                    }
                    mysqli_stmt_close($invStmt);
                }
            }
        }
    } elseif ($userRole === 'departmentcoordinator') {
        // Logic for Coordinator
    } elseif ($userRole === 'admin') {
        // Logic for Admin
    }
}
?>
<header class="dashboard-header">
    <div class="header-left">
        <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ri-menu-line"></i>
        </button>
        <h1>
            <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
        </h1>
    </div>
    <div class="header-right">
        <div class="header-search">
            <i class="ri-search-line"></i>
            <input type="text" placeholder="Search...">
        </div>

        <div class="header-icon" title="<?php echo htmlspecialchars($notificationTitle); ?>">
            <i class="ri-notification-3-line"></i>
            <?php if ($notificationCount > 0): ?>
                <span class="badge"></span>
            <?php endif; ?>
        </div>

        <div class="user-profile" onclick="toggleUserDropdown(event)">
            <div class="user-avatar">
                <?php echo $userInitials; ?>
            </div>
            <div class="user-info">
                <span class="user-name">
                    <?php echo htmlspecialchars($userName); ?>
                </span>
                <span class="user-role">
                    <?php echo ucfirst($userRole); ?>
                </span>
            </div>

            <!-- User Dropdown -->
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <span>
                        <?php echo htmlspecialchars($userName); ?>
                    </span>
                    <small>
                        <?php echo ucfirst($userRole); ?>
                    </small>
                </div>
                <a href="profile.php"><i class="ri-user-line"></i> Profile</a>
                <a href="settings.php"><i class="ri-settings-line"></i> Settings</a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" style="color: #ef4444;"><i class="ri-logout-box-line"></i> Logout</a>
            </div>
        </div>
    </div>
</header>