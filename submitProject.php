<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Student';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Student');
$userId = $_SESSION['user_id'];

// Check if student has a team
$myTeam = null;
$teamCheck = mysqli_prepare($conn, "SELECT t.* FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ?");
mysqli_stmt_bind_param($teamCheck, "i", $userId);
mysqli_stmt_execute($teamCheck);
$myTeam = mysqli_fetch_assoc(mysqli_stmt_get_result($teamCheck));
mysqli_stmt_close($teamCheck);

// Get team members for display
$teamMemberNames = '';
if ($myTeam) {
    $memberStmt = mysqli_prepare($conn, "SELECT u.name FROM team_members tm JOIN users u ON tm.user_id = u.id WHERE tm.team_id = ? AND tm.user_id != ?");
    mysqli_stmt_bind_param($memberStmt, "ii", $myTeam['id'], $userId);
    mysqli_stmt_execute($memberStmt);
    $memberRes = mysqli_stmt_get_result($memberStmt);
    $names = [];
    while ($row = mysqli_fetch_assoc($memberRes)) { $names[] = $row['name']; }
    $teamMemberNames = implode(', ', $names);
    mysqli_stmt_close($memberStmt);
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
    <title>Submit Project | SPARK'26</title>
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
                    <h1>Submit Project</h1>
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
                <div style="background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);color:#92400e;padding:1.25rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <i class="ri-error-warning-line" style="font-size:1.5rem;"></i>
                        <div>
                            <strong>Team Required!</strong>
                            <p style="font-size:0.85rem;opacity:0.9;">You need to be part of a team to submit projects.</p>
                        </div>
                    </div>
                    <a href="myTeam.php" style="background:#92400e;color:#fff;padding:0.5rem 1.25rem;border-radius:8px;font-weight:600;text-decoration:none;font-size:0.9rem;">Join/Create Team</a>
                </div>
                <?php endif; ?>

                <div class="form-container">
                    <div class="form-card">
                        <h2>Project Submission Form</h2>
                        <p class="form-description">Fill in the details below to submit your project for SPARK'26</p>
                        
                        <form action="sparkBackend.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="submit_project">
                            
                            <div class="form-group">
                                <label for="projectTitle">Project Title *</label>
                                <input type="text" id="projectTitle" name="projectTitle" required placeholder="Enter your project title">
                            </div>

                            <div class="form-group">
                                <label for="projectCategory">Category *</label>
                                <select id="projectCategory" name="projectCategory" required>
                                    <option value="">Select a category</option>
                                    <option value="web">Web Development</option>
                                    <option value="mobile">Mobile Application</option>
                                    <option value="ai">AI/Machine Learning</option>
                                    <option value="iot">IoT</option>
                                    <option value="blockchain">Blockchain</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="projectDescription">Description *</label>
                                <textarea id="projectDescription" name="projectDescription" rows="5" required placeholder="Describe your project in detail"></textarea>
                            </div>

                            <?php if ($myTeam): ?>
                            <div class="form-group">
                                <label>Team</label>
                                <input type="text" value="<?php echo htmlspecialchars($myTeam['team_name']); ?>" disabled style="background:var(--bg-surface);">
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="teamMembers">Team Members</label>
                                <input type="text" id="teamMembers" name="teamMembers" placeholder="Enter team member names (comma separated)" value="<?php echo htmlspecialchars($teamMemberNames); ?>">
                            </div>

                            <div class="form-group">
                                <label for="projectFile">Project Documentation (PDF)</label>
                                <input type="file" id="projectFile" name="projectFile" accept=".pdf">
                            </div>

                            <div class="form-group">
                                <label for="githubLink">GitHub Repository</label>
                                <input type="url" id="githubLink" name="githubLink" placeholder="https://github.com/username/repo">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary" <?php echo !$myTeam ? 'disabled title="Join a team first"' : ''; ?>>Submit Project</button>
                                <a href="myProjects.php" class="btn-secondary">Cancel</a>
                            </div>
                        </form>
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

    // SweetAlert form submission confirmation
    document.querySelector('form[action="sparkBackend.php"]')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Submit Project?',
            text: 'Are you sure you want to submit this project for review?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, submit it!'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
    </script>
</body>

</html>
