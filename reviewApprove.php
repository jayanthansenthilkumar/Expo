<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDepartment = $_SESSION['department'] ?? '';
$role = $_SESSION['role'] ?? '';

// Determine if user sees all projects or only their department
$filterByDept = ($role === 'coordinator' || $role === 'department_coordinator');

// Count pending projects
if ($filterByDept) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = 'pending' AND department = ?");
    $stmt->execute([$userDepartment]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'pending'");
}
$pendingCount = $stmt->fetchColumn();

// Count approved projects
if ($filterByDept) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = 'approved' AND department = ?");
    $stmt->execute([$userDepartment]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'approved'");
}
$approvedCount = $stmt->fetchColumn();

// Count rejected projects
if ($filterByDept) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE status = 'rejected' AND department = ?");
    $stmt->execute([$userDepartment]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'rejected'");
}
$rejectedCount = $stmt->fetchColumn();

$underReviewCount = 0;

// Fetch pending projects with student name
if ($filterByDept) {
    $stmt = $pdo->prepare("SELECT p.*, u.name AS student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'pending' AND p.department = ? ORDER BY p.created_at DESC");
    $stmt->execute([$userDepartment]);
} else {
    $stmt = $pdo->query("SELECT p.*, u.name AS student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'pending' ORDER BY p.created_at DESC");
}
$pendingProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Flash messages
$flashSuccess = $_SESSION['success'] ?? '';
$flashError = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review & Approve | SPARK'26</title>
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
                    <h1>Review & Approve</h1>
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
                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success">
                        <i class="ri-checkbox-circle-line"></i>
                        <?php echo htmlspecialchars($flashSuccess); ?>
                    </div>
                <?php endif; ?>
                <?php if ($flashError): ?>
                    <div class="alert alert-danger">
                        <i class="ri-error-warning-line"></i>
                        <?php echo htmlspecialchars($flashError); ?>
                    </div>
                <?php endif; ?>

                <div class="review-stats">
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pendingCount; ?></h3>
                            <p>Awaiting Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="ri-eye-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $underReviewCount; ?></h3>
                            <p>Under Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $approvedCount; ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="ri-close-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $rejectedCount; ?></h3>
                            <p>Rejected</p>
                        </div>
                    </div>
                </div>

                <div class="content-header">
                    <h2>Projects Pending Review</h2>
                </div>

                <div class="review-queue">
                    <?php if (empty($pendingProjects)): ?>
                    <div class="empty-state">
                        <i class="ri-checkbox-circle-line"></i>
                        <h3>No Projects to Review</h3>
                        <p>All projects in your department have been reviewed. Check back later for new submissions.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Student</th>
                                    <th>Category</th>
                                    <th>Department</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingProjects as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                                    <td><?php echo htmlspecialchars($project['student_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($project['category'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($project['department'] ?? '-'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-primary btn-sm" onclick="openReviewModal(<?php echo $project['id']; ?>, <?php echo htmlspecialchars(json_encode($project['title'])); ?>, <?php echo htmlspecialchars(json_encode($project['description'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['student_name'] ?? 'Unknown')); ?>, <?php echo htmlspecialchars(json_encode($project['category'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['department'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['github_link'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['team_members'] ?? '')); ?>)">
                                            <i class="ri-eye-line"></i> Review
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Review Modal Template -->
                <div class="review-modal" id="reviewModal" style="display: none;">
                    <div class="modal-content large">
                        <div class="modal-header">
                            <h3>Review Project</h3>
                            <button class="btn-icon" onclick="closeReviewModal()">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="project-details" id="modalProjectDetails">
                                <h4 id="modalProjectTitle">Project Title</h4>
                                <p id="modalProjectDesc">Project description will appear here...</p>
                                <div class="project-meta" style="margin-top: 10px; font-size: 0.9em; color: #666;">
                                    <p><strong>Student:</strong> <span id="modalStudentName"></span></p>
                                    <p><strong>Category:</strong> <span id="modalCategory"></span></p>
                                    <p><strong>Department:</strong> <span id="modalDepartment"></span></p>
                                    <p><strong>Team Members:</strong> <span id="modalTeam"></span></p>
                                    <p id="modalGithubWrap" style="display:none;"><strong>GitHub:</strong> <a id="modalGithub" href="#" target="_blank"></a></p>
                                </div>
                            </div>
                            <form action="sparkBackend.php" method="POST">
                                <input type="hidden" name="action" value="review_project">
                                <input type="hidden" name="project_id" id="modalProjectId" value="">
                                
                                <div class="form-group">
                                    <label>Review Decision</label>
                                    <div class="decision-buttons">
                                        <label class="decision-option approve">
                                            <input type="radio" name="decision" value="approved" required>
                                            <i class="ri-checkbox-circle-line"></i>
                                            <span>Approve</span>
                                        </label>
                                        <label class="decision-option reject">
                                            <input type="radio" name="decision" value="rejected" required>
                                            <i class="ri-close-circle-line"></i>
                                            <span>Reject</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="reviewComments">Comments</label>
                                    <textarea id="reviewComments" name="comments" rows="4" placeholder="Add your review comments..."></textarea>
                                </div>

                                <div class="modal-actions">
                                    <button type="button" class="btn-secondary" onclick="closeReviewModal()">Cancel</button>
                                    <button type="submit" class="btn-primary">Submit Review</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function openReviewModal(projectId, title, description, student, category, department, github, team) {
            document.getElementById('modalProjectId').value = projectId;
            document.getElementById('modalProjectTitle').textContent = title;
            document.getElementById('modalProjectDesc').textContent = description || 'No description provided.';
            document.getElementById('modalStudentName').textContent = student;
            document.getElementById('modalCategory').textContent = category;
            document.getElementById('modalDepartment').textContent = department;
            document.getElementById('modalTeam').textContent = team || 'N/A';
            if (github) {
                document.getElementById('modalGithub').href = github;
                document.getElementById('modalGithub').textContent = github;
                document.getElementById('modalGithubWrap').style.display = 'block';
            } else {
                document.getElementById('modalGithubWrap').style.display = 'none';
            }
            // Reset form
            document.getElementById('reviewComments').value = '';
            var radios = document.querySelectorAll('input[name="decision"]');
            radios.forEach(function(r) { r.checked = false; });
            document.getElementById('reviewModal').style.display = 'flex';
        }
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }
    </script>
</body>

</html>
