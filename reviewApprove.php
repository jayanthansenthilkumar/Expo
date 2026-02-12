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
$filterByDept = ($role === 'departmentcoordinator');

// Multi-department support (AIDS & AIML share one coordinator)
$deptFilter = buildDeptFilter($userDepartment);
$dp = $deptFilter['placeholders'];
$dt = $deptFilter['types'];
$dv = $deptFilter['values'];

// Count pending projects
if ($filterByDept) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending' AND department IN ($dp)");
    mysqli_stmt_bind_param($stmt, $dt, ...$dv);
    mysqli_stmt_execute($stmt);
    $pendingCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
    mysqli_stmt_close($stmt);
} else {
    $pendingCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending'"))['cnt'];
}

// Count approved projects
if ($filterByDept) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved' AND department IN ($dp)");
    mysqli_stmt_bind_param($stmt, $dt, ...$dv);
    mysqli_stmt_execute($stmt);
    $approvedCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
    mysqli_stmt_close($stmt);
} else {
    $approvedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];
}

// Count rejected projects
if ($filterByDept) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'rejected' AND department IN ($dp)");
    mysqli_stmt_bind_param($stmt, $dt, ...$dv);
    mysqli_stmt_execute($stmt);
    $rejectedCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
    mysqli_stmt_close($stmt);
} else {
    $rejectedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'rejected'"))['cnt'];
}

// Fetch pending projects with student name
if ($filterByDept) {
    $stmt = mysqli_prepare($conn, "SELECT p.*, u.name AS student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'pending' AND p.department IN ($dp) ORDER BY p.created_at DESC");
    mysqli_stmt_bind_param($stmt, $dt, ...$dv);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn, "SELECT p.*, u.name AS student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'pending' ORDER BY p.created_at DESC");
}
$pendingProjects = [];
while ($row = mysqli_fetch_assoc($res)) {
    $pendingProjects[] = $row;
}
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}

// Fetch rejected projects (for reconsider)
if ($filterByDept) {
    $stmt = mysqli_prepare($conn, "SELECT p.*, u.name AS student_name, r.name AS reviewer_name FROM projects p LEFT JOIN users u ON p.student_id = u.id LEFT JOIN users r ON p.reviewed_by = r.id WHERE p.status = 'rejected' AND p.department IN ($dp) ORDER BY p.reviewed_at DESC");
    mysqli_stmt_bind_param($stmt, $dt, ...$dv);
    mysqli_stmt_execute($stmt);
    $res2 = mysqli_stmt_get_result($stmt);
} else {
    $res2 = mysqli_query($conn, "SELECT p.*, u.name AS student_name, r.name AS reviewer_name FROM projects p LEFT JOIN users u ON p.student_id = u.id LEFT JOIN users r ON p.reviewed_by = r.id WHERE p.status = 'rejected' ORDER BY p.reviewed_at DESC");
}
$rejectedProjects = [];
while ($row = mysqli_fetch_assoc($res2)) {
    $rejectedProjects[] = $row;
}
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}

// Fetch approved projects (for reconsider)
if ($filterByDept) {
    $stmt = mysqli_prepare($conn, "SELECT p.*, u.name AS student_name, r.name AS reviewer_name FROM projects p LEFT JOIN users u ON p.student_id = u.id LEFT JOIN users r ON p.reviewed_by = r.id WHERE p.status = 'approved' AND p.department IN ($dp) ORDER BY p.reviewed_at DESC");
    mysqli_stmt_bind_param($stmt, $dt, ...$dv);
    mysqli_stmt_execute($stmt);
    $res3 = mysqli_stmt_get_result($stmt);
} else {
    $res3 = mysqli_query($conn, "SELECT p.*, u.name AS student_name, r.name AS reviewer_name FROM projects p LEFT JOIN users u ON p.student_id = u.id LEFT JOIN users r ON p.reviewed_by = r.id WHERE p.status = 'approved' ORDER BY p.reviewed_at DESC");
}
$approvedProjects = [];
while ($row = mysqli_fetch_assoc($res3)) {
    $approvedProjects[] = $row;
}
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}

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
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.2/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        .review-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0;
        }
        .review-tab {
            padding: 0.75rem 1.5rem;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            color: #64748b;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .review-tab:hover {
            color: #1e293b;
            background: #f8fafc;
            border-radius: 8px 8px 0 0;
        }
        .review-tab.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
            font-weight: 600;
        }
        .tab-badge {
            font-size: 0.75rem;
            padding: 0.15rem 0.55rem;
            border-radius: 10px;
            font-weight: 600;
            line-height: 1;
        }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fef2f2; color: #991b1b; }
        .review-tab-content {
            display: none;
        }
        .review-tab-content.active {
            display: block;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Review & Approve';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

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

                <!-- Tabs -->
                <div class="review-tabs">
                    <button class="review-tab active" data-tab="pending" onclick="switchTab('pending')">
                        <i class="ri-time-line"></i> Pending <span class="tab-badge badge-amber"><?php echo $pendingCount; ?></span>
                    </button>
                    <button class="review-tab" data-tab="approved" onclick="switchTab('approved')">
                        <i class="ri-checkbox-circle-line"></i> Approved <span class="tab-badge badge-green"><?php echo $approvedCount; ?></span>
                    </button>
                    <button class="review-tab" data-tab="rejected" onclick="switchTab('rejected')">
                        <i class="ri-close-circle-line"></i> Rejected <span class="tab-badge badge-red"><?php echo $rejectedCount; ?></span>
                    </button>
                </div>

                <!-- Pending Tab -->
                <div class="review-tab-content active" id="tab-pending">
                <div class="review-queue">
                    <?php if (empty($pendingProjects)): ?>
                        <div class="empty-state">
                            <i class="ri-checkbox-circle-line"></i>
                            <h3>No Projects to Review</h3>
                            <p>All projects in your department have been reviewed. Check back later for new submissions.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <div class="table-export-bar">
                                <span class="export-label">Export</span>
                                <div class="export-btn-group" data-table="pendingReviewTable" data-filename="Pending_Review_Projects">
                                    <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                                    <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                                </div>
                            </div>
                            <table class="data-table" id="pendingReviewTable">
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
                                                <button class="btn-primary btn-sm"
                                                    onclick="openReviewModal(<?php echo $project['id']; ?>, <?php echo htmlspecialchars(json_encode($project['title'])); ?>, <?php echo htmlspecialchars(json_encode($project['description'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['student_name'] ?? 'Unknown')); ?>, <?php echo htmlspecialchars(json_encode($project['category'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['department'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['github_link'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['team_members'] ?? '')); ?>)">
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
                </div>

                <!-- Approved Tab -->
                <div class="review-tab-content" id="tab-approved">
                <div class="review-queue">
                    <?php if (empty($approvedProjects)): ?>
                        <div class="empty-state">
                            <i class="ri-checkbox-circle-line"></i>
                            <h3>No Approved Projects</h3>
                            <p>No projects have been approved yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <div class="table-export-bar">
                                <span class="export-label">Export</span>
                                <div class="export-btn-group" data-table="approvedProjectsTable" data-filename="Approved_Projects">
                                    <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                                    <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                                </div>
                            </div>
                            <table class="data-table" id="approvedProjectsTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Student</th>
                                        <th>Category</th>
                                        <th>Department</th>
                                        <th>Reviewed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approvedProjects as $project): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project['title']); ?></td>
                                            <td><?php echo htmlspecialchars($project['student_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($project['category'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($project['department'] ?? '-'); ?></td>
                                            <td><?php echo $project['reviewed_at'] ? date('M d, Y', strtotime($project['reviewed_at'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <button class="btn-view btn-sm"
                                                    onclick="openViewModal(<?php echo $project['id']; ?>, <?php echo htmlspecialchars(json_encode($project['title'])); ?>, <?php echo htmlspecialchars(json_encode($project['description'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['student_name'] ?? 'Unknown')); ?>, <?php echo htmlspecialchars(json_encode($project['category'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['department'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['github_link'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['team_members'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['review_comments'] ?? '')); ?>, 'approved')">
                                                    <i class="ri-eye-line"></i> View
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

                <!-- Rejected Tab -->
                <div class="review-tab-content" id="tab-rejected">
                <div class="review-queue">
                    <?php if (empty($rejectedProjects)): ?>
                        <div class="empty-state">
                            <i class="ri-close-circle-line"></i>
                            <h3>No Rejected Projects</h3>
                            <p>No projects have been rejected.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <div class="table-export-bar">
                                <span class="export-label">Export</span>
                                <div class="export-btn-group" data-table="rejectedProjectsTable" data-filename="Rejected_Projects">
                                    <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                                    <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                                </div>
                            </div>
                            <table class="data-table" id="rejectedProjectsTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Student</th>
                                        <th>Category</th>
                                        <th>Department</th>
                                        <th>Rejected On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rejectedProjects as $project): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project['title']); ?></td>
                                            <td><?php echo htmlspecialchars($project['student_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($project['category'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($project['department'] ?? '-'); ?></td>
                                            <td><?php echo $project['reviewed_at'] ? date('M d, Y', strtotime($project['reviewed_at'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <button class="btn-view btn-sm"
                                                    onclick="openViewModal(<?php echo $project['id']; ?>, <?php echo htmlspecialchars(json_encode($project['title'])); ?>, <?php echo htmlspecialchars(json_encode($project['description'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['student_name'] ?? 'Unknown')); ?>, <?php echo htmlspecialchars(json_encode($project['category'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['department'] ?? '-')); ?>, <?php echo htmlspecialchars(json_encode($project['github_link'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['team_members'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($project['review_comments'] ?? '')); ?>, 'rejected')">
                                                    <i class="ri-eye-line"></i> View
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
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/tableExport.js?v=2"></script>
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.review-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.review-tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector(`.review-tab[data-tab="${tab}"]`).classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }

        function openReviewModal(projectId, title, description, student, category, department, github, team) {
            const githubHtml = github
                ? `<p><strong>GitHub:</strong> <a href="${escapeHtml(github)}" target="_blank" style="color:#2563eb;">${escapeHtml(github)}</a></p>`
                : '';

            Swal.fire({
                title: 'Review Project',
                html: `
                <div style="text-align:left;">
                    <div style="background:#f8fafc;border-radius:8px;padding:1rem;margin-bottom:1rem;border:1px solid #e2e8f0;">
                        <h4 style="margin:0 0 0.5rem 0;color:#1e293b;">${escapeHtml(title)}</h4>
                        <p style="margin:0 0 0.75rem 0;color:#475569;font-size:0.9rem;">${escapeHtml(description || 'No description provided.')}</p>
                        <div style="font-size:0.85rem;color:#64748b;line-height:1.8;">
                            <p style="margin:0;"><strong>Student:</strong> ${escapeHtml(student)}</p>
                            <p style="margin:0;"><strong>Category:</strong> ${escapeHtml(category)}</p>
                            <p style="margin:0;"><strong>Department:</strong> ${escapeHtml(department)}</p>
                            <p style="margin:0;"><strong>Team:</strong> ${escapeHtml(team || 'N/A')}</p>
                            ${githubHtml}
                        </div>
                    </div>
                    <div style="margin-bottom:0.75rem;">
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.5rem;">Decision *</label>
                        <div style="display:flex;gap:1rem;">
                            <label style="display:flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border:2px solid #22c55e;border-radius:8px;cursor:pointer;flex:1;justify-content:center;transition:all 0.2s;" id="swal-approve-label">
                                <input type="radio" name="swal-decision" value="approved" id="swal-approve-radio" style="cursor:pointer;" onchange="this.closest('.swal2-html-container').querySelector('#swal-reject-label').style.background='transparent'; this.closest('label').style.background='#dcfce7';">
                                <i class="ri-checkbox-circle-line" style="color:#22c55e;"></i> <span style="font-weight:500;">Approve</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;border:2px solid #ef4444;border-radius:8px;cursor:pointer;flex:1;justify-content:center;transition:all 0.2s;" id="swal-reject-label">
                                <input type="radio" name="swal-decision" value="rejected" id="swal-reject-radio" style="cursor:pointer;" onchange="this.closest('.swal2-html-container').querySelector('#swal-approve-label').style.background='transparent'; this.closest('label').style.background='#fef2f2';">
                                <i class="ri-close-circle-line" style="color:#ef4444;"></i> <span style="font-weight:500;">Reject</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Comments</label>
                        <textarea id="swal-comments" class="swal2-textarea" rows="3" placeholder="Add your review comments..." style="margin:0;width:100%;box-sizing:border-box;"></textarea>
                    </div>
                </div>
            `,
                confirmButtonText: '<i class="ri-check-double-line"></i> Submit Review',
                confirmButtonColor: '#2563eb',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                width: Math.min(600, window.innerWidth - 40) + 'px',
                focusConfirm: false,
                preConfirm: () => {
                    const decision = document.querySelector('input[name="swal-decision"]:checked');
                    if (!decision) {
                        Swal.showValidationMessage('Please select Approve or Reject');
                        return false;
                    }
                    return {
                        decision: decision.value,
                        comments: document.getElementById('swal-comments').value.trim()
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
                showCancelButton: true,
                showDenyButton: true,
                denyButtonText: 'Close',
                denyButtonColor: '#6b7280',
                cancelButtonText: '',
                showCancelButton: false,
                width: Math.min(600, window.innerWidth - 40) + 'px',
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

        <?php if ($flashSuccess): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo htmlspecialchars($flashSuccess, ENT_QUOTES); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($flashError): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo htmlspecialchars($flashError, ENT_QUOTES); ?>', confirmButtonColor: '#2563eb' });
        <?php endif; ?>
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>