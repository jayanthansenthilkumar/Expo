<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');

// Count projects by status
$pendingCount = $conn->query("SELECT COUNT(*) FROM projects WHERE status='pending'")->fetch_row()[0];
$approvedCount = $conn->query("SELECT COUNT(*) FROM projects WHERE status='approved'")->fetch_row()[0];
$rejectedCount = $conn->query("SELECT COUNT(*) FROM projects WHERE status='rejected'")->fetch_row()[0];

// Department filter
$deptFilter = isset($_GET['department']) ? trim($_GET['department']) : '';

// Fetch distinct departments for filter dropdown
$deptResult = $conn->query("SELECT DISTINCT department FROM projects WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$departments = [];
while ($row = $deptResult->fetch_assoc()) {
    $departments[] = $row['department'];
}

// Fetch pending projects with student name
$sql = "SELECT p.*, u.name AS student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status='pending'";
if ($deptFilter !== '') {
    $sql .= " AND p.department = '" . $conn->real_escape_string($deptFilter) . "'";
}
$sql .= " ORDER BY p.created_at DESC";
$pendingProjects = $conn->query($sql);

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
    <title>Approvals | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Approvals';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="approval-stats">
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pendingCount; ?></h3>
                            <p>Pending Approval</p>
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
                    <h2>Pending Approvals</h2>
                    <div class="filter-controls">
                        <form method="GET" action="approvals.php">
                            <select name="department" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $deptFilter === $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="approval-list">
                    <?php if ($pendingProjects && $pendingProjects->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Student</th>
                                    <th>Department</th>
                                    <th>Category</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($project = $pendingProjects->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars($project['student_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($project['department'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($project['category'] ?? '-'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                        <td>
                                            <div style="display:flex;gap:6px;">
                                                <button type="button" class="btn btn-sm btn-success" title="Approve"
                                                    onclick="confirmReview(<?php echo $project['id']; ?>, 'approved', '<?php echo addslashes(htmlspecialchars($project['title'])); ?>')">
                                                    <i class="ri-checkbox-circle-line"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" title="Reject"
                                                    onclick="confirmReview(<?php echo $project['id']; ?>, 'rejected', '<?php echo addslashes(htmlspecialchars($project['title'])); ?>')">
                                                    <i class="ri-close-circle-line"></i> Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ri-checkbox-circle-line"></i>
                            <h3>No Pending Approvals</h3>
                            <p>All projects have been reviewed. Check back later for new submissions.</p>
                        </div>
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

        function confirmReview(projectId, decision, title) {
            const isApprove = (decision === 'approved');
            Swal.fire({
                title: isApprove ? 'Approve Project?' : 'Reject Project?',
                html: `<p>Are you sure you want to ${decision === 'approved' ? 'approve' : 'reject'} <strong>${title}</strong>?</p>
                   <div style="margin-top:1rem;text-align:left;">
                       <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:0.3rem;">Comments (optional)</label>
                       <textarea id="swal-comments" class="swal2-textarea" placeholder="Add review comments..." style="margin:0;width:100%;box-sizing:border-box;"></textarea>
                   </div>`,
                icon: isApprove ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: isApprove ? '<i class="ri-checkbox-circle-line"></i> Approve' : '<i class="ri-close-circle-line"></i> Reject',
                focusConfirm: false,
                preConfirm: () => {
                    return document.getElementById('swal-comments').value.trim();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="review_project">
                    <input type="hidden" name="project_id" value="${projectId}">
                    <input type="hidden" name="decision" value="${decision}">
                    <input type="hidden" name="comments" value="${escapeHtml(result.value || '')}">
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

    <?php include 'includes/bot.php'; ?>
</body>

</html>