<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Student';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Student');
$userId = $_SESSION['user_id'];

// Check if student is in a team
$myTeamId = null;
$isLeader = false;
$teamCheck = mysqli_prepare($conn, "SELECT t.id, t.leader_id FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ?");
mysqli_stmt_bind_param($teamCheck, "i", $userId);
mysqli_stmt_execute($teamCheck);
$teamRow = mysqli_fetch_assoc(mysqli_stmt_get_result($teamCheck));
if ($teamRow) {
    $myTeamId = $teamRow['id'];
    $isLeader = ((int) $teamRow['leader_id'] === (int) $userId);
}
mysqli_stmt_close($teamCheck);

// Fetch team's projects (all projects linked to the team) or individual projects if no team
$projects = [];
if ($myTeamId) {
    $stmt = mysqli_prepare($conn, "SELECT p.*, u.name as reviewer_name, us.name as submitter_name FROM projects p LEFT JOIN users u ON p.reviewed_by = u.id LEFT JOIN users us ON p.student_id = us.id WHERE p.team_id = ? ORDER BY p.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $myTeamId);
} else {
    $stmt = mysqli_prepare($conn, "SELECT p.*, u.name as reviewer_name, NULL as submitter_name FROM projects p LEFT JOIN users u ON p.reviewed_by = u.id WHERE p.student_id = ? ORDER BY p.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $projects[] = $row;
}
mysqli_stmt_close($stmt);

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
    <title>My Projects | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'My Projects';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="content-header">
                    <h2>Your Submitted Projects</h2>
                    <?php if ($isLeader): ?>
                        <a href="submitProject.php" class="btn-primary">
                            <i class="ri-add-line"></i> New Project
                        </a>
                    <?php else: ?>
                        <span
                            style="font-size:0.85rem;color:var(--text-muted);background:var(--bg-surface);padding:0.4rem 0.8rem;border-radius:8px;"><i
                                class="ri-eye-line"></i> View Only (Leader submits projects)</span>
                    <?php endif; ?>
                </div>

                <div class="projects-grid">
                    <?php if (empty($projects)): ?>
                        <div class="empty-state">
                            <i class="ri-folder-open-line"></i>
                            <h3>No Projects Yet</h3>
                            <?php if ($isLeader): ?>
                                <p>You haven't submitted any projects. Start by creating your first project!</p>
                                <a href="submitProject.php" class="btn-primary">Submit Your First Project</a>
                            <?php elseif ($myTeamId): ?>
                                <p>Your team hasn't submitted any projects yet. Only the team leader can submit projects.</p>
                            <?php else: ?>
                                <p>Join or create a team first, then submit your project.</p>
                                <a href="myTeam.php" class="btn-primary">Go to My Team</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                            <div class="dash-card" style="margin-bottom: 1rem;">
                                <div class="dash-card-header">
                                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                                    <span class="status-badge status-<?php echo $project['status']; ?>" style="padding:0.25rem 0.75rem;border-radius:20px;font-size:0.8rem;font-weight:600;
                                    <?php if ($project['status'] === 'approved')
                                        echo 'background:#dcfce7;color:#166534;';
                                    elseif ($project['status'] === 'rejected')
                                        echo 'background:#fef2f2;color:#991b1b;';
                                    else
                                        echo 'background:#fef3c7;color:#92400e;'; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </div>
                                <div class="dash-card-body">
                                    <p style="color: var(--text-muted); margin-bottom: 0.75rem;">
                                        <?php echo htmlspecialchars(substr($project['description'], 0, 150)); ?>...</p>
                                    <div
                                        style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:0.85rem;color:var(--text-muted);">
                                        <span><i class="ri-price-tag-3-line"></i>
                                            <?php echo htmlspecialchars(ucfirst($project['category'])); ?></span>
                                        <span><i class="ri-calendar-line"></i>
                                            <?php echo date('M d, Y', strtotime($project['created_at'])); ?></span>
                                        <?php if ($project['github_link']): ?>
                                            <a href="<?php echo htmlspecialchars($project['github_link']); ?>" target="_blank"
                                                style="color:var(--primary);"><i class="ri-github-line"></i> GitHub</a>
                                        <?php endif; ?>
                                        <?php if ($project['team_members']): ?>
                                            <span><i class="ri-team-line"></i>
                                                <?php echo htmlspecialchars($project['team_members']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($project['review_comments']): ?>
                                        <div
                                            style="margin-top:0.75rem;padding:0.75rem;background:var(--bg-surface);border-radius:8px;font-size:0.85rem;">
                                            <strong>Review: </strong><?php echo htmlspecialchars($project['review_comments']); ?>
                                            <?php if ($project['reviewer_name']): ?>
                                                <span style="color:var(--text-muted);"> —
                                                    <?php echo htmlspecialchars($project['reviewer_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isLeader && $project['status'] === 'pending'): ?>
                                        <form action="sparkBackend.php" method="POST" style="margin-top:0.75rem;"
                                            class="confirm-delete-form">
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                            <button type="submit" class="btn-secondary"
                                                style="color:#ef4444;border-color:#ef4444;font-size:0.85rem;">
                                                <i class="ri-delete-bin-line"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
        document.querySelectorAll('.confirm-delete-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const formEl = this;
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) formEl.submit();
                });
            });
        });
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>