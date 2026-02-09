<?php
// Get current page to set active class
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? strtolower($_SESSION['user_role'] ?? 'guest');

// Define menu structure for each role
$menus = [
    'student' => [
        'Main' => [
            ['link' => 'studentDashboard.php', 'icon' => 'ri-dashboard-line', 'text' => 'Dashboard'],
            ['link' => '#', 'icon' => 'ri-folder-line', 'text' => 'My Projects'],
            ['link' => '#', 'icon' => 'ri-add-circle-line', 'text' => 'Submit Project']
        ],
        'Resources' => [
            ['link' => '#', 'icon' => 'ri-calendar-line', 'text' => 'Schedule'],
            ['link' => '#', 'icon' => 'ri-file-list-line', 'text' => 'Guidelines'],
            ['link' => '#', 'icon' => 'ri-notification-line', 'text' => 'Announcements']
        ],
        'Account' => [
            ['link' => '#', 'icon' => 'ri-user-line', 'text' => 'Profile'],
            ['link' => '#', 'icon' => 'ri-settings-line', 'text' => 'Settings']
        ]
    ],
    'admin' => [
        'Overview' => [
            ['link' => 'sparkAdmin.php', 'icon' => 'ri-dashboard-line', 'text' => 'Dashboard'],
            ['link' => '#', 'icon' => 'ri-bar-chart-box-line', 'text' => 'Analytics']
        ],
        'Management' => [
            ['link' => '#', 'icon' => 'ri-folder-line', 'text' => 'All Projects'],
            ['link' => '#', 'icon' => 'ri-user-line', 'text' => 'Users'],
            ['link' => '#', 'icon' => 'ri-building-line', 'text' => 'Departments'],
            ['link' => '#', 'icon' => 'ri-team-line', 'text' => 'Coordinators']
        ],
        'Event' => [
            ['link' => '#', 'icon' => 'ri-calendar-line', 'text' => 'Schedule'],
            ['link' => '#', 'icon' => 'ri-megaphone-line', 'text' => 'Announcements'],
            ['link' => '#', 'icon' => 'ri-award-line', 'text' => 'Judging']
        ],
        'System' => [
            ['link' => '#', 'icon' => 'ri-settings-3-line', 'text' => 'Settings'],
            ['link' => '#', 'icon' => 'ri-database-line', 'text' => 'Database']
        ]
    ],
    'studentaffairs' => [
        'Overview' => [
            ['link' => 'studentAffairs.php', 'icon' => 'ri-dashboard-line', 'text' => 'Dashboard'],
            ['link' => '#', 'icon' => 'ri-bar-chart-line', 'text' => 'Analytics']
        ],
        'Management' => [
            ['link' => '#', 'icon' => 'ri-folder-line', 'text' => 'All Projects'],
            ['link' => '#', 'icon' => 'ri-checkbox-circle-line', 'text' => 'Approvals'],
            ['link' => '#', 'icon' => 'ri-group-line', 'text' => 'Students']
        ],
        'Communication' => [
            ['link' => '#', 'icon' => 'ri-megaphone-line', 'text' => 'Announcements'],
            ['link' => '#', 'icon' => 'ri-mail-line', 'text' => 'Messages']
        ],
        'Account' => [
            ['link' => '#', 'icon' => 'ri-user-line', 'text' => 'Profile'],
            ['link' => '#', 'icon' => 'ri-settings-line', 'text' => 'Settings']
        ]
    ],
    'departmentcoordinator' => [
        'Overview' => [
            ['link' => 'departmentCoordinator.php', 'icon' => 'ri-dashboard-line', 'text' => 'Dashboard'],
            ['link' => '#', 'icon' => 'ri-bar-chart-line', 'text' => 'Department Stats']
        ],
        'Projects' => [
            ['link' => '#', 'icon' => 'ri-folder-line', 'text' => 'Department Projects'],
            ['link' => '#', 'icon' => 'ri-checkbox-circle-line', 'text' => 'Review & Approve'],
            ['link' => '#', 'icon' => 'ri-star-line', 'text' => 'Top Projects']
        ],
        'Students' => [
            ['link' => '#', 'icon' => 'ri-group-line', 'text' => 'Student List'],
            ['link' => '#', 'icon' => 'ri-team-line', 'text' => 'Teams']
        ],
        'Account' => [
            ['link' => '#', 'icon' => 'ri-user-line', 'text' => 'Profile'],
            ['link' => '#', 'icon' => 'ri-settings-line', 'text' => 'Settings']
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
            SPARK <span>'26</span>
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

    <div class="sidebar-footer">
        <a href="logout.php" class="menu-item" style="color: #ef4444;">
            <i class="ri-logout-box-line"></i>
            Logout
        </a>
    </div>
</aside>