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
    $isLeader = ((int)$myTeam['leader_id'] === (int)$userId);

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
    $projStmt = mysqli_prepare($conn, "SELECT p.* FROM projects p WHERE p.student_id IN (SELECT user_id FROM team_members WHERE team_id = ?) ORDER BY p.created_at DESC");
    mysqli_stmt_bind_param($projStmt, "i", $myTeam['id']);
    mysqli_stmt_execute($projStmt);
    $projResult = mysqli_stmt_get_result($projStmt);
    $teamProjects = [];
    while ($row = mysqli_fetch_assoc($projResult)) {
        $teamProjects[] = $row;
    }
    mysqli_stmt_close($projStmt);
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
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="ri-menu-line"></i>
                    </button>
                    <h1>My Team</h1>
                </div>
                <div class="header-right">
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
                <?php if (!$myTeam): ?>
                <!-- ===== NO TEAM - SHOW CREATE/JOIN OPTIONS ===== -->
                <div class="welcome-card" style="text-align:center;margin-bottom:2rem;">
                    <h2>Register for SPARK'26</h2>
                    <p style="margin-top:0.5rem;">To participate in SPARK'26, you need to be part of a team. Create a new team or join an existing one using a team code.</p>
                </div>

                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                    <!-- Create Team Card -->
                    <div class="dash-card" style="border:2px solid var(--border);">
                        <div class="dash-card-header">
                            <h3><i class="ri-add-circle-line" style="color:var(--primary);margin-right:0.5rem;"></i> Create a Team</h3>
                        </div>
                        <div class="dash-card-body">
                            <p style="color:var(--text-muted);margin-bottom:1.5rem;">Start a new team and become the team leader. You'll get a unique team code to share with your teammates.</p>
                            <button class="btn-primary" style="width:100%;" onclick="showCreateTeam()">
                                <i class="ri-team-line"></i> Create Team
                            </button>
                        </div>
                    </div>

                    <!-- Join Team Card -->
                    <div class="dash-card" style="border:2px solid var(--border);">
                        <div class="dash-card-header">
                            <h3><i class="ri-login-circle-line" style="color:#10b981;margin-right:0.5rem;"></i> Join a Team</h3>
                        </div>
                        <div class="dash-card-body">
                            <p style="color:var(--text-muted);margin-bottom:1.5rem;">Have a team code? Join an existing team created by your teammate. You'll be able to access the team's projects.</p>
                            <button class="btn-primary" style="width:100%;background:#10b981;" onclick="showJoinTeam()">
                                <i class="ri-key-line"></i> Join Team
                            </button>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- ===== HAS TEAM - SHOW TEAM DASHBOARD ===== -->
                
                <!-- Team Info Card -->
                <div class="welcome-card" style="margin-bottom:2rem;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
                        <div>
                            <h2><?php echo htmlspecialchars($myTeam['team_name']); ?> 
                                <?php if ($isLeader): ?>
                                <span style="background:#fbbf24;color:#92400e;padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;margin-left:0.5rem;">Leader</span>
                                <?php else: ?>
                                <span style="background:#dbeafe;color:#1e40af;padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;margin-left:0.5rem;">Member</span>
                                <?php endif; ?>
                            </h2>
                            <?php if ($myTeam['description']): ?>
                            <p style="margin-top:0.5rem;opacity:0.9;"><?php echo htmlspecialchars($myTeam['description']); ?></p>
                            <?php endif; ?>
                            <div style="display:flex;gap:1.5rem;margin-top:1rem;flex-wrap:wrap;font-size:0.9rem;opacity:0.9;">
                                <span><i class="ri-building-line"></i> <?php echo htmlspecialchars($myTeam['department'] ?: 'N/A'); ?></span>
                                <span><i class="ri-team-line"></i> <?php echo count($teamMembers); ?>/<?php echo $myTeam['max_members']; ?> Members</span>
                                <span><i class="ri-calendar-line"></i> Created <?php echo date('M d, Y', strtotime($myTeam['created_at'])); ?></span>
                                <span style="background:<?php echo $myTeam['status']==='open'?'#dcfce7':'#fef2f2'; ?>;color:<?php echo $myTeam['status']==='open'?'#166534':'#991b1b'; ?>;padding:0.15rem 0.6rem;border-radius:12px;font-weight:600;">
                                    <?php echo ucfirst($myTeam['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($isLeader): ?>
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <button class="btn-primary" style="font-size:0.85rem;" onclick="showTeamCode('<?php echo $myTeam['team_code']; ?>')">
                                <i class="ri-share-line"></i> Share Code
                            </button>
                            <button class="btn-secondary" style="font-size:0.85rem;color:#ef4444;border-color:#ef4444;" onclick="confirmDeleteTeam(<?php echo $myTeam['id']; ?>)">
                                <i class="ri-delete-bin-line"></i> Delete Team
                            </button>
                        </div>
                        <?php else: ?>
                        <button class="btn-secondary" style="font-size:0.85rem;color:#ef4444;border-color:#ef4444;" onclick="confirmLeaveTeam(<?php echo $myTeam['id']; ?>)">
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
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid var(--border);">
                                <div style="display:flex;align-items:center;gap:0.75rem;">
                                    <div style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;">
                                        <?php echo strtoupper(substr($member['name'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <strong style="font-size:0.95rem;"><?php echo htmlspecialchars($member['name']); ?></strong>
                                        <?php if ($member['role'] === 'leader'): ?>
                                        <span style="background:#fbbf24;color:#92400e;padding:0.1rem 0.4rem;border-radius:8px;font-size:0.65rem;font-weight:700;margin-left:0.3rem;">LEADER</span>
                                        <?php endif; ?>
                                        <p style="font-size:0.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($member['email']); ?> &bull; <?php echo htmlspecialchars($member['department'] ?? ''); ?></p>
                                    </div>
                                </div>
                                <?php if ($isLeader && $member['id'] !== $userId && $member['role'] !== 'leader'): ?>
                                <button class="btn-icon" style="color:#ef4444;" title="Remove member" onclick="confirmRemoveMember(<?php echo $member['id']; ?>, '<?php echo addslashes($member['name']); ?>', <?php echo $myTeam['id']; ?>)">
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
                            <a href="submitProject.php" style="color:var(--primary);font-size:0.9rem;"><i class="ri-add-line"></i> New</a>
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
                                        <strong style="font-size:0.95rem;"><?php echo htmlspecialchars($proj['title']); ?></strong>
                                        <span style="padding:0.15rem 0.5rem;border-radius:12px;font-size:0.7rem;font-weight:600;
                                            <?php if($proj['status']==='approved') echo 'background:#dcfce7;color:#166534;';
                                            elseif($proj['status']==='rejected') echo 'background:#fef2f2;color:#991b1b;';
                                            else echo 'background:#fef3c7;color:#92400e;'; ?>">
                                            <?php echo ucfirst($proj['status']); ?>
                                        </span>
                                    </div>
                                    <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;"><?php echo htmlspecialchars(ucfirst($proj['category'] ?? '')); ?> &bull; <?php echo date('M d, Y', strtotime($proj['created_at'])); ?></p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>

</html>
