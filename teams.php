<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDept = $_SESSION['department'] ?? '';
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';

// Multi-department support (AIDS & AIML share one coordinator)
$deptFilter = buildDeptFilter($userDept);

// Query teams with project and leader info
$sql = "SELECT t.*, p.title as project_title, p.status as project_status, u.name as leader_name,
        (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as member_count
        FROM teams t
        LEFT JOIN projects p ON p.team_id = t.id
        LEFT JOIN users u ON t.leader_id = u.id";

if (strtolower($role) === 'departmentcoordinator') {
    $sql .= " WHERE t.department IN (" . $deptFilter['placeholders'] . ")";
    $sql .= " ORDER BY t.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $deptFilter['types'], ...$deptFilter['values']);
    mysqli_stmt_execute($stmt);
    $teamRes = mysqli_stmt_get_result($stmt);
} else {
    $sql .= " ORDER BY t.created_at DESC";
    $teamRes = mysqli_query($conn, $sql);
}
$teams = [];
while ($row = mysqli_fetch_assoc($teamRes)) {
    $teams[] = $row;
}
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}

// Flash messages
$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// For admin, fetch team members (name + reg_no) grouped by team_id
$isAdmin = (strtolower($role) === 'admin');
$teamMembersMap = [];
if ($isAdmin && !empty($teams)) {
    $memberRes = mysqli_query($conn, "SELECT tm.team_id, u.name, u.reg_no FROM team_members tm JOIN users u ON tm.user_id = u.id ORDER BY tm.team_id, u.name");
    while ($mRow = mysqli_fetch_assoc($memberRes)) {
        $teamMembersMap[(int)$mRow['team_id']][] = $mRow;
    }
}

// For admin/studentaffairs, also fetch all projects
$isAdminOrAffairs = in_array(strtolower($role), ['admin', 'studentaffairs']);
$allProjects = [];
if ($isAdminOrAffairs) {
    $projSql = "SELECT p.*, u.name AS student_name, t.team_name AS team_name
                FROM projects p
                LEFT JOIN users u ON p.student_id = u.id
                LEFT JOIN teams t ON p.team_id = t.id
                ORDER BY p.created_at DESC";
    $projRes = mysqli_query($conn, $projSql);
    while ($row = mysqli_fetch_assoc($projRes)) {
        $allProjects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams | SPARK'26</title>
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
            $pageTitle = 'Teams';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <?php if ($isAdminOrAffairs): ?>
                <!-- Admin/Student Affairs: Tabbed view with Teams + Projects -->
                <div class="content-header">
                    <h2>Teams & Projects</h2>
                </div>

                <div class="teams-projects-tabs">
                    <button class="tp-tab active" data-tab="teams-tab" onclick="switchTPTab('teams-tab')">
                        <i class="ri-team-line"></i> Teams <span class="tp-tab-badge"><?php echo count($teams); ?></span>
                    </button>
                    <button class="tp-tab" data-tab="projects-tab" onclick="switchTPTab('projects-tab')">
                        <i class="ri-folder-line"></i> Projects <span class="tp-tab-badge"><?php echo count($allProjects); ?></span>
                    </button>
                </div>

                <!-- Teams Tab -->
                <div class="tp-tab-content active" id="teams-tab">
                <?php if (empty($teams)): ?>
                    <div class="empty-state">
                        <i class="ri-team-line"></i>
                        <h3>No Teams Yet</h3>
                        <p>Teams will appear here once students create teams.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-export-bar">
                            <span class="export-label">Export</span>
                            <div class="export-btn-group" data-table="teamsTable" data-filename="Teams">
                                <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                                <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                            </div>
                        </div>
                        <table class="data-table" id="teamsTable">
                            <thead>
                                <tr>
                                    <th>Team Name</th>
                                    <th>Department</th>
                                    <th>Team Leader</th>
                                    <th>Members</th>
                                    <?php if ($isAdmin): ?><th>Team Members</th><?php endif; ?>
                                    <th>Project</th>
                                    <th>Project Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teams as $team):
                                    $pStatus = $team['project_status'] ?? '';
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                        <td><?php echo htmlspecialchars($team['department'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($team['leader_name'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo (int) $team['member_count']; ?></td>
                                        <?php if ($isAdmin): ?>
                                        <td>
                                            <?php
                                            $members = $teamMembersMap[(int)$team['id']] ?? [];
                                            if (!empty($members)) {
                                                echo '<ul style="margin:0;padding-left:1.2em;list-style:disc;">';
                                                foreach ($members as $m) {
                                                    echo '<li>' . htmlspecialchars($m['name']) . ' <span style="color:#64748b;font-size:0.85em;">(' . htmlspecialchars($m['reg_no'] ?? 'N/A') . ')</span></li>';
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '<span style="color:#94a3b8;">No members</span>';
                                            }
                                            ?>
                                        </td>
                                        <?php endif; ?>
                                        <td><?php echo !empty($team['project_title']) ? htmlspecialchars($team['project_title']) : '<span style="color:#94a3b8;">No project</span>'; ?></td>
                                        <td>
                                            <?php if (!empty($pStatus)): ?>
                                                <span class="status-badge status-<?php echo htmlspecialchars($pStatus); ?>"><?php echo ucfirst(htmlspecialchars($pStatus)); ?></span>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($team['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                </div>

                <!-- Projects Tab -->
                <div class="tp-tab-content" id="projects-tab">
                <?php if (empty($allProjects)): ?>
                    <div class="empty-state">
                        <i class="ri-folder-open-line"></i>
                        <h3>No Projects Yet</h3>
                        <p>Projects will appear here once students submit them.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-export-bar">
                            <span class="export-label">Export</span>
                            <div class="export-btn-group" data-table="allProjectsTable" data-filename="All_Projects">
                                <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                                <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                            </div>
                        </div>
                        <table class="data-table" id="allProjectsTable">
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
                                <?php foreach ($allProjects as $project): ?>
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
                                                <a href="<?php echo htmlspecialchars($project['github_link']); ?>" target="_blank" title="<?php echo htmlspecialchars($project['github_link']); ?>" style="color:#D97706;"><i class="ri-github-line"></i> Repo</a>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($project['file_path'])): ?>
                                                <a href="javascript:void(0)" onclick="viewPdf('<?php echo htmlspecialchars($project['file_path'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($project['title'], ENT_QUOTES); ?>')" title="View PDF" style="color:#D97706;cursor:pointer;"><i class="ri-file-pdf-2-line"></i> PDF</a>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="status-badge status-<?php echo htmlspecialchars($project['status']); ?>"><?php echo ucfirst(htmlspecialchars($project['status'])); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                        <td>
                                            <button class="btn-view btn-sm" onclick="viewProject(<?php echo $project['id']; ?>)">
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

                <?php else: ?>
                <!-- Department Coordinator: Teams table only -->
                <div class="content-header">
                    <h2>Project Teams</h2>
                </div>

                <?php if (empty($teams)): ?>
                    <div class="empty-state">
                        <i class="ri-team-line"></i>
                        <h3>No Teams Yet</h3>
                        <p>Teams will appear here once students create teams.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-export-bar">
                            <span class="export-label">Export</span>
                            <div class="export-btn-group" data-table="teamsTable" data-filename="Teams">
                                <button class="export-btn export-pdf-btn" title="Download as PDF"><i class="ri-file-pdf-2-line"></i> <span>PDF</span></button>
                                <button class="export-btn export-excel-btn" title="Download as Excel"><i class="ri-file-excel-2-line"></i> <span>Excel</span></button>
                            </div>
                        </div>
                        <table class="data-table" id="teamsTable">
                            <thead>
                                <tr>
                                    <th>Team Name</th>
                                    <th>Department</th>
                                    <th>Team Leader</th>
                                    <th>Members</th>
                                    <th>Project</th>
                                    <th>Project Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teams as $team):
                                    $pStatus = $team['project_status'] ?? '';
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                        <td><?php echo htmlspecialchars($team['department'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($team['leader_name'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo (int) $team['member_count']; ?></td>
                                        <td><?php echo !empty($team['project_title']) ? htmlspecialchars($team['project_title']) : '<span style="color:#94a3b8;">No project</span>'; ?></td>
                                        <td>
                                            <?php if (!empty($pStatus)): ?>
                                                <span class="status-badge status-<?php echo htmlspecialchars($pStatus); ?>"><?php echo ucfirst(htmlspecialchars($pStatus)); ?></span>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($team['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/tableExport.js?v=2"></script>
    <script>
        <?php if ($successMsg): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#D97706', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#D97706' });
        <?php endif; ?>

        // Tab switching for admin/studentaffairs
        function switchTPTab(tab) {
            document.querySelectorAll('.tp-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tp-tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector(`.tp-tab[data-tab="${tab}"]`).classList.add('active');
            document.getElementById(tab).classList.add('active');
        }

        <?php if ($isAdminOrAffairs): ?>
        const projectsData = <?php echo json_encode($allProjects); ?>;

        function viewProject(id) {
            const project = projectsData.find(p => p.id == id);
            if (!project) return;

            const isPending = project.status === 'pending';

            let actionButtons = '';
            if (!isPending) {
                actionButtons = `
                    <div style="margin-top:1.5rem;border-top:1px solid #eee;padding-top:1rem;">
                        <button onclick="submitReview(${project.id}, 'pending')" class="btn-primary" style="background:#f59e0b;width:100%;border:none;">
                            <i class="ri-arrow-go-back-line"></i> Revert to Pending
                        </button>
                    </div>
                `;
            }

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
                            <p style="margin:0.5rem 0 0;color:#64748b;font-size:0.9rem;">${escapeHtml(project.category)} &bull; ${escapeHtml(project.department || 'N/A')}</p>
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
                                    ${project.github_link ? `<a href="${escapeHtml(project.github_link)}" target="_blank" style="color:#D97706;text-decoration:none;font-weight:500;"><i class="ri-github-line"></i> ${escapeHtml(project.github_link)}</a>` : '<span style="color:#94a3b8;">No link provided</span>'}
                                </div>
                                <div>
                                    <label style="display:block;font-weight:600;font-size:0.8rem;color:#64748b;margin-bottom:0.25rem;text-transform:uppercase;">Documentation (PDF)</label>
                                    ${project.file_path ? `<a href="javascript:void(0)" onclick="Swal.close(); setTimeout(() => viewPdf('${escapeHtml(project.file_path)}', '${escapeHtml(project.title)}'), 300);" style="color:#D97706;text-decoration:none;font-weight:500;cursor:pointer;"><i class="ri-file-pdf-2-line"></i> View PDF</a>` : '<span style="color:#94a3b8;">No file uploaded</span>'}
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
                html: `<textarea id="swal-comment" class="swal2-textarea" placeholder="Add comments (optional)..." style="margin:0;"></textarea>`,
                icon: decision === 'approved' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: decision === 'approved' ? '#22c55e' : (decision === 'rejected' ? '#ef4444' : '#f59e0b'),
                confirmButtonText: 'Yes, ' + decision,
                preConfirm: () => document.getElementById('swal-comment').value
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
                        <input type="hidden" name="redirect" value="teams.php">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function viewPdf(filePath, title) {
            const modal = document.getElementById('pdfModal');
            document.getElementById('pdfModalTitle').textContent = title || 'Project Documentation';
            const pdfEmbed = document.getElementById('pdfModalEmbed');
            const pdfFallback = document.getElementById('pdfModalFallback');
            document.getElementById('pdfModalFallbackLink').href = filePath;

            pdfFallback.style.display = 'none';
            pdfEmbed.style.display = 'block';
            pdfEmbed.src = filePath;
            pdfEmbed.onerror = function() {
                pdfEmbed.style.display = 'none';
                pdfFallback.style.display = 'flex';
            };

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePdfModal() {
            const modal = document.getElementById('pdfModal');
            document.getElementById('pdfModalEmbed').src = '';
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
        <?php endif; ?>

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

    <?php if ($isAdminOrAffairs): ?>
    <!-- PDF Viewer Modal -->
    <div id="pdfModal" class="pdf-modal-overlay" onclick="if(event.target===this) closePdfModal();">
        <div class="pdf-modal-container">
            <div class="pdf-modal-header">
                <h3 id="pdfModalTitle">Project Documentation</h3>
                <button class="pdf-modal-close" onclick="closePdfModal()" title="Close"><i class="ri-close-line"></i></button>
            </div>
            <div class="pdf-modal-body">
                <embed id="pdfModalEmbed" src="" type="application/pdf" class="pdf-modal-embed">
                <div id="pdfModalFallback" class="pdf-modal-fallback" style="display:none;">
                    <i class="ri-file-pdf-2-line"></i>
                    <p>Unable to display PDF in the browser.</p>
                    <a id="pdfModalFallbackLink" href="#" target="_blank" class="btn-primary"><i class="ri-external-link-line"></i> Open PDF in New Tab</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <style>
        .teams-projects-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .tp-tab {
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
        .tp-tab:hover {
            color: #1e293b;
            background: #f8fafc;
            border-radius: 8px 8px 0 0;
        }
        .tp-tab.active {
            color: #D97706;
            border-bottom-color: #D97706;
            font-weight: 600;
        }
        .tp-tab-badge {
            font-size: 0.75rem;
            padding: 0.15rem 0.55rem;
            border-radius: 10px;
            font-weight: 600;
            background: #e0e7ff;
            color: #3730a3;
        }
        .tp-tab-content {
            display: none;
        }
        .tp-tab-content.active {
            display: block;
        }
        .pdf-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }
        .pdf-modal-overlay.active { display: flex; }
        .pdf-modal-container {
            background: #fff;
            border-radius: 12px;
            width: 100%; max-width: 950px; height: 90vh;
            display: flex; flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .pdf-modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.5rem;
            background: #f8fafc; border-bottom: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .pdf-modal-header h3 { margin: 0; font-size: 1.1rem; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pdf-modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; padding: 0.25rem; border-radius: 6px; line-height: 1; transition: all 0.2s; }
        .pdf-modal-close:hover { background: #fee2e2; color: #ef4444; }
        .pdf-modal-body { flex: 1; overflow: hidden; position: relative; }
        .pdf-modal-embed { width: 100%; height: 100%; border: none; display: block; }
        .pdf-modal-fallback { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; gap: 1rem; color: #64748b; }
        .pdf-modal-fallback i { font-size: 4rem; color: #ef4444; }
        .pdf-modal-fallback p { margin: 0; font-size: 1rem; }
    </style>

    <?php include 'includes/bot.php'; ?>
</body>

</html>