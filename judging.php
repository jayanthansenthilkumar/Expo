<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Admin');

// Handle score submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'score_project') {
    $projectId = intval($_POST['project_id']);
    $score = intval($_POST['score']);
    if ($score >= 0 && $score <= 100) {
        $stmt = $conn->prepare("UPDATE projects SET score = ? WHERE id = ? AND status = 'approved'");
        $stmt->bind_param('ii', $score, $projectId);
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = 'Score updated successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to update score.';
            $_SESSION['flash_type'] = 'error';
        }
        $stmt->close();
    } else {
        $_SESSION['flash_message'] = 'Score must be between 0 and 100.';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: judging.php');
    exit;
}

// Fetch approved projects with student name
$projects = [];
$result = $conn->query("SELECT p.*, u.name as student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'approved' ORDER BY p.score DESC, p.title ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Count totals
$totalApproved = count($projects);
$scoredCount = 0;
foreach ($projects as $p) {
    if ($p['score'] > 0) $scoredCount++;
}
$unscoredCount = $totalApproved - $scoredCount;
$progressPercent = $totalApproved > 0 ? round(($scoredCount / $totalApproved) * 100) : 0;

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
    <title>Judging | SPARK'26</title>
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
                    <h1>Judging Panel</h1>
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
                <div class="content-header">
                    <h2>Judging Management</h2>
                    <button class="btn-primary">
                        <i class="ri-add-line"></i> Add Judge
                    </button>
                </div>

                <div class="judging-section">
                    <div class="judging-criteria">
                        <h3>Judging Criteria</h3>
                        <div class="criteria-list">
                            <div class="criteria-item">
                                <span class="criteria-name">Innovation</span>
                                <span class="criteria-weight">25%</span>
                            </div>
                            <div class="criteria-item">
                                <span class="criteria-name">Technical Complexity</span>
                                <span class="criteria-weight">25%</span>
                            </div>
                            <div class="criteria-item">
                                <span class="criteria-name">Practicality</span>
                                <span class="criteria-weight">20%</span>
                            </div>
                            <div class="criteria-item">
                                <span class="criteria-name">Presentation</span>
                                <span class="criteria-weight">15%</span>
                            </div>
                            <div class="criteria-item">
                                <span class="criteria-name">Documentation</span>
                                <span class="criteria-weight">15%</span>
                            </div>
                        </div>
                        <button class="btn-secondary">
                            <i class="ri-edit-line"></i> Edit Criteria
                        </button>
                    </div>

                    <div class="judges-panel">
                        <h3>Projects to Judge</h3>
                        <?php if (empty($projects)): ?>
                        <div class="empty-state">
                            <i class="ri-file-list-3-line"></i>
                            <h4>No Approved Projects</h4>
                            <p>There are no approved projects to judge yet</p>
                        </div>
                        <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Project Title</th>
                                        <th>Student</th>
                                        <th>Department</th>
                                        <th>Category</th>
                                        <th>Current Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars($project['student_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($project['department'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($project['category'] ?? '-'); ?></td>
                                        <td><?php echo $project['score'] > 0 ? $project['score'] . '/100' : '<span style="color:#999;">Not scored</span>'; ?></td>
                                        <td>
                                            <button class="btn-primary" onclick="openScoreModal(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars(addslashes($project['title'])); ?>', <?php echo intval($project['score']); ?>)">
                                                <i class="ri-star-line"></i> <?php echo $project['score'] > 0 ? 'Update Score' : 'Score'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="judging-progress">
                    <h3>Judging Progress</h3>
                    <div class="progress-stats">
                        <div class="progress-item">
                            <span class="progress-label">Projects Judged</span>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%"></div>
                            </div>
                            <span class="progress-value"><?php echo $scoredCount; ?>/<?php echo $totalApproved; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Score Modal -->
                <div id="scoreModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
                    <div style="background:#fff; border-radius:12px; padding:30px; max-width:400px; width:90%; margin:auto; position:relative; top:50%; transform:translateY(-50%);">
                        <h3 id="scoreModalTitle" style="margin-bottom:20px;">Score Project</h3>
                        <form method="POST" action="judging.php">
                            <input type="hidden" name="action" value="score_project">
                            <input type="hidden" name="project_id" id="scoreProjectId">
                            <div style="margin-bottom:16px;">
                                <label style="display:block; margin-bottom:6px; font-weight:500;">Score (0-100)</label>
                                <input type="number" name="score" id="scoreInput" min="0" max="100" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
                            </div>
                            <div style="display:flex; gap:10px; justify-content:flex-end;">
                                <button type="button" class="btn-secondary" onclick="closeScoreModal()">Cancel</button>
                                <button type="submit" class="btn-primary">Submit Score</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function openScoreModal(projectId, title, currentScore) {
            document.getElementById('scoreProjectId').value = projectId;
            document.getElementById('scoreModalTitle').textContent = 'Score: ' + title;
            document.getElementById('scoreInput').value = currentScore > 0 ? currentScore : '';
            document.getElementById('scoreModal').style.display = 'block';
        }
        function closeScoreModal() {
            document.getElementById('scoreModal').style.display = 'none';
        }
        document.getElementById('scoreModal').addEventListener('click', function(e) {
            if (e.target === this) closeScoreModal();
        });
    </script>
    <script>
    <?php if ($flashMessage): ?>
    Swal.fire({ icon: '<?php echo $flashType === "success" ? "success" : "error"; ?>', title: '<?php echo $flashType === "success" ? "Success!" : "Oops!"; ?>', text: '<?php echo addslashes($flashMessage); ?>', confirmButtonColor: '#2563eb'<?php if ($flashType === "success"): ?>, timer: 3000, timerProgressBar: true<?php endif; ?> });
    <?php endif; ?>
    </script>
</body>

</html>
