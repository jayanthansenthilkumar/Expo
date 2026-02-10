<?php
// Get current page to set active class
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'guest';

// Check if student is a team leader (for sidebar display)
$_isTeamLeader = false;
if ($user_role === 'student' && isset($conn) && isset($_SESSION['user_id'])) {
    $_tlStmt = mysqli_prepare($conn, "SELECT t.leader_id FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ?");
    if ($_tlStmt) {
        mysqli_stmt_bind_param($_tlStmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($_tlStmt);
        $_tlRow = mysqli_fetch_assoc(mysqli_stmt_get_result($_tlStmt));
        if ($_tlRow && (int)$_tlRow['leader_id'] === (int)$_SESSION['user_id']) {
            $_isTeamLeader = true;
        }
        mysqli_stmt_close($_tlStmt);
    }
}

// Define menu structure for each role
$studentMain = [
    ['link' => 'studentDashboard.php', 'icon' => 'ri-dashboard-line', 'text' => 'Dashboard'],
    ['link' => 'myTeam.php', 'icon' => 'ri-team-line', 'text' => 'My Team'],
    ['link' => 'myProjects.php', 'icon' => 'ri-folder-line', 'text' => 'My Projects'],
];
if ($_isTeamLeader) {
    $studentMain[] = ['link' => 'submitProject.php', 'icon' => 'ri-add-circle-line', 'text' => 'Submit Project'];
}

$menus = [
    'student' => [
        'Main' => $studentMain,
        'Resources' => [
            ['link' => 'schedule.php', 'icon' => 'ri-calendar-line', 'text' => 'Schedule'],
            ['link' => 'guidelines.php', 'icon' => 'ri-file-list-line', 'text' => 'Guidelines'],
            ['link' => 'announcements.php', 'icon' => 'ri-notification-line', 'text' => 'Announcements']
        ],
        'Account' => [
            ['link' => 'profile.php', 'icon' => 'ri-user-line', 'text' => 'Profile'],
            ['link' => 'settings.php', 'icon' => 'ri-settings-line', 'text' => 'Settings']
        ]
    ],
    'admin' => [
        'Overview' => [
            ['link' => 'sparkAdmin.php', 'icon' => 'ri-dashboard-line', 'text' => 'Dashboard'],
            ['link' => 'analytics.php', 'icon' => 'ri-bar-chart-box-line', 'text' => 'Analytics']
        ],
        'Management' => [
            ['link' => 'allProjects.php', 'icon' => 'ri-folder-line', 'text' => 'All Projects'],
            ['link' => 'approvals.php', 'icon' => 'ri-checkbox-circle-line', 'text' => 'Approvals'],
            ['link' => 'users.php', 'icon' => 'ri-user-line', 'text' => 'Users'],
            ['link' => 'students.php', 'icon' => 'ri-group-line', 'text' => 'Students'],
            ['link' => 'departments.php', 'icon' => 'ri-building-line', 'text' => 'Departments'],
            ['link' => 'coordinators.php', 'icon' => 'ri-team-line', 'text' => 'Coordinators']
        ],
        'Event' => [
            ['link' => 'schedule.php', 'icon' => 'ri-calendar-line', 'text' => 'Schedule'],
            ['link' => 'announcements.php', 'icon' => 'ri-megaphone-line', 'text' => 'Announcements'],
            ['link' => 'judging.php', 'icon' => 'ri-award-line', 'text' => 'Judging'],
            ['link' => 'guidelines.php', 'icon' => 'ri-file-list-line', 'text' => 'Guidelines'],
            ['link' => 'messages.php', 'icon' => 'ri-mail-line', 'text' => 'Messages']
        ],
        'System' => [
            ['link' => 'profile.php', 'icon' => 'ri-user-line', 'text' => 'Profile'],
            ['link' => 'settings.php', 'icon' => 'ri-settings-3-line', 'text' => 'Settings'],
            ['link' => 'database.php', 'icon' => 'ri-database-line', 'text' => 'Database']
        ]
    ],
    'studentaffairs' => [
        'Overview' => [
            ['link' => 'studentAffairs.php', 'icon' => 'ri-dashboard-line', 'text' => 'Dashboard'],
            ['link' => 'analytics.php', 'icon' => 'ri-bar-chart-line', 'text' => 'Analytics']
        ],
        'Management' => [
            ['link' => 'allProjects.php', 'icon' => 'ri-folder-line', 'text' => 'All Projects'],
            ['link' => 'approvals.php', 'icon' => 'ri-checkbox-circle-line', 'text' => 'Approvals'],
            ['link' => 'users.php', 'icon' => 'ri-user-line', 'text' => 'Users'],
            ['link' => 'students.php', 'icon' => 'ri-group-line', 'text' => 'Students'],
            ['link' => 'departments.php', 'icon' => 'ri-building-line', 'text' => 'Departments'],
            ['link' => 'coordinators.php', 'icon' => 'ri-team-line', 'text' => 'Coordinators']
        ],
        'Event' => [
            ['link' => 'schedule.php', 'icon' => 'ri-calendar-line', 'text' => 'Schedule'],
            ['link' => 'announcements.php', 'icon' => 'ri-megaphone-line', 'text' => 'Announcements'],
            ['link' => 'judging.php', 'icon' => 'ri-award-line', 'text' => 'Judging'],
            ['link' => 'guidelines.php', 'icon' => 'ri-file-list-line', 'text' => 'Guidelines'],
            ['link' => 'messages.php', 'icon' => 'ri-mail-line', 'text' => 'Messages']
        ],
        'System' => [
            ['link' => 'profile.php', 'icon' => 'ri-user-line', 'text' => 'Profile'],
            ['link' => 'settings.php', 'icon' => 'ri-settings-3-line', 'text' => 'Settings'],
            // ['link' => 'database.php', 'icon' => 'ri-database-line', 'text' => 'Database']
        ]
    ],
    'departmentcoordinator' => [
        'Overview' => [
            ['link' => 'departmentCoordinator.php', 'icon' => 'ri-dashboard-line', 'text' => 'Dashboard'],
            ['link' => 'departmentStats.php', 'icon' => 'ri-bar-chart-line', 'text' => 'Department Stats']
        ],
        'Projects' => [
            ['link' => 'departmentProjects.php', 'icon' => 'ri-folder-line', 'text' => 'Department Projects'],
            ['link' => 'reviewApprove.php', 'icon' => 'ri-checkbox-circle-line', 'text' => 'Review & Approve'],
            ['link' => 'topProjects.php', 'icon' => 'ri-star-line', 'text' => 'Top Projects']
        ],
        'Students' => [
            ['link' => 'studentList.php', 'icon' => 'ri-group-line', 'text' => 'Student List'],
            ['link' => 'teams.php', 'icon' => 'ri-team-line', 'text' => 'Teams']
        ],
        'Account' => [
            ['link' => 'profile.php', 'icon' => 'ri-user-line', 'text' => 'Profile'],
            ['link' => 'settings.php', 'icon' => 'ri-settings-line', 'text' => 'Settings']
        ]
    ]
];

// Fallback for role mismatch or empty role
$role_menu = $menus[$user_role] ?? [];

// In case auth.php hasn't been included (unlikely given the usage, but good practice), ensure session is started if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="logo">
            <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
            SPARK<span>'26</span>
        </a>
    </div>

    <nav class="sidebar-menu">
        <?php if (empty($role_menu)): ?>
            <div class="menu-label">Menu</div>
            <a href="index.php" class="menu-item active">
                <i class="ri-home-line"></i>
                Home
            </a>
        <?php else: ?>
            <?php foreach ($role_menu as $label => $items): ?>
                <div class="menu-label">
                    <?php echo htmlspecialchars($label); ?>
                </div>
                <?php foreach ($items as $item):
                    $active = ($current_page === $item['link']) ? 'active' : '';
                    ?>
                    <a href="<?php echo htmlspecialchars($item['link']); ?>" class="menu-item <?php echo $active; ?>">
                        <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                        <?php echo htmlspecialchars($item['text']); ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <!-- <div class="sidebar-footer">
    </div> -->
</aside>