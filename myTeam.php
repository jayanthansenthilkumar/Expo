<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Student';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Student');
$userId = $_SESSION['user_id'];
$userDept = $_SESSION['department'] ?? '';

// Check if student is already in a team
$myTeam = null;
$teamMembers = [];
$isLeader = false;

$teamCheck = mysqli_prepare($conn, "SELECT t.*, tm.role as my_role, u.name as leader_name 
    FROM team_members tm 
    JOIN teams t ON tm.team_id = t.id 
    LEFT JOIN users u ON t.leader_id = u.id 
    WHERE tm.user_id = ?");
mysqli_stmt_bind_param($teamCheck, "i", $userId);
mysqli_stmt_execute($teamCheck);
$teamResult = mysqli_stmt_get_result($teamCheck);
$myTeam = mysqli_fetch_assoc($teamResult);
mysqli_stmt_close($teamCheck);

if ($myTeam) {
    $isLeader = ((int) $myTeam['leader_id'] === (int) $userId);

    // Fetch all team members
    $memberStmt = mysqli_prepare($conn, "SELECT u.id, u.name, u.email, u.department, u.reg_no, tm.role, tm.joined_at 
        FROM team_members tm 
        JOIN users u ON tm.user_id = u.id 
        WHERE tm.team_id = ? 
        ORDER BY tm.role = 'leader' DESC, tm.joined_at ASC");
    mysqli_stmt_bind_param($memberStmt, "i", $myTeam['id']);
    mysqli_stmt_execute($memberStmt);
    $memberResult = mysqli_stmt_get_result($memberStmt);
    while ($row = mysqli_fetch_assoc($memberResult)) {
        $teamMembers[] = $row;
    }
    mysqli_stmt_close($memberStmt);

    // Fetch team's projects
    $projStmt = mysqli_prepare($conn, "SELECT p.* FROM projects p WHERE p.team_id = ? ORDER BY p.created_at DESC");
    mysqli_stmt_bind_param($projStmt, "i", $myTeam['id']);
    mysqli_stmt_execute($projStmt);
    $projResult = mysqli_stmt_get_result($projStmt);
    $teamProjects = [];
    while ($row = mysqli_fetch_assoc($projResult)) {
        $teamProjects[] = $row;
    }
    mysqli_stmt_close($projStmt);

    // Fetch pending sent invitations (for leader view)
    $sentInvitations = [];
    if ($isLeader) {
        $sentStmt = mysqli_prepare($conn, "SELECT ti.*, u.name as invited_name, u.email as invited_email, u.department as invited_dept FROM team_invitations ti JOIN users u ON ti.invited_user_id = u.id WHERE ti.team_id = ? AND ti.status = 'pending' ORDER BY ti.created_at DESC");
        mysqli_stmt_bind_param($sentStmt, "i", $myTeam['id']);
        mysqli_stmt_execute($sentStmt);
        $sentResult = mysqli_stmt_get_result($sentStmt);
        while ($row = mysqli_fetch_assoc($sentResult)) {
            $sentInvitations[] = $row;
        }
        mysqli_stmt_close($sentStmt);

        // Fetch students not in any team (for invite dropdown)
        $invitableStmt = mysqli_prepare($conn, "SELECT u.id, u.name, u.email, u.department, u.reg_no FROM users u WHERE u.role = 'student' AND u.id != ? AND u.id NOT IN (SELECT user_id FROM team_members) ORDER BY u.name ASC");
        mysqli_stmt_bind_param($invitableStmt, "i", $userId);
        mysqli_stmt_execute($invitableStmt);
        $invitableStudents = mysqli_fetch_all(mysqli_stmt_get_result($invitableStmt), MYSQLI_ASSOC);
        mysqli_stmt_close($invitableStmt);
    }
}

// Fetch pending invitations FOR the current user (when they have no team)
$pendingInvitations = [];
if (!$myTeam) {
    $invStmt = mysqli_prepare($conn, "SELECT ti.*, t.team_name, t.department as team_dept, u.name as leader_name FROM team_invitations ti JOIN teams t ON ti.team_id = t.id JOIN users u ON ti.invited_by = u.id WHERE ti.invited_user_id = ? AND ti.status = 'pending' ORDER BY ti.created_at DESC");
    mysqli_stmt_bind_param($invStmt, "i", $userId);
    mysqli_stmt_execute($invStmt);
    $invResult = mysqli_stmt_get_result($invStmt);
    while ($row = mysqli_fetch_assoc($invResult)) {
        $pendingInvitations[] = $row;
    }
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
    <title>My Team | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'My Team';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">
                <?php if (!$myTeam): ?>
                    <!-- ===== NO TEAM - SHOW CREATE/JOIN OPTIONS ===== -->
                    <div class="welcome-card" style="text-align:center;margin-bottom:2rem;">
                        <h2>Register for SPARK'26</h2>
                        <p style="margin-top:0.5rem;">To participate in SPARK'26, you need to be part of a team. Create a
                            new team or join an existing one using a team code.</p>
                    </div>

                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <!-- Create Team Card -->
                        <div class="dash-card" style="border:2px solid var(--border);">
                            <div class="dash-card-header">
                                <h3><i class="ri-add-circle-line" style="color:var(--primary);margin-right:0.5rem;"></i>
                                    Create a Team</h3>
                            </div>
                            <div class="dash-card-body">
                                <p style="color:var(--text-muted);margin-bottom:1.5rem;">Start a new team and become the
                                    team leader. You'll get a unique team code to share with your teammates.</p>
                                <button class="btn-primary" style="width:100%;" onclick="showCreateTeam()">
                                    <i class="ri-team-line"></i> Create Team
                                </button>
                            </div>
                        </div>

                        <!-- Join Team Card -->
                        <div class="dash-card" style="border:2px solid var(--border);">
                            <div class="dash-card-header">
                                <h3><i class="ri-login-circle-line" style="color:#10b981;margin-right:0.5rem;"></i> Join a
                                    Team</h3>
                            </div>
                            <div class="dash-card-body">
                                <p style="color:var(--text-muted);margin-bottom:1.5rem;">Have a team code? Join an existing
                                    team created by your teammate. You'll be able to access the team's projects.</p>
                                <button class="btn-primary" style="width:100%;background:#10b981;" onclick="showJoinTeam()">
                                    <i class="ri-key-line"></i> Join Team
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($pendingInvitations)): ?>
                        <!-- ===== PENDING INVITATIONS ===== -->
                        <div class="dash-card" style="margin-top:2rem;border:2px solid #3b82f6;">
                            <div class="dash-card-header">
                                <h3><i class="ri-mail-send-line" style="color:#3b82f6;margin-right:0.5rem;"></i> Team
                                    Invitations <span
                                        style="background:#3b82f6;color:#fff;padding:0.15rem 0.5rem;border-radius:12px;font-size:0.75rem;margin-left:0.5rem;"><?php echo count($pendingInvitations); ?></span>
                                </h3>
                            </div>
                            <div class="dash-card-body">
                                <p style="color:var(--text-muted);margin-bottom:1rem;">You have been invited to join the
                                    following teams:</p>
                                <?php foreach ($pendingInvitations as $inv): ?>
                                    <div
                                        style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem;border:1px solid var(--border);border-radius:10px;margin-bottom:0.75rem;background:var(--bg-surface);">
                                        <div>
                                            <strong
                                                style="font-size:0.95rem;"><?php echo htmlspecialchars($inv['team_name']); ?></strong>
                                            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.2rem;">
                                                Invited by <?php echo htmlspecialchars($inv['leader_name']); ?>
                                                <?php if ($inv['team_dept']): ?> &bull;
                                                    <?php echo htmlspecialchars($inv['team_dept']); ?>            <?php endif; ?>
                                                &bull; <?php echo date('M d, Y', strtotime($inv['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div style="display:flex;gap:0.5rem;">
                                            <form action="sparkBackend.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="accept_invite">
                                                <input type="hidden" name="invite_id" value="<?php echo $inv['id']; ?>">
                                                <button type="submit" class="btn-primary"
                                                    style="font-size:0.8rem;padding:0.4rem 0.8rem;">
                                                    <i class="ri-check-line"></i> Accept
                                                </button>
                                            </form>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="decline_invite">
                                                <input type="hidden" name="invite_id" value="<?php echo $inv['id']; ?>">
                                                <button type="submit" class="btn-secondary"
                                                    style="font-size:0.8rem;padding:0.4rem 0.8rem;color:#ef4444;border-color:#ef4444;">
                                                    <i class="ri-close-line"></i> Decline
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>

                    <!-- Team Info Card -->
                    <div class="welcome-card" style="margin-bottom:2rem;">
                        <div
                            style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
                            <div>
                                <h2><?php echo htmlspecialchars($myTeam['team_name']); ?>
                                    <?php if ($isLeader): ?>
                                        <span
                                            style="background:#fbbf24;color:#92400e;padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;margin-left:0.5rem;">Leader</span>
                                    <?php else: ?>
                                        <span
                                            style="background:#dbeafe;color:#1e40af;padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;margin-left:0.5rem;">Member</span>
                                    <?php endif; ?>
                                </h2>
                                <?php if ($myTeam['description']): ?>
                                    <p style="margin-top:0.5rem;opacity:0.9;">
                                        <?php echo htmlspecialchars($myTeam['description']); ?></p>
                                <?php endif; ?>
                                <div
                                    style="display:flex;gap:1.5rem;margin-top:1rem;flex-wrap:wrap;font-size:0.9rem;opacity:0.9;">
                                    <span><i class="ri-building-line"></i>
                                        <?php echo htmlspecialchars($myTeam['department'] ?: 'N/A'); ?></span>
                                    <span><i class="ri-team-line"></i>
                                        <?php echo count($teamMembers); ?>/<?php echo $myTeam['max_members']; ?>
                                        Members</span>
                                    <span><i class="ri-calendar-line"></i> Created
                                        <?php echo date('M d, Y', strtotime($myTeam['created_at'])); ?></span>
                                    <span
                                        style="background:<?php echo $myTeam['status'] === 'open' ? '#dcfce7' : '#fef2f2'; ?>;color:<?php echo $myTeam['status'] === 'open' ? '#166534' : '#991b1b'; ?>;padding:0.15rem 0.6rem;border-radius:12px;font-weight:600;">
                                        <?php echo ucfirst($myTeam['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($isLeader): ?>
                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                    <button class="btn-primary" style="font-size:0.85rem;" onclick="showInviteMember()">
                                        <i class="ri-user-add-line"></i> Invite Member
                                    </button>
                                    <button class="btn-primary" style="font-size:0.85rem;background:#10b981;"
                                        onclick="showTeamCode('<?php echo $myTeam['team_code']; ?>')">
                                        <i class="ri-share-line"></i> Share Code
                                    </button>
                                    <button class="btn-secondary" style="font-size:0.85rem;color:#ef4444;border-color:#ef4444;"
                                        onclick="confirmDeleteTeam(<?php echo $myTeam['id']; ?>)">
                                        <i class="ri-delete-bin-line"></i> Delete Team
                                    </button>
                                </div>
                            <?php else: ?>
                                <button class="btn-secondary" style="font-size:0.85rem;color:#ef4444;border-color:#ef4444;"
                                    onclick="confirmLeaveTeam(<?php echo $myTeam['id']; ?>)">
                                    <i class="ri-logout-box-line"></i> Leave Team
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="welcome-decoration">
                            <i class="ri-team-line"></i>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="stats-grid" style="margin-bottom:2rem;">
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class="ri-team-line"></i></div>
                            <div class="stat-info">
                                <h3><?php echo count($teamMembers); ?></h3>
                                <p>Team Members</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="ri-folder-line"></i></div>
                            <div class="stat-info">
                                <h3><?php echo count($teamProjects ?? []); ?></h3>
                                <p>Team Projects</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon amber"><i class="ri-key-2-line"></i></div>
                            <div class="stat-info">
                                <h3><?php echo htmlspecialchars($myTeam['team_code']); ?></h3>
                                <p>Team Code</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon purple"><i class="ri-user-star-line"></i></div>
                            <div class="stat-info">
                                <h3><?php echo htmlspecialchars($myTeam['leader_name']); ?></h3>
                                <p>Team Leader</p>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <!-- Team Members -->
                        <div class="dash-card">
                            <div class="dash-card-header">
                                <h3>Team Members</h3>
                            </div>
                            <div class="dash-card-body">
                                <?php foreach ($teamMembers as $member): ?>
                                    <div
                                        style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid var(--border);">
                                        <div style="display:flex;align-items:center;gap:0.75rem;">
                                            <div
                                                style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;">
                                                <?php echo strtoupper(substr($member['name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <strong
                                                    style="font-size:0.95rem;"><?php echo htmlspecialchars($member['name']); ?></strong>
                                                <?php if ($member['role'] === 'leader'): ?>
                                                    <span
                                                        style="background:#fbbf24;color:#92400e;padding:0.1rem 0.4rem;border-radius:8px;font-size:0.65rem;font-weight:700;margin-left:0.3rem;">LEADER</span>
                                                <?php endif; ?>
                                                <p style="font-size:0.8rem;color:var(--text-muted);">
                                                    <?php echo htmlspecialchars($member['email']); ?> &bull;
                                                    <?php echo htmlspecialchars($member['department'] ?? ''); ?></p>
                                            </div>
                                        </div>
                                        <?php if ($isLeader && $member['id'] !== $userId && $member['role'] !== 'leader'): ?>
                                            <button class="btn-icon" style="color:#ef4444;" title="Remove member"
                                                onclick="confirmRemoveMember(<?php echo $member['id']; ?>, '<?php echo addslashes($member['name']); ?>', <?php echo $myTeam['id']; ?>)">
                                                <i class="ri-user-unfollow-line"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Team Projects -->
                        <div class="dash-card">
                            <div class="dash-card-header">
                                <h3>Team Projects</h3>
                                <?php if ($isLeader): ?>
                                    <a href="submitProject.php" style="color:var(--primary);font-size:0.9rem;"><i
                                            class="ri-add-line"></i> New</a>
                                <?php else: ?>
                                    <span style="font-size:0.8rem;color:var(--text-muted);"><i class="ri-eye-line"></i> View
                                        Only</span>
                                <?php endif; ?>
                            </div>
                            <div class="dash-card-body">
                                <?php if (empty($teamProjects)): ?>
                                    <div style="text-align:center;padding:2rem 0;color:var(--text-muted);">
                                        <i class="ri-folder-open-line" style="font-size:2rem;"></i>
                                        <p style="margin-top:0.5rem;">No projects yet. Submit your first project!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($teamProjects as $proj): ?>
                                        <div style="padding:0.75rem 0;border-bottom:1px solid var(--border);">
                                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                                <strong
                                                    style="font-size:0.95rem;"><?php echo htmlspecialchars($proj['title']); ?></strong>
                                                <span style="padding:0.15rem 0.5rem;border-radius:12px;font-size:0.7rem;font-weight:600;
                                            <?php if ($proj['status'] === 'approved')
                                                echo 'background:#dcfce7;color:#166534;';
                                            elseif ($proj['status'] === 'rejected')
                                                echo 'background:#fef2f2;color:#991b1b;';
                                            else
                                                echo 'background:#fef3c7;color:#92400e;'; ?>">
                                                    <?php echo ucfirst($proj['status']); ?>
                                                </span>
                                            </div>
                                            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">
                                                <?php echo htmlspecialchars(ucfirst($proj['category'] ?? '')); ?> &bull;
                                                <?php echo date('M d, Y', strtotime($proj['created_at'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($isLeader && !empty($sentInvitations)): ?>
                        <!-- Sent Invitations -->
                        <div class="dash-card" style="margin-top:1.5rem;">
                            <div class="dash-card-header">
                                <h3><i class="ri-send-plane-line" style="margin-right:0.3rem;"></i> Pending Invitations Sent
                                </h3>
                            </div>
                            <div class="dash-card-body">
                                <?php foreach ($sentInvitations as $inv): ?>
                                    <div
                                        style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid var(--border);">
                                        <div>
                                            <strong
                                                style="font-size:0.9rem;"><?php echo htmlspecialchars($inv['invited_name']); ?></strong>
                                            <p style="font-size:0.8rem;color:var(--text-muted);">
                                                <?php echo htmlspecialchars($inv['invited_email']); ?> &bull;
                                                <?php echo htmlspecialchars($inv['invited_dept'] ?? ''); ?> &bull; Sent
                                                <?php echo date('M d', strtotime($inv['created_at'])); ?></p>
                                        </div>
                                        <button class="btn-secondary"
                                            style="font-size:0.75rem;padding:0.3rem 0.6rem;color:#ef4444;border-color:#ef4444;"
                                            onclick="confirmCancelInvite(<?php echo $inv['id']; ?>, '<?php echo addslashes($inv['invited_name']); ?>')">
                                            <i class="ri-close-line"></i> Cancel
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Flash messages via SweetAlert
        <?php if ($successMsg): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo addslashes($successMsg); ?>',
                confirmButtonColor: '#2563eb',
                timer: 3000,
                timerProgressBar: true
            });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: '<?php echo addslashes($errorMsg); ?>',
                confirmButtonColor: '#2563eb'
            });
        <?php endif; ?>

        function showCreateTeam() {
            Swal.fire({
                title: 'Create a Team',
                html: `
                <div style="text-align:left;">
                    <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:0.3rem;">Team Name *</label>
                    <input id="swal-teamName" class="swal2-input" placeholder="e.g. Team Innovators" style="margin:0 0 1rem 0;width:100%;box-sizing:border-box;">
                    <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:0.3rem;">Description</label>
                    <textarea id="swal-teamDesc" class="swal2-textarea" placeholder="Brief team description..." style="margin:0;width:100%;box-sizing:border-box;"></textarea>
                </div>
            `,
                confirmButtonText: '<i class="ri-team-line"></i> Create Team',
                confirmButtonColor: '#2563eb',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                focusConfirm: false,
                preConfirm: () => {
                    const name = document.getElementById('swal-teamName').value.trim();
                    if (!name) {
                        Swal.showValidationMessage('Team name is required');
                        return false;
                    }
                    return { name: name, description: document.getElementById('swal-teamDesc').value.trim() };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="create_team">
                    <input type="hidden" name="teamName" value="${escapeHtml(result.value.name)}">
                    <input type="hidden" name="description" value="${escapeHtml(result.value.description)}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function showJoinTeam() {
            Swal.fire({
                title: 'Join a Team',
                html: `
                <p style="color:#6b7280;font-size:0.9rem;margin-bottom:1rem;">Enter the 6-character team code shared by your team leader</p>
                <input id="swal-teamCode" class="swal2-input" placeholder="e.g. A3F8B2" maxlength="6" style="text-transform:uppercase;text-align:center;font-size:1.5rem;letter-spacing:0.3rem;font-weight:700;">
            `,
                confirmButtonText: '<i class="ri-login-circle-line"></i> Join Team',
                confirmButtonColor: '#10b981',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                focusConfirm: false,
                preConfirm: () => {
                    const code = document.getElementById('swal-teamCode').value.trim();
                    if (!code || code.length < 4) {
                        Swal.showValidationMessage('Please enter a valid team code');
                        return false;
                    }
                    return code;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="join_team">
                    <input type="hidden" name="teamCode" value="${escapeHtml(result.value)}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function showTeamCode(code) {
            Swal.fire({
                title: 'Share Team Code',
                html: `
                <p style="color:#6b7280;margin-bottom:1rem;">Share this code with your teammates so they can join your team</p>
                <div style="background:#f1f5f9;padding:1.5rem;border-radius:12px;margin:1rem 0;">
                    <span style="font-size:2rem;font-weight:800;letter-spacing:0.5rem;color:#1e293b;">${code}</span>
                </div>
            `,
                confirmButtonText: '<i class="ri-file-copy-line"></i> Copy Code',
                confirmButtonColor: '#2563eb',
                showCancelButton: true,
                cancelButtonText: 'Close',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    navigator.clipboard.writeText(code).then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Copied!',
                            text: 'Team code copied to clipboard',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    });
                }
            });
        }

        function confirmDeleteTeam(teamId) {
            Swal.fire({
                title: 'Delete Team?',
                text: 'This will remove all team members and cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="delete_team">
                    <input type="hidden" name="team_id" value="${teamId}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function confirmLeaveTeam(teamId) {
            Swal.fire({
                title: 'Leave Team?',
                text: 'You will no longer have access to this team\'s projects.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, leave'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="leave_team">
                    <input type="hidden" name="team_id" value="${teamId}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function confirmRemoveMember(memberId, memberName, teamId) {
            Swal.fire({
                title: 'Remove Member?',
                text: `Are you sure you want to remove ${memberName} from the team?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Remove'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="remove_member">
                    <input type="hidden" name="member_id" value="${memberId}">
                    <input type="hidden" name="team_id" value="${teamId}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        <?php if ($myTeam && $isLeader): ?>
            const invitableStudents = <?php echo json_encode($invitableStudents ?? []); ?>;

            function showInviteMember() {
                if (invitableStudents.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'No Students Available',
                        text: 'All students are already in teams or no students are registered yet.',
                        confirmButtonColor: '#2563eb'
                    });
                    return;
                }

                let studentOptions = '<option value="">-- Select a student --</option>';
                invitableStudents.forEach(s => {
                    studentOptions += `<option value="${s.id}">${escapeHtml(s.name)} (${escapeHtml(s.department || '')}) - ${escapeHtml(s.reg_no || s.email)}</option>`;
                });

                Swal.fire({
                    title: 'Invite Team Member',
                    html: `
                <div style="text-align:left;">
                    <p style="color:#6b7280;font-size:0.85rem;margin-bottom:1rem;">Send an invitation to a student to join your team. They will see the invitation on their Team page.</p>
                    <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:0.3rem;">Select Student *</label>
                    <select id="swal-inviteUser" class="swal2-select" style="margin:0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">${studentOptions}</select>
                    <div style="margin-top:0.75rem;">
                        <input id="swal-searchStudent" type="text" class="swal2-input" placeholder="Type to search students..." style="margin:0;width:100%;box-sizing:border-box;" oninput="filterInviteStudents(this.value)">
                    </div>
                </div>
            `,
                    confirmButtonText: '<i class="ri-send-plane-line"></i> Send Invitation',
                    confirmButtonColor: '#2563eb',
                    showCancelButton: true,
                    cancelButtonColor: '#6b7280',
                    focusConfirm: false,
                    didOpen: () => {
                        // Focus search field
                        document.getElementById('swal-searchStudent')?.focus();
                    },
                    preConfirm: () => {
                        const userId = document.getElementById('swal-inviteUser').value;
                        if (!userId) {
                            Swal.showValidationMessage('Please select a student to invite');
                            return false;
                        }
                        return userId;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'sparkBackend.php';
                        form.innerHTML = `
                    <input type="hidden" name="action" value="send_invite">
                    <input type="hidden" name="invited_user_id" value="${result.value}">
                `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }

            function filterInviteStudents(query) {
                const select = document.getElementById('swal-inviteUser');
                const q = query.toLowerCase();
                select.innerHTML = '<option value="">-- Select a student --</option>';
                invitableStudents.forEach(s => {
                    const text = `${s.name} ${s.department || ''} ${s.reg_no || ''} ${s.email}`.toLowerCase();
                    if (!q || text.includes(q)) {
                        select.innerHTML += `<option value="${s.id}">${escapeHtml(s.name)} (${escapeHtml(s.department || '')}) - ${escapeHtml(s.reg_no || s.email)}</option>`;
                    }
                });
            }

            function confirmCancelInvite(inviteId, studentName) {
                Swal.fire({
                    title: 'Cancel Invitation?',
                    text: `Cancel the pending invitation to ${studentName}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, cancel it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'sparkBackend.php';
                        form.innerHTML = `
                    <input type="hidden" name="action" value="cancel_invite">
                    <input type="hidden" name="invite_id" value="${inviteId}">
                `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        <?php endif; ?>

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // SweetAlert confirmations for accept/decline invite forms
        document.querySelectorAll('form input[name="action"][value="accept_invite"]').forEach(input => {
            input.closest('form').addEventListener('submit', function (e) {
                e.preventDefault();
                const form = this;
                Swal.fire({
                    title: 'Accept Invitation?',
                    text: 'You will join this team and any other pending invitations will be automatically declined.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, join team!'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        document.querySelectorAll('form input[name="action"][value="decline_invite"]').forEach(input => {
            input.closest('form').addEventListener('submit', function (e) {
                e.preventDefault();
                const form = this;
                Swal.fire({
                    title: 'Decline Invitation?',
                    text: 'You can always ask the team leader to invite you again later.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, decline'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    </script>
</body>

</html>