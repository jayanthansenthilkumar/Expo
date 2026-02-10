<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Student';
$userInitials = strtoupper(substr($userName, 0, 2));
$userId = $_SESSION['user_id'];

// Check if student has a team (needed for accurate project counts)
$myTeam = null;
$isLeader = false;
$teamCheck = mysqli_prepare($conn, "SELECT t.*, tm.role as my_role FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ?");
mysqli_stmt_bind_param($teamCheck, "i", $userId);
mysqli_stmt_execute($teamCheck);
$teamResult = mysqli_stmt_get_result($teamCheck);
$myTeam = mysqli_fetch_assoc($teamResult);
mysqli_stmt_close($teamCheck);

$teamMemberCount = 0;
if ($myTeam) {
    $isLeader = ((int) $myTeam['leader_id'] === (int) $userId);
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM team_members WHERE team_id = ?");
    mysqli_stmt_bind_param($countStmt, "i", $myTeam['id']);
    mysqli_stmt_execute($countStmt);
    $teamMemberCount = mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['cnt'];
    mysqli_stmt_close($countStmt);
}

// Fetch student stats (team-based if in a team, otherwise individual)
$totalProjects = 0;
$approvedProjects = 0;
$pendingProjects = 0;

if ($myTeam) {
    $stmt = mysqli_prepare($conn, "SELECT status, COUNT(*) as cnt FROM projects WHERE team_id = ? GROUP BY status");
    mysqli_stmt_bind_param($stmt, "i", $myTeam['id']);
} else {
    $stmt = mysqli_prepare($conn, "SELECT status, COUNT(*) as cnt FROM projects WHERE student_id = ? GROUP BY status");
    mysqli_stmt_bind_param($stmt, "i", $userId);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $totalProjects += $row['cnt'];
    if ($row['status'] === 'approved')
        $approvedProjects = $row['cnt'];
    if ($row['status'] === 'pending')
        $pendingProjects = $row['cnt'];
}
mysqli_stmt_close($stmt);

// Days to event
$eventDate = '2026-02-15';
$daysToEvent = max(0, (int) ((strtotime($eventDate) - time()) / 86400));

// Fetch recent announcements
$announcements = [];
$annResult = mysqli_query($conn, "SELECT a.*, u.name as author_name FROM announcements a JOIN users u ON a.author_id = u.id WHERE a.target_role IN ('all', 'student') ORDER BY a.created_at DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($annResult)) {
    $announcements[] = $row;
}

// Fetch upcoming schedule
$scheduleItems = [];
$schedResult = mysqli_query($conn, "SELECT * FROM schedule WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 3");
while ($row = mysqli_fetch_assoc($schedResult)) {
    $scheduleItems[] = $row;
}

// Team info already fetched above

// Check pending invitations for students without a team
$pendingInviteCount = 0;
if (!$myTeam) {
    $invStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM team_invitations WHERE invited_user_id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($invStmt, "i", $userId);
    mysqli_stmt_execute($invStmt);
    $pendingInviteCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($invStmt))['cnt'];
    mysqli_stmt_close($invStmt);
}

$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>


        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php
            $pageTitle = 'Dashboard';
            include 'includes/header.php';
            ?>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <?php if (!$myTeam): ?>
                    <!-- Team Registration Banner -->
                    <div
                        style="background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);color:#92400e;padding:1.25rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <i class="ri-error-warning-line" style="font-size:1.5rem;"></i>
                            <div>
                                <strong>You haven't joined a team yet!</strong>
                                <p style="font-size:0.85rem;opacity:0.9;">Register for SPARK'26 by creating or joining a
                                    team.<?php if ($pendingInviteCount > 0): ?> You have
                                        <strong><?php echo $pendingInviteCount; ?> pending
                                            invitation<?php echo $pendingInviteCount > 1 ? 's' : ''; ?></strong>!<?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="myTeam.php"
                            style="background:#92400e;color:#fff;padding:0.5rem 1.25rem;border-radius:8px;font-weight:600;text-decoration:none;font-size:0.9rem;"><?php echo $pendingInviteCount > 0 ? 'View Invitations' : 'Register Now'; ?></a>
                    </div>
                <?php endif; ?>

                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h2>
                    <p>Ready to showcase your innovation?
                        <?php echo ($myTeam && $isLeader) ? 'Submit your project and compete with the best minds on campus.' : 'Join a team to get started with SPARK\'26.'; ?>
                    </p>
                    <?php if ($myTeam && $isLeader): ?>
                        <a href="submitProject.php" class="btn-light">Submit Project</a>
                    <?php elseif ($myTeam): ?>
                        <a href="myProjects.php" class="btn-light">View Projects</a>
                    <?php else: ?>
                        <a href="myTeam.php" class="btn-light">Join a Team</a>
                    <?php endif; ?>
                    <div class="welcome-decoration">
                        <i class="ri-rocket-2-line"></i>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="ri-folder-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalProjects; ?></h3>
                            <p>Projects Submitted</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $approvedProjects; ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pendingProjects; ?></h3>
                            <p>Pending Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-calendar-event-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $daysToEvent; ?></h3>
                            <p>Days to Expo</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Quick Actions</h3>
                <div class="quick-actions">
                    <a href="myTeam.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon"
                            style="<?php echo $myTeam ? 'background:#dcfce7;' : 'background:#fef3c7;'; ?>">
                            <i class="ri-team-line"
                                style="<?php echo $myTeam ? 'color:#166534;' : 'color:#92400e;'; ?>"></i>
                        </div>
                        <div>
                            <h4><?php echo $myTeam ? $myTeam['team_name'] : 'Join/Create Team'; ?></h4>
                            <p><?php echo $myTeam ? $teamMemberCount . ' members &bull; ' . ucfirst($myTeam['my_role']) : 'Register for SPARK\'26'; ?>
                            </p>
                        </div>
                    </a>
                    <?php if ($myTeam && $isLeader): ?>
                        <a href="submitProject.php" class="action-card" style="text-decoration:none;color:inherit;">
                            <div class="action-icon">
                                <i class="ri-add-line"></i>
                            </div>
                            <div>
                                <h4>New Project</h4>
                                <p>Submit a new project</p>
                            </div>
                        </a>
                    <?php elseif (!$myTeam && $pendingInviteCount > 0): ?>
                        <a href="myTeam.php" class="action-card" style="text-decoration:none;color:inherit;">
                            <div class="action-icon" style="background:#dbeafe;">
                                <i class="ri-mail-line" style="color:#1d4ed8;"></i>
                            </div>
                            <div>
                                <h4>Invitations</h4>
                                <p><?php echo $pendingInviteCount; ?> pending
                                    invitation<?php echo $pendingInviteCount > 1 ? 's' : ''; ?></p>
                            </div>
                        </a>
                    <?php endif; ?>
                    <a href="myProjects.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-folder-line"></i>
                        </div>
                        <div>
                            <h4>My Projects</h4>
                            <p>View your submissions</p>
                        </div>
                    </a>
                    <a href="guidelines.php" class="action-card" style="text-decoration:none;color:inherit;">
                        <div class="action-icon">
                            <i class="ri-book-open-line"></i>
                        </div>
                        <div>
                            <h4>View Guidelines</h4>
                            <p>Read submission rules</p>
                        </div>
                    </a>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid" style="margin-top: 2rem;">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Recent Announcements</h3>
                            <a href="announcements.php" style="color: var(--primary); font-size: 0.9rem;">View All</a>
                        </div>
                        <div class="dash-card-body">
                            <?php if (empty($announcements)): ?>
                                <p style="color: var(--text-muted);">No announcements yet.</p>
                            <?php else: ?>
                                <?php foreach ($announcements as $ann): ?>
                                    <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                                        <h4 style="font-size: 0.95rem; margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($ann['title']); ?>
                                        </h4>
                                        <p style="font-size: 0.8rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars(substr($ann['message'], 0, 80)); ?>...
                                        </p>
                                        <span
                                            style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Upcoming Deadlines</h3>
                            <a href="schedule.php" style="color: var(--primary); font-size: 0.9rem;">View Schedule</a>
                        </div>
                        <div class="dash-card-body">
                            <?php if (empty($scheduleItems)): ?>
                                <p style="color: var(--text-muted);">No upcoming events.</p>
                            <?php else: ?>
                                <?php foreach ($scheduleItems as $event): ?>
                                    <div
                                        style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                                        <div
                                            style="width: 50px; height: 50px; background: var(--bg-surface); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <span
                                                style="font-size: 1.25rem; font-weight: 800; line-height: 1;"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                            <span
                                                style="font-size: 0.7rem; color: var(--text-muted);"><?php echo strtoupper(date('M', strtotime($event['event_date']))); ?></span>
                                        </div>
                                        <div>
                                            <h4 style="font-size: 0.95rem;"><?php echo htmlspecialchars($event['title']); ?>
                                            </h4>
                                            <p style="font-size: 0.8rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($event['event_type']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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