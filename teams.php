<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDept = $_SESSION['department'] ?? '';
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';

// Query teams with project and leader info
$sql = "SELECT t.*, p.title as project_title, u.name as leader_name,
        (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as member_count
        FROM teams t
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u ON t.leader_id = u.id";

if (strtolower($role) === 'departmentcoordinator') {
    $sql .= " WHERE t.department = ?";
    $sql .= " ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userDept]);
} else {
    $sql .= " ORDER BY t.created_at DESC";
    $stmt = $pdo->query($sql);
}
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Flash messages
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="ri-menu-line"></i>
                    </button>
                    <h1>Teams</h1>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Search teams...">
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo $userInitials; ?></div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>" style="padding:12px 16px;border-radius:8px;margin-bottom:16px;background:<?php echo $flashType === 'success' ? '#d4edda' : ($flashType === 'error' ? '#f8d7da' : '#d1ecf1'); ?>;color:<?php echo $flashType === 'success' ? '#155724' : ($flashType === 'error' ? '#721c24' : '#0c5460'); ?>;">
                        <?php echo htmlspecialchars($flashMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="content-header">
                    <h2>Project Teams</h2>
                </div>

                <?php if (empty($teams)): ?>
                <div class="teams-grid">
                    <div class="empty-state">
                        <i class="ri-team-line"></i>
                        <h3>No Teams Yet</h3>
                        <p>Teams will appear here once students submit projects with team members.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="teams-grid">
                    <?php foreach ($teams as $team):
                        $leaderInitials = strtoupper(substr($team['leader_name'] ?? 'NA', 0, 2));
                    ?>
                    <div class="team-card">
                        <div class="team-header">
                            <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                            <span class="team-badge"><?php echo (int)$team['member_count']; ?> Members</span>
                        </div>
                        <div class="team-project">
                            <i class="ri-folder-line"></i>
                            <span><?php echo htmlspecialchars($team['project_title'] ?? 'No project assigned'); ?></span>
                        </div>
                        <div class="team-members">
                            <div class="member">
                                <div class="member-avatar"><?php echo $leaderInitials; ?></div>
                                <div class="member-info">
                                    <span class="member-name"><?php echo htmlspecialchars($team['leader_name'] ?? 'Unassigned'); ?></span>
                                    <span class="member-role">Team Lead</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
