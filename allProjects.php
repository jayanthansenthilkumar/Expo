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

// Count total
$countQuery = "SELECT COUNT(*) as total FROM projects p $where";
$countStmt = mysqli_prepare($conn, $countQuery);
if ($types) mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$totalRows = mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
mysqli_stmt_close($countStmt);
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch projects
$query = "SELECT p.*, u.name as student_name, u.department as student_dept 
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
                    <h1>All Projects</h1>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Search projects...">
                    </div>
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
                <?php if ($successMsg): ?>
                    <div style="background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;margin-bottom:1rem;"><i class="ri-checkbox-circle-line"></i> <?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div style="background:#fef2f2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;"><i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

                <div class="content-header">
                    <h2>Project Management</h2>
                    <div class="filter-controls">
                        <form method="GET" style="display:flex;gap:0.5rem;">
                            <select class="filter-select" name="category" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <option value="web" <?php if($filterCategory==='web') echo 'selected'; ?>>Web Development</option>
                                <option value="mobile" <?php if($filterCategory==='mobile') echo 'selected'; ?>>Mobile Application</option>
                                <option value="ai" <?php if($filterCategory==='ai') echo 'selected'; ?>>AI/Machine Learning</option>
                                <option value="iot" <?php if($filterCategory==='iot') echo 'selected'; ?>>IoT</option>
                                <option value="blockchain" <?php if($filterCategory==='blockchain') echo 'selected'; ?>>Blockchain</option>
                                <option value="other" <?php if($filterCategory==='other') echo 'selected'; ?>>Other</option>
                            </select>
                            <select class="filter-select" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="pending" <?php if($filterStatus==='pending') echo 'selected'; ?>>Pending</option>
                                <option value="approved" <?php if($filterStatus==='approved') echo 'selected'; ?>>Approved</option>
                                <option value="rejected" <?php if($filterStatus==='rejected') echo 'selected'; ?>>Rejected</option>
                            </select>
                        </form>
                    </div>
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
                                    <td><?php echo htmlspecialchars($p['student_dept'] ?? $p['department'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span style="padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:600;
                                            <?php if($p['status']==='approved') echo 'background:#dcfce7;color:#166534;';
                                            elseif($p['status']==='rejected') echo 'background:#fef2f2;color:#991b1b;';
                                            else echo 'background:#fef3c7;color:#92400e;'; ?>">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                    <td>
                                        <div style="display:flex;gap:0.5rem;">
                                            <?php if ($p['status'] === 'pending' && $_SESSION['role'] !== 'student'): ?>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="review_project">
                                                <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                                <input type="hidden" name="decision" value="approved">
                                                <input type="hidden" name="comments" value="Approved via project list">
                                                <button type="submit" class="btn-icon" title="Approve" style="color:#22c55e;"><i class="ri-checkbox-circle-line"></i></button>
                                            </form>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="review_project">
                                                <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                                <input type="hidden" name="decision" value="rejected">
                                                <input type="hidden" name="comments" value="Rejected via project list">
                                                <button type="submit" class="btn-icon" title="Reject" style="color:#ef4444;"><i class="ri-close-circle-line"></i></button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this project?');">
                                                <input type="hidden" name="action" value="delete_project">
                                                <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="btn-icon" title="Delete" style="color:#ef4444;"><i class="ri-delete-bin-line"></i></button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page-1); ?>&category=<?php echo $filterCategory; ?>&status=<?php echo $filterStatus; ?>" class="btn-pagination" <?php if($page<=1) echo 'disabled'; ?>>&laquo; Previous</a>
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <a href="?page=<?php echo min($totalPages, $page+1); ?>&category=<?php echo $filterCategory; ?>&status=<?php echo $filterStatus; ?>" class="btn-pagination" <?php if($page>=$totalPages) echo 'disabled'; ?>>Next &raquo;</a>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
