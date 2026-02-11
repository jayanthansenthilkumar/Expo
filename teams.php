<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDept = $_SESSION['department'] ?? '';
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';

// Multi-department support (AIDS & AIML share one coordinator)
$deptFilter = buildDeptFilter($userDept);

// Query teams with project and leader info
$sql = "SELECT t.*, p.title as project_title, p.status as project_status, u.name as leader_name,
        (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as member_count
        FROM teams t
        LEFT JOIN projects p ON p.team_id = t.id
        LEFT JOIN users u ON t.leader_id = u.id";

if (strtolower($role) === 'departmentcoordinator') {
    $sql .= " WHERE t.department IN (" . $deptFilter['placeholders'] . ")";
    $sql .= " ORDER BY t.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $deptFilter['types'], ...$deptFilter['values']);
    mysqli_stmt_execute($stmt);
    $teamRes = mysqli_stmt_get_result($stmt);
} else {
    $sql .= " ORDER BY t.created_at DESC";
    $teamRes = mysqli_query($conn, $sql);
}
$teams = [];
while ($row = mysqli_fetch_assoc($teamRes)) {
    $teams[] = $row;
}
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}

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
            <?php
            $pageTitle = 'Teams';
            include 'includes/header.php';
            ?>

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
                            $statusColors = [
                                'approved' => 'background:#dcfce7; color:#166534; border-color:#bbf7d0;',
                                'rejected' => 'background:#fef2f2; color:#991b1b; border-color:#fecaca;',
                                'pending' => 'background:#fef3c7; color:#92400e; border-color:#fde68a;'
                            ];
                            $pStatus = $team['project_status'] ?? 'pending';
                            $statusStyle = $statusColors[$pStatus] ?? $statusColors['pending'];
                            ?>
                            <div class="team-card">
                                <div class="team-header">
                                    <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                    <span class="team-badge"><?php echo (int) $team['member_count']; ?> Members</span>
                                </div>
                                <?php if (!empty($team['project_title'])): ?>
                                    <div class="team-project assigned"
                                        style="<?php echo $statusStyle; ?> border:1px solid; display:flex; justify-content:space-between; align-items:center;">
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <i class="ri-folder-check-line"></i>
                                            <span
                                                style="font-weight:600;"><?php echo htmlspecialchars($team['project_title']); ?></span>
                                        </div>
                                        <span
                                            style="font-size:0.7rem; text-transform:uppercase; font-weight:700; padding:2px 6px; border-radius:4px; background:rgba(255,255,255,0.5);">
                                            <?php echo htmlspecialchars($pStatus); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="team-project unassigned" style="background:#f1f5f9; color:#64748b;">
                                        <i class="ri-folder-line"></i>
                                        <span>No project assigned</span>
                                    </div>
                                <?php endif; ?>
                                <div class="team-members">
                                    <div class="member">
                                        <div class="member-avatar"><?php echo $leaderInitials; ?></div>
                                        <div class="member-info">
                                            <span
                                                class="member-name"><?php echo htmlspecialchars($team['leader_name'] ?? 'Unassigned'); ?></span>
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

    <?php include 'includes/bot.php'; ?>
</body>

</html>