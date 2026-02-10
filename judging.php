<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? 'Admin');

// Fetch approved projects with student name using prepared statement
$projects = [];
$stmt = mysqli_prepare($conn, "SELECT p.*, u.name as student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'approved' ORDER BY p.score DESC, p.title ASC");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Count totals
$totalApproved = count($projects);
$scoredCount = 0;
foreach ($projects as $p) {
    if ($p['score'] > 0)
        $scoredCount++;
}
$unscoredCount = $totalApproved - $scoredCount;
$progressPercent = $totalApproved > 0 ? round(($scoredCount / $totalApproved) * 100) : 0;

// Flash messages
$flashMessage = $_SESSION['flash_message'] ?? $_SESSION['success'] ?? null;
$flashType = $_SESSION['flash_type'] ?? (isset($_SESSION['success']) ? 'success' : (isset($_SESSION['error']) ? 'error' : 'info'));
if (isset($_SESSION['error'])) {
    $flashMessage = $_SESSION['error'];
    $flashType = 'error';
}
unset($_SESSION['flash_message'], $_SESSION['flash_type'], $_SESSION['success'], $_SESSION['error']);
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
            <?php
            $pageTitle = 'Judging Panel';
            include 'includes/header.php';
            ?>

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
                                                <td><?php echo $project['score'] > 0 ? $project['score'] . '/100' : '<span style="color:#999;">Not scored</span>'; ?>
                                                </td>
                                                <td>
                                                    <button class="btn-primary"
                                                        onclick="openScoreModal(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars(addslashes($project['title'])); ?>', <?php echo intval($project['score']); ?>)">
                                                        <i class="ri-star-line"></i>
                                                        <?php echo $project['score'] > 0 ? 'Update Score' : 'Score'; ?>
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
                            <span
                                class="progress-value"><?php echo $scoredCount; ?>/<?php echo $totalApproved; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Score handled via SweetAlert -->
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function openScoreModal(projectId, title, currentScore) {
            Swal.fire({
                title: 'Score: ' + title,
                html: `
                    <div style="text-align:left;">
                        <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:0.3rem;">Score (0-100)</label>
                        <input id="swal-score" type="number" class="swal2-input" min="0" max="100" value="${currentScore > 0 ? currentScore : ''}" placeholder="Enter score" style="margin:0;width:100%;box-sizing:border-box;">
                    </div>
                `,
                confirmButtonText: '<i class="ri-star-line"></i> Submit Score',
                confirmButtonColor: '#2563eb',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                focusConfirm: false,
                preConfirm: () => {
                    const score = parseInt(document.getElementById('swal-score').value);
                    if (isNaN(score) || score < 0 || score > 100) {
                        Swal.showValidationMessage('Score must be between 0 and 100');
                        return false;
                    }
                    return score;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="score_project">
                        <input type="hidden" name="project_id" value="${projectId}">
                        <input type="hidden" name="score" value="${result.value}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
    <script>
        <?php if ($flashMessage): ?>
            Swal.fire({ icon: '<?php echo $flashType === "success" ? "success" : "error"; ?>', title: '<?php echo $flashType === "success" ? "Success!" : "Oops!"; ?>', text: '<?php echo htmlspecialchars($flashMessage, ENT_QUOTES); ?>', confirmButtonColor: '#2563eb'<?php if ($flashType === "success"): ?>, timer: 3000, timerProgressBar: true<?php endif; ?> });
        <?php endif; ?>
    </script>
</body>

</html>