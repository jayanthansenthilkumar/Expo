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

// Fetch projects with student name and team name
$sql = "SELECT p.*, u.name AS student_name, t.team_name AS team_name
        FROM projects p
        LEFT JOIN users u ON p.student_id = u.id
        LEFT JOIN teams t ON p.team_id = t.id
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
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.2/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
                    <div class="table-export-bar">
                        <span class="export-label">Export</span>
                        <div class="export-btn-group" data-table="deptProjectsTable" data-filename="Department_Projects">
                            <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                            <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                        </div>
                    </div>
                    <table class="data-table" id="deptProjectsTable">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Team Lead</th>
                                <th>Team Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>GitHub</th>
                                <th>File</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="10" class="empty-table">
                                        <i class="ri-folder-open-line"></i>
                                        <p>No projects in your department yet</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars($project['student_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($project['team_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($project['category']); ?></td>
                                        <td title="<?php echo htmlspecialchars($project['description'] ?? ''); ?>">
                                            <?php
                                            $desc = $project['description'] ?? '';
                                            echo htmlspecialchars(mb_strlen($desc) > 60 ? mb_substr($desc, 0, 60) . '...' : $desc);
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($project['github_link'])): ?>
                                                <a href="<?php echo htmlspecialchars($project['github_link']); ?>" target="_blank" title="<?php echo htmlspecialchars($project['github_link']); ?>" style="color:#2563eb;"><i class="ri-github-line"></i> Repo</a>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($project['file_path'])): ?>
                                                <a href="javascript:void(0)" onclick="viewPdf('<?php echo htmlspecialchars($project['file_path'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($project['title'], ENT_QUOTES); ?>')" title="View PDF" style="color:#2563eb;cursor:pointer;"><i class="ri-file-pdf-2-line"></i> PDF</a>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span
                                                class="status-badge status-<?php echo htmlspecialchars($project['status']); ?>"><?php echo ucfirst(htmlspecialchars($project['status'])); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                        <td>
                                            <button class="btn-view btn-sm"
                                                onclick="viewProject(<?php echo $project['id']; ?>)">
                                                <i class="ri-eye-line"></i> View/Review
                                            </button>
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
    <script src="assets/js/tableExport.js?v=2"></script>
    <script>
        <?php if ($flashMessage): ?>
            Swal.fire({ icon: '<?php echo $flashType === "success" ? "success" : "error"; ?>', title: '<?php echo $flashType === "success" ? "Success!" : "Oops!"; ?>', text: '<?php echo htmlspecialchars($flashMessage, ENT_QUOTES); ?>', confirmButtonColor: '#2563eb'<?php if ($flashType === "success"): ?>, timer: 3000, timerProgressBar: true<?php endif; ?> });
        <?php endif; ?>

        const projectsData = <?php echo json_encode($projects); ?>;
        const userRole = "<?php echo $_SESSION['role'] ?? $_SESSION['user_role'] ?? ''; ?>";

        function viewProject(id) {
            const project = projectsData.find(p => p.id == id);
            if (!project) return;

            const isCoordinator = ['departmentcoordinator', 'coordinator'].includes(userRole.toLowerCase());
            const isAdminOrAffairs = ['admin', 'studentaffairs'].includes(userRole.toLowerCase());
            const isPending = project.status === 'pending';

            let actionButtons = '';

            // Approval Flow for Coordinators
            if (isCoordinator && isPending) {
                actionButtons = `
                    <div style="display:flex;gap:1rem;margin-top:1.5rem;border-top:1px solid #eee;padding-top:1rem;">
                        <button onclick="submitReview(${project.id}, 'approved')" class="btn-primary" style="background:#22c55e;flex:1;border:none;">
                            <i class="ri-checkbox-circle-line"></i> Approve
                        </button>
                        <button onclick="submitReview(${project.id}, 'rejected')" class="btn-primary" style="background:#ef4444;flex:1;border:none;">
                            <i class="ri-close-circle-line"></i> Reject
                        </button>
                    </div>
                `;
            }
            // Revert Flow for Admin/Affairs (or View Only)
            else if (isAdminOrAffairs && !isPending) {
                actionButtons = `
                     <div style="margin-top:1.5rem;border-top:1px solid #eee;padding-top:1rem;">
                        <button onclick="submitReview(${project.id}, 'pending')" class="btn-primary" style="background:#f59e0b;width:100%;border:none;">
                            <i class="ri-arrow-go-back-line"></i> Revert to Pending
                        </button>
                    </div>
                `;
            }

            // Generate Member List
            let membersList = 'No members listed';
            if (project.team_members) {
                membersList = project.team_members.split(',').map(m => `<span style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:0.85rem;margin-right:4px;display:inline-block;margin-bottom:4px;">${escapeHtml(m.trim())}</span>`).join('');
            }

            Swal.fire({
                title: '',
                width: Math.min(600, window.innerWidth - 40) + 'px',
                padding: '0',
                showConfirmButton: false,
                showCloseButton: true,
                html: `
                    <div style="text-align:left;">
                        <div style="padding:1.5rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <div style="display:flex;justify-content:space-between;align-items:start;gap:1rem;">
                                <h2 style="font-size:1.25rem;margin:0;color:#1e293b;line-height:1.4;">${escapeHtml(project.title)}</h2>
                                <span class="status-badge status-${project.status}" style="font-size:0.75rem;padding:0.25rem 0.75rem;border-radius:20px;white-space:nowrap;">
                                    ${project.status.toUpperCase()}
                                </span>
                            </div>
                            <p style="margin:0.5rem 0 0;color:#64748b;font-size:0.9rem;">${escapeHtml(project.category)} • ${escapeHtml(project.department || 'N/A')}</p>
                        </div>
                        
                        <div style="padding:1.5rem;">
                            <div style="margin-bottom:1.25rem;">
                                <label style="display:block;font-weight:600;font-size:0.8rem;color:#64748b;margin-bottom:0.25rem;text-transform:uppercase;">Description</label>
                                <p style="color:#334155;line-height:1.6;font-size:0.95rem;">${escapeHtml(project.description || 'No description provided.')}</p>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.25rem;">
                                <div>
                                    <label style="display:block;font-weight:600;font-size:0.8rem;color:#64748b;margin-bottom:0.25rem;text-transform:uppercase;">Team Lead</label>
                                    <p style="color:#334155;font-weight:500;">${escapeHtml(project.student_name || 'Unknown')}</p>
                                </div>
                                <div>
                                    <label style="display:block;font-weight:600;font-size:0.8rem;color:#64748b;margin-bottom:0.25rem;text-transform:uppercase;">Team Name</label>
                                    <p style="color:#334155;font-weight:500;">${escapeHtml(project.team_name || 'N/A')}</p>
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.25rem;">
                                <div>
                                     <label style="display:block;font-weight:600;font-size:0.8rem;color:#64748b;margin-bottom:0.25rem;text-transform:uppercase;">GitHub Repository</label>
                                     ${project.github_link ? `<a href="${escapeHtml(project.github_link)}" target="_blank" style="color:#2563eb;text-decoration:none;font-weight:500;"><i class="ri-github-line"></i> ${escapeHtml(project.github_link)}</a>` : '<span style="color:#94a3b8;">No link provided</span>'}
                                </div>
                                <div>
                                     <label style="display:block;font-weight:600;font-size:0.8rem;color:#64748b;margin-bottom:0.25rem;text-transform:uppercase;">Documentation (PDF)</label>
                                     ${project.file_path ? `<a href="javascript:void(0)" onclick="Swal.close(); setTimeout(() => viewPdf('${escapeHtml(project.file_path)}', '${escapeHtml(project.title)}'), 300);" style="color:#2563eb;text-decoration:none;font-weight:500;cursor:pointer;"><i class="ri-file-pdf-2-line"></i> View PDF</a>` : '<span style="color:#94a3b8;">No file uploaded</span>'}
                                </div>
                            </div>

                            <div style="margin-bottom:1.25rem;">
                                <label style="display:block;font-weight:600;font-size:0.8rem;color:#64748b;margin-bottom:0.5rem;text-transform:uppercase;">Team Members</label>
                                <div>${membersList}</div>
                            </div>

                            ${project.review_comments ? `
                                <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:1rem;margin-top:1rem;">
                                    <strong style="color:#92400e;display:block;margin-bottom:0.25rem;font-size:0.9rem;"><i class="ri-chat-1-line"></i> Reviewer Comments</strong>
                                    <p style="color:#b45309;margin:0;font-size:0.9rem;">${escapeHtml(project.review_comments)}</p>
                                </div>
                            ` : ''}

                            ${actionButtons}
                        </div>
                    </div>
                `
            });
        }

        function submitReview(projectId, decision) {
            Swal.fire({
                title: decision === 'approved' ? 'Approve Project?' : (decision === 'rejected' ? 'Reject Project?' : 'Revert to Pending?'),
                html: `
                    <textarea id="swal-comment" class="swal2-textarea" placeholder="Add comments (optional)..." style="margin:0;"></textarea>
                `,
                icon: decision === 'approved' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: decision === 'approved' ? '#22c55e' : (decision === 'rejected' ? '#ef4444' : '#f59e0b'),
                confirmButtonText: 'Yes, ' + decision,
                preConfirm: () => {
                    return document.getElementById('swal-comment').value;
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
                        <input type="hidden" name="comments" value="${escapeHtml(result.value)}">
                        <input type="hidden" name="redirect" value="departmentProjects.php">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function viewPdf(filePath, title) {
            const modal = document.getElementById('pdfModal');
            const pdfTitle = document.getElementById('pdfModalTitle');
            const pdfEmbed = document.getElementById('pdfModalEmbed');
            const pdfFallback = document.getElementById('pdfModalFallback');
            const pdfFallbackLink = document.getElementById('pdfModalFallbackLink');

            pdfTitle.textContent = title || 'Project Documentation';
            pdfEmbed.src = filePath;
            pdfFallbackLink.href = filePath;

            // Show fallback after a timeout in case embed fails
            pdfFallback.style.display = 'none';
            pdfEmbed.style.display = 'block';
            pdfEmbed.onerror = function() {
                pdfEmbed.style.display = 'none';
                pdfFallback.style.display = 'flex';
            };

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePdfModal() {
            const modal = document.getElementById('pdfModal');
            const pdfEmbed = document.getElementById('pdfModalEmbed');
            pdfEmbed.src = '';
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

    <!-- PDF Viewer Modal -->
    <div id="pdfModal" class="pdf-modal-overlay" onclick="if(event.target===this) closePdfModal();">
        <div class="pdf-modal-container">
            <div class="pdf-modal-header">
                <h3 id="pdfModalTitle">Project Documentation</h3>
                <button class="pdf-modal-close" onclick="closePdfModal()" title="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="pdf-modal-body">
                <embed id="pdfModalEmbed" src="" type="application/pdf" class="pdf-modal-embed">
                <div id="pdfModalFallback" class="pdf-modal-fallback" style="display:none;">
                    <i class="ri-file-pdf-2-line"></i>
                    <p>Unable to display PDF in the browser.</p>
                    <a id="pdfModalFallbackLink" href="#" target="_blank" class="btn-primary">
                        <i class="ri-external-link-line"></i> Open PDF in New Tab
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .pdf-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }
        .pdf-modal-overlay.active {
            display: flex;
        }
        .pdf-modal-container {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 950px;
            height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .pdf-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .pdf-modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pdf-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            padding: 0.25rem;
            border-radius: 6px;
            line-height: 1;
            transition: all 0.2s;
        }
        .pdf-modal-close:hover {
            background: #fee2e2;
            color: #ef4444;
        }
        .pdf-modal-body {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        .pdf-modal-embed {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }
        .pdf-modal-fallback {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 1rem;
            color: #64748b;
        }
        .pdf-modal-fallback i {
            font-size: 4rem;
            color: #ef4444;
        }
        .pdf-modal-fallback p {
            margin: 0;
            font-size: 1rem;
        }
    </style>

    <?php include 'includes/bot.php'; ?>
</body>

</html>