<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDepartment = $_SESSION['department'] ?? '';

// Filter parameters
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$where = "WHERE p.department = ?";
$params = [$userDepartment];
$types = "s";

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
                    <h1>Department Projects</h1>
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
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>">
                        <?php echo htmlspecialchars($flashMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="content-header">
                    <h2>Projects in Your Department</h2>
                    <form method="GET" class="filter-controls">
                        <select name="category" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <option value="Web Development" <?php echo $categoryFilter === 'Web Development' ? 'selected' : ''; ?>>Web Development</option>
                            <option value="Mobile Application" <?php echo $categoryFilter === 'Mobile Application' ? 'selected' : ''; ?>>Mobile Application</option>
                            <option value="AI/Machine Learning" <?php echo $categoryFilter === 'AI/Machine Learning' ? 'selected' : ''; ?>>AI/Machine Learning</option>
                            <option value="IoT" <?php echo $categoryFilter === 'IoT' ? 'selected' : ''; ?>>IoT</option>
                            <option value="Other" <?php echo $categoryFilter === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                                    <td><span class="status-badge status-<?php echo htmlspecialchars($project['status']); ?>"><?php echo ucfirst(htmlspecialchars($project['status'])); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                    <td>
                                        <?php if ($project['status'] === 'pending'): ?>
                                            <a href="reviewApprove.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                        <?php else: ?>
                                            <a href="reviewApprove.php?id=<?php echo $project['id']; ?>" class="btn btn-sm">View</a>
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
                        <a href="?category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&page=<?php echo $page - 1; ?>" class="btn-pagination">&laquo; Previous</a>
                    <?php else: ?>
                        <button class="btn-pagination" disabled>&laquo; Previous</button>
                    <?php endif; ?>
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&page=<?php echo $page + 1; ?>" class="btn-pagination">Next &raquo;</a>
                    <?php else: ?>
                        <button class="btn-pagination" disabled>Next &raquo;</button>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
