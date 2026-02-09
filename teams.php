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
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $userDept);
    mysqli_stmt_execute($stmt);
    $teamRes = mysqli_stmt_get_result($stmt);
} else {
    $sql .= " ORDER BY t.created_at DESC";
    $teamRes = mysqli_query($conn, $sql);
}
$teams = [];
while ($row = mysqli_fetch_assoc($teamRes)) { $teams[] = $row; }
if (isset($stmt)) { mysqli_stmt_close($stmt); }

// Flash messages
$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <script>
    <?php if ($successMsg): ?>
    Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#2563eb' });
    <?php endif; ?>
    </script>
</body>

</html>
