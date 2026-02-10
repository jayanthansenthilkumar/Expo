<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDepartment = $_SESSION['department'] ?? '';

// Multi-department support (AIDS & AIML share one coordinator)
$deptFilter = buildDeptFilter($userDepartment);
$dp = $deptFilter['placeholders'];
$dt = $deptFilter['types'];
$dv = $deptFilter['values'];

// Filter parameters
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$where = "WHERE p.department IN ($dp)";
$params = $dv;
$types = $dt;

if ($categoryFilter !== '') {
    $where .= " AND p.category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}
if ($statusFilter !== '') {
    $where .= " AND p.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Count total projects
$countSql = "SELECT COUNT(*) as total FROM projects p $where";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalProjects = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalProjects / $perPage));
$countStmt->close();

// Fetch projects with student name
$sql = "SELECT p.*, u.name AS student_name
        FROM projects p
        LEFT JOIN users u ON p.student_id = u.id
        $where
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <title>Department Projects | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Department Projects';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="content-header">
                    <h2>Projects in Your Department</h2>
                    <form method="GET" class="filter-controls">
                        <select name="category" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <option value="web" <?php echo $categoryFilter === 'web' ? 'selected' : ''; ?>>Web Development
                            </option>
                            <option value="mobile" <?php echo $categoryFilter === 'mobile' ? 'selected' : ''; ?>>Mobile
                                Application</option>
                            <option value="ai" <?php echo $categoryFilter === 'ai' ? 'selected' : ''; ?>>AI/Machine
                                Learning</option>
                            <option value="iot" <?php echo $categoryFilter === 'iot' ? 'selected' : ''; ?>>IoT</option>
                            <option value="blockchain" <?php echo $categoryFilter === 'blockchain' ? 'selected' : ''; ?>>
                                Blockchain</option>
                            <option value="other" <?php echo $categoryFilter === 'other' ? 'selected' : ''; ?>>Other
                            </option>
                        </select>
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending
                            </option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>
                                Approved</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>
                                Rejected</option>
                        </select>
                    </form>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Team Lead</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="6" class="empty-table">
                                        <i class="ri-folder-open-line"></i>
                                        <p>No projects in your department yet</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars($project['student_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($project['category']); ?></td>
                                        <td><span
                                                class="status-badge status-<?php echo htmlspecialchars($project['status']); ?>"><?php echo ucfirst(htmlspecialchars($project['status'])); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                        <td>
                                            <?php if ($project['status'] === 'pending'): ?>
                                                <a href="reviewApprove.php" class="btn-primary btn-sm"
                                                    style="text-decoration:none;">
                                                    <i class="ri-eye-line"></i> Review
                                                </a>
                                            <?php else: ?>
                                                <button class="btn-view btn-sm"
                                                    onclick="openViewModal(<?php echo $project['id']; ?>, <?php echo htmlspecialchars(json_encode($project['title'])); ?>, <?php echo htmlspecialchars(json_encode($project['description'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['student_name'] ?? 'Unknown')); ?>, <?php echo htmlspecialchars(json_encode($project['category'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['department'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['github_link'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['team_members'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['review_comments'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['status'])); ?>)">
                                                    <i class="ri-eye-line"></i> View
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&page=<?php echo $page - 1; ?>"
                            class="btn-pagination">&laquo; Previous</a>
                    <?php else: ?>
                        <button class="btn-pagination" disabled>&laquo; Previous</button>
                    <?php endif; ?>
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&page=<?php echo $page + 1; ?>"
                            class="btn-pagination">Next &raquo;</a>
                    <?php else: ?>
                        <button class="btn-pagination" disabled>Next &raquo;</button>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        <?php if ($flashMessage): ?>
            Swal.fire({ icon: '<?php echo $flashType === "success" ? "success" : "error"; ?>', title: '<?php echo $flashType === "success" ? "Success!" : "Oops!"; ?>', text: '<?php echo htmlspecialchars($flashMessage, ENT_QUOTES); ?>', confirmButtonColor: '#2563eb'<?php if ($flashType === "success"): ?>, timer: 3000, timerProgressBar: true<?php endif; ?> });
        <?php endif; ?>

        function openViewModal(projectId, title, description, student, category, department, github, team, comments, currentStatus) {
            const githubHtml = github
                ? `<p><strong>GitHub:</strong> <a href="${escapeHtml(github)}" target="_blank" style="color:#2563eb;">${escapeHtml(github)}</a></p>`
                : '';

            const statusColor = currentStatus === 'approved' ? '#22c55e' : '#ef4444';
            const statusLabel = currentStatus === 'approved' ? 'Approved' : 'Rejected';
            const statusIcon = currentStatus === 'approved' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line';

            Swal.fire({
                title: 'Project Details',
                html: `
                <div style="text-align:left;">
                    <div style="background:#f8fafc;border-radius:8px;padding:1rem;margin-bottom:1rem;border:1px solid #e2e8f0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                            <h4 style="margin:0;color:#1e293b;">${escapeHtml(title)}</h4>
                            <span style="background:${statusColor}20;color:${statusColor};padding:0.25rem 0.75rem;border-radius:20px;font-size:0.8rem;font-weight:600;"><i class="${statusIcon}"></i> ${statusLabel}</span>
                        </div>
                        <p style="margin:0 0 0.75rem 0;color:#475569;font-size:0.9rem;">${escapeHtml(description || 'No description provided.')}</p>
                        <div style="font-size:0.85rem;color:#64748b;line-height:1.8;">
                            <p style="margin:0;"><strong>Student:</strong> ${escapeHtml(student)}</p>
                            <p style="margin:0;"><strong>Category:</strong> ${escapeHtml(category)}</p>
                            <p style="margin:0;"><strong>Department:</strong> ${escapeHtml(department)}</p>
                            <p style="margin:0;"><strong>Team:</strong> ${escapeHtml(team || 'N/A')}</p>
                            ${githubHtml}
                        </div>
                    </div>
                    ${comments ? `<div style="background:#fef9c3;border-radius:8px;padding:0.75rem 1rem;margin-bottom:1rem;border:1px solid #fde047;"><strong style="font-size:0.85rem;">Review Comments:</strong><p style="margin:0.25rem 0 0 0;font-size:0.9rem;color:#713f12;">${escapeHtml(comments)}</p></div>` : ''}
                    <div style="margin-bottom:0.75rem;">
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.5rem;">Change Decision</label>
                        <div style="display:flex;gap:0.75rem;">
                            ${currentStatus !== 'approved' ? `<label style="display:flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border:2px solid #22c55e;border-radius:8px;cursor:pointer;flex:1;justify-content:center;transition:all 0.2s;" id="swal-view-approve-label">
                                <input type="radio" name="swal-view-decision" value="approved" style="cursor:pointer;" onchange="this.closest('.swal2-html-container').querySelectorAll('[id^=swal-view-]').forEach(l => { if(l.tagName==='LABEL') l.style.background='transparent'; }); this.closest('label').style.background='#dcfce7';">
                                <i class="ri-checkbox-circle-line" style="color:#22c55e;"></i> <span style="font-weight:500;">Approve</span>
                            </label>` : ''}
                            <label style="display:flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border:2px solid #f59e0b;border-radius:8px;cursor:pointer;flex:1;justify-content:center;transition:all 0.2s;" id="swal-view-pending-label">
                                <input type="radio" name="swal-view-decision" value="pending" style="cursor:pointer;" onchange="this.closest('.swal2-html-container').querySelectorAll('[id^=swal-view-]').forEach(l => { if(l.tagName==='LABEL') l.style.background='transparent'; }); this.closest('label').style.background='#fef3c7';">
                                <i class="ri-arrow-go-back-line" style="color:#f59e0b;"></i> <span style="font-weight:500;">Revert to Pending</span>
                            </label>
                            ${currentStatus !== 'rejected' ? `<label style="display:flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border:2px solid #ef4444;border-radius:8px;cursor:pointer;flex:1;justify-content:center;transition:all 0.2s;" id="swal-view-reject-label">
                                <input type="radio" name="swal-view-decision" value="rejected" style="cursor:pointer;" onchange="this.closest('.swal2-html-container').querySelectorAll('[id^=swal-view-]').forEach(l => { if(l.tagName==='LABEL') l.style.background='transparent'; }); this.closest('label').style.background='#fef2f2';">
                                <i class="ri-close-circle-line" style="color:#ef4444;"></i> <span style="font-weight:500;">Reject</span>
                            </label>` : ''}
                        </div>
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Updated Comments</label>
                        <textarea id="swal-view-comments" class="swal2-textarea" rows="3" placeholder="Add updated comments..." style="margin:0;width:100%;box-sizing:border-box;"></textarea>
                    </div>
                </div>
            `,
                confirmButtonText: '<i class="ri-refresh-line"></i> Update Decision',
                confirmButtonColor: '#2563eb',
                showDenyButton: true,
                denyButtonText: 'Close',
                denyButtonColor: '#6b7280',
                showCancelButton: false,
                width: '600px',
                focusConfirm: false,
                preConfirm: () => {
                    const decision = document.querySelector('input[name="swal-view-decision"]:checked');
                    if (!decision) {
                        Swal.showValidationMessage('Please select a new decision');
                        return false;
                    }
                    return {
                        decision: decision.value,
                        comments: document.getElementById('swal-view-comments').value.trim()
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const d = result.value;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="review_project">
                    <input type="hidden" name="project_id" value="${projectId}">
                    <input type="hidden" name="decision" value="${escapeHtml(d.decision)}">
                    <input type="hidden" name="comments" value="${escapeHtml(d.comments)}">
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