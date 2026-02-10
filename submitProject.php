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
$isLeader = false;
$teamCheck = mysqli_prepare($conn, "SELECT t.* FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ?");
mysqli_stmt_bind_param($teamCheck, "i", $userId);
mysqli_stmt_execute($teamCheck);
$myTeam = mysqli_fetch_assoc(mysqli_stmt_get_result($teamCheck));
mysqli_stmt_close($teamCheck);

if ($myTeam) {
    $isLeader = ((int) $myTeam['leader_id'] === (int) $userId);
}

// Get team members for display
$teamMemberNames = '';
if ($myTeam) {
    $memberStmt = mysqli_prepare($conn, "SELECT u.name FROM team_members tm JOIN users u ON tm.user_id = u.id WHERE tm.team_id = ? AND tm.user_id != ?");
    mysqli_stmt_bind_param($memberStmt, "ii", $myTeam['id'], $userId);
    mysqli_stmt_execute($memberStmt);
    $memberRes = mysqli_stmt_get_result($memberStmt);
    $names = [];
    while ($row = mysqli_fetch_assoc($memberRes)) {
        $names[] = $row['name'];
    }
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
            <?php
            $pageTitle = 'Submit Project';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">
                <?php if (!$myTeam): ?>
                    <div style="text-align:center;padding:3rem 1rem;">
                        <i class="ri-team-line"
                            style="font-size:4rem;color:var(--text-muted);margin-bottom:1rem;display:block;"></i>
                        <h2 style="margin-bottom:0.5rem;">Team Required</h2>
                        <p style="color:var(--text-muted);max-width:400px;margin:0 auto 1.5rem;">You must be part of a team
                            to submit projects for SPARK'26. Create a new team or join an existing one.</p>
                        <a href="myTeam.php" class="btn-primary"><i class="ri-team-line"></i> Go to My Team</a>
                    </div>
                <?php elseif (!$isLeader): ?>
                    <div style="text-align:center;padding:3rem 1rem;">
                        <i class="ri-lock-line"
                            style="font-size:4rem;color:var(--text-muted);margin-bottom:1rem;display:block;"></i>
                        <h2 style="margin-bottom:0.5rem;">Leader Access Only</h2>
                        <p style="color:var(--text-muted);max-width:450px;margin:0 auto 1.5rem;">Only the team leader can
                            submit projects. You can view your team's projects from the My Projects page.</p>
                        <a href="myProjects.php" class="btn-primary"><i class="ri-folder-line"></i> View Projects</a>
                    </div>
                <?php else: ?>

                    <div class="form-container">
                        <div class="form-card">
                            <h2>Project Submission Form</h2>
                            <p class="form-description">Fill in the details below to submit your project for SPARK'26</p>

                            <form action="sparkBackend.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="submit_project">

                                <div class="form-group">
                                    <label for="projectTitle">Project Title *</label>
                                    <input type="text" id="projectTitle" name="projectTitle" required
                                        placeholder="Enter your project title">
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
                                    <textarea id="projectDescription" name="projectDescription" rows="5" required
                                        placeholder="Describe your project in detail"></textarea>
                                </div>

                                <?php if ($myTeam): ?>
                                    <div class="form-group">
                                        <label>Team</label>
                                        <input type="text" value="<?php echo htmlspecialchars($myTeam['team_name']); ?>"
                                            disabled style="background:var(--bg-surface);">
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="teamMembers">Team Members</label>
                                    <input type="text" id="teamMembers" name="teamMembers"
                                        placeholder="Enter team member names (comma separated)"
                                        value="<?php echo htmlspecialchars($teamMemberNames); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="projectFile">Project Documentation (PDF)</label>
                                    <input type="file" id="projectFile" name="projectFile" accept=".pdf">
                                </div>

                                <div class="form-group">
                                    <label for="githubLink">GitHub Repository</label>
                                    <input type="url" id="githubLink" name="githubLink"
                                        placeholder="https://github.com/username/repo">
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">Submit Project</button>
                                    <a href="myProjects.php" class="btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
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

        // SweetAlert form submission confirmation
        document.querySelector('form[action="sparkBackend.php"]')?.addEventListener('submit', function (e) {
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