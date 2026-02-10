<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');

// Get filter values
$filterCategory = $_GET['category'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterYear = $_GET['year'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($filterCategory) {
    $where .= " AND p.category = ?";
    $params[] = $filterCategory;
    $types .= "s";
}
if ($filterStatus) {
    $where .= " AND p.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}
if ($filterYear) {
    $where .= " AND u.year = ?";
    $params[] = $filterYear;
    $types .= "s";
}

// Helper for generating tab links preserving other filters
function getTabLink($year)
{
    $currentParams = $_GET;
    // Don't include page in filter change (reset to 1 implicitly or explicitly)
    unset($currentParams['page']);
    if ($year === '') {
        unset($currentParams['year']);
    } else {
        $currentParams['year'] = $year;
    }
    return '?' . http_build_query($currentParams);
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM projects p JOIN users u ON p.student_id = u.id $where";
$countStmt = mysqli_prepare($conn, $countQuery);
if ($types) {
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
}
mysqli_stmt_execute($countStmt);
$totalRows = mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
mysqli_stmt_close($countStmt);
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch projects
$query = "SELECT p.*, u.name as student_name, u.department as student_dept, u.year as student_year
          FROM projects p JOIN users u ON p.student_id = u.id
          $where ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$projects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $projects[] = $row;
}
mysqli_stmt_close($stmt);

$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Projects | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'All Projects';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="content-header stacked">
                    <div class="header-top">
                        <h2>Project Management</h2>
                        <div class="filter-controls">
                            <form method="GET" style="display:flex;gap:0.5rem;">
                                <?php if ($filterYear): ?>
                                    <input type="hidden" name="year" value="<?php echo htmlspecialchars($filterYear); ?>">
                                <?php endif; ?>
                                <select class="filter-select" name="category" onchange="this.form.submit()">
                                    <option value="">All Categories</option>
                                    <option value="web" <?php if ($filterCategory === 'web')
                                        echo 'selected'; ?>>Web
                                        Development</option>
                                    <option value="mobile" <?php if ($filterCategory === 'mobile')
                                        echo 'selected'; ?>>
                                        Mobile Application</option>
                                    <option value="ai" <?php if ($filterCategory === 'ai')
                                        echo 'selected'; ?>>AI/Machine
                                        Learning</option>
                                    <option value="iot" <?php if ($filterCategory === 'iot')
                                        echo 'selected'; ?>>IoT
                                    </option>
                                    <option value="blockchain" <?php if ($filterCategory === 'blockchain')
                                        echo 'selected'; ?>>Blockchain</option>
                                    <option value="other" <?php if ($filterCategory === 'other')
                                        echo 'selected'; ?>>Other
                                    </option>
                                </select>
                                <select class="filter-select" name="status" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php if ($filterStatus === 'pending')
                                        echo 'selected'; ?>>
                                        Pending</option>
                                    <option value="approved" <?php if ($filterStatus === 'approved')
                                        echo 'selected'; ?>>
                                        Approved</option>
                                    <option value="rejected" <?php if ($filterStatus === 'rejected')
                                        echo 'selected'; ?>>
                                        Rejected</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tabs-container">
                    <a href="<?php echo getTabLink(''); ?>"
                        class="tab-link <?php echo $filterYear === '' ? 'active' : ''; ?>">All Years</a>
                    <a href="<?php echo getTabLink('I year'); ?>"
                        class="tab-link <?php echo $filterYear === 'I year' ? 'active' : ''; ?>">First Year</a>
                    <a href="<?php echo getTabLink('II year'); ?>"
                        class="tab-link <?php echo $filterYear === 'II year' ? 'active' : ''; ?>">Second Year</a>
                    <a href="<?php echo getTabLink('III year'); ?>"
                        class="tab-link <?php echo $filterYear === 'III year' ? 'active' : ''; ?>">Third Year</a>
                    <a href="<?php echo getTabLink('IV year'); ?>"
                        class="tab-link <?php echo $filterYear === 'IV year' ? 'active' : ''; ?>">Final Year</a>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Team Lead</th>
                                <th>Category</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="7" class="empty-table">
                                        <i class="ri-folder-open-line"></i>
                                        <p>No projects found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projects as $p): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($p['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($p['category'] ?? 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars($p['student_dept'] ?? $p['department'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <span style="padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;
                                            <?php if ($p['status'] === 'approved')
                                                echo 'background:#dcfce7;color:#166534;';
                                            elseif ($p['status'] === 'rejected')
                                                echo 'background:#fef2f2;color:#991b1b;';
                                            else
                                                echo 'background:#fef3c7;color:#92400e;'; ?>">
                                                <?php echo ucfirst($p['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                        <td>
                                            <div style="display:flex;gap:0.5rem;justify-content:center;">
                                                <button class="btn-icon" onclick="viewProject(<?php echo $p['id']; ?>)"
                                                    title="View Details">
                                                    <i class="ri-eye-line"></i>
                                                </button>
                                                <?php if (in_array($_SESSION['role'], ['admin', 'studentaffairs'])): ?>
                                                    <form action="sparkBackend.php" method="POST" style="display:inline;"
                                                        class="confirm-delete-form">
                                                        <input type="hidden" name="action" value="delete_project">
                                                        <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                                        <button type="submit" class="btn-icon" title="Delete"
                                                            style="color:#ef4444;"><i class="ri-delete-bin-line"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => null])); ?>"
                                    class="btn-pagination">« Previous</a>
                            <?php endif; ?>
                            <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => null])); ?>"
                                    class="btn-pagination">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        const projectsData = <?php echo json_encode($projects); ?>;
        const userRole = "<?php echo $_SESSION['role']; ?>";

        function viewProject(id) {
            const project = projectsData.find(p => p.id == id);
            if (!project) return;

            const isCoordinator = userRole === 'departmentcoordinator';
            const isAdminOrAffairs = ['admin', 'studentaffairs'].includes(userRole);
            const isPending = project.status === 'pending';

            let actionButtons = '';

            // Approval Flow for Coordinators
            if (isCoordinator && isPending) {
                actionButtons = `
                    <div style="display:flex;gap:1rem;margin-top:1.5rem;border-top:1px solid #eee;padding-top:1rem;">
                        <button onclick="submitReview(${project.id}, 'approved')" class="btn-primary" style="background:#22c55e;flex:1;">
                            <i class="ri-checkbox-circle-line"></i> Approve
                        </button>
                        <button onclick="submitReview(${project.id}, 'rejected')" class="btn-primary" style="background:#ef4444;flex:1;">
                            <i class="ri-close-circle-line"></i> Reject
                        </button>
                    </div>
                `;
            }
            // Revert Flow for Admin/Affairs
            else if (isAdminOrAffairs && !isPending) {
                actionButtons = `
                    <div style="margin-top:1.5rem;border-top:1px solid #eee;padding-top:1rem;">
                        <button onclick="submitReview(${project.id}, 'pending')" class="btn-secondary" style="width:100%;">
                            <i class="ri-arrow-go-back-line"></i> Revert to Pending
                        </button>
                    </div>
                `;
            }

            Swal.fire({
                title: `<span style="font-size:1.5rem;color:var(--primary);">${escapeHtml(project.title)}</span>`,
                html: `
                    <div style="text-align:left;font-size:0.95rem;line-height:1.6;">
                        <div style="background:#f8fafc;padding:1rem;border-radius:8px;margin-bottom:1rem;border:1px solid #e2e8f0;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.5rem;">
                                <div><strong>ID:</strong> #${project.id}</div>
                                <div><strong>Status:</strong> 
                                    <span style="padding:0.1rem 0.5rem;border-radius:4px;font-size:0.8rem;font-weight:600;
                                        background:${getStatusColor(project.status).bg};color:${getStatusColor(project.status).color};">
                                        ${project.status.toUpperCase()}
                                    </span>
                                </div>
                                <div><strong>Lead:</strong> ${escapeHtml(project.student_name)}</div>
                                <div><strong>Category:</strong> ${escapeHtml(project.category)}</div>
                            </div>
                            <div><strong>Members:</strong> ${escapeHtml(project.team_members || 'N/A')}</div>
                        </div>

                        <div style="margin-bottom:1rem;">
                            <strong style="display:block;margin-bottom:0.3rem;color:var(--text-main);">Abstract:</strong>
                            <div style="max-height:150px;overflow-y:auto;padding:0.5rem;background:white;border:1px solid #eee;border-radius:6px;color:#4b5563;">
                                ${escapeHtml(project.description)}
                            </div>
                        </div>

                        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                            ${project.github_link ? `
                                <a href="${escapeHtml(project.github_link)}" target="_blank" class="btn-secondary btn-sm" style="text-decoration:none;">
                                    <i class="ri-github-fill"></i> GitHub
                                </a>` : ''}
                            ${project.file_path ? `
                                <a href="${escapeHtml(project.file_path)}" target="_blank" class="btn-secondary btn-sm" style="text-decoration:none;">
                                    <i class="ri-file-pdf-line"></i> Documentation
                                </a>` : '<span style="font-size:0.85rem;color:#9ca3af;"><i class="ri-error-warning-line"></i> No Document</span>'}
                        </div>

                        ${actionButtons}
                    </div>
                `,
                width: Math.min(600, window.innerWidth - 40),
                showConfirmButton: false,
                showCloseButton: true,
                focusConfirm: false
            });
        }

        function submitReview(id, decision) {
            const comments = decision === 'rejected' ? 'Rejected by coordinator' : 'Approved by coordinator';
            // Create hidden form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'sparkBackend.php';
            form.innerHTML = `
                <input type="hidden" name="action" value="review_project">
                <input type="hidden" name="project_id" value="${id}">
                <input type="hidden" name="decision" value="${decision}">
                <input type="hidden" name="comments" value="${comments}">
                <input type="hidden" name="redirect" value="allProjects.php">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function getStatusColor(status) {
            if (status === 'approved') return { bg: '#dcfce7', color: '#166534' };
            if (status === 'rejected') return { bg: '#fef2f2', color: '#991b1b' };
            return { bg: '#fef3c7', color: '#92400e' };
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        <?php if ($successMsg): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#2563eb' });
        <?php endif; ?>

        document.querySelectorAll('.confirm-delete-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const formEl = this;
                Swal.fire({
                    title: 'Delete Project?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) formEl.submit();
                });
            });
        });
    </script>
</body>

</html>