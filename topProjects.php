<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDept = $_SESSION['department'] ?? '';
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';

$categoryFilter = $_GET['category'] ?? '';

// Build query for top scored approved projects
$sql = "SELECT p.*, u.name as student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'approved' AND p.score > 0";
$params = [];

if (strtolower($role) === 'departmentcoordinator') {
    $sql .= " AND p.department = ?";
    $params[] = $userDept;
}

if (!empty($categoryFilter)) {
    $sql .= " AND p.category = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY p.score DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$topResult = mysqli_stmt_get_result($stmt);
$topProjects = [];
while ($row = mysqli_fetch_assoc($topResult)) { $topProjects[] = $row; }
mysqli_stmt_close($stmt);

// Get distinct categories for filter
$catSql = "SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != ''";
if (strtolower($role) === 'departmentcoordinator') {
    $catSql .= " AND department = ?";
    $catStmt = mysqli_prepare($conn, $catSql);
    mysqli_stmt_bind_param($catStmt, 's', $userDept);
    mysqli_stmt_execute($catStmt);
    $catRes = mysqli_stmt_get_result($catStmt);
} else {
    $catRes = mysqli_query($conn, $catSql);
}
$categories = [];
while ($row = mysqli_fetch_assoc($catRes)) { $categories[] = $row['category']; }
if (isset($catStmt)) { mysqli_stmt_close($catStmt); }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Projects | SPARK'26</title>
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
                    <h1>Top Projects</h1>
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
                    <h2>Best Projects <?php echo strtolower($role) === 'departmentcoordinator' ? 'in Your Department' : 'Overall'; ?></h2>
                    <div class="filter-controls">
                        <form method="GET" action="topProjects.php">
                            <select class="filter-select" name="category" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($categoryFilter === $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <?php if (empty($topProjects)): ?>
                <div class="top-projects-grid">
                    <div class="empty-state">
                        <i class="ri-trophy-line"></i>
                        <h3>No Top Projects Yet</h3>
                        <p>Top projects will appear here once projects are reviewed and scored.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="leaderboard-section">
                    <h3>Project Leaderboard</h3>
                    <div class="leaderboard">
                        <?php foreach ($topProjects as $index => $project):
                            $rank = $index + 1;
                            $rankClass = '';
                            if ($rank === 1) $rankClass = 'gold';
                            elseif ($rank === 2) $rankClass = 'silver';
                            elseif ($rank === 3) $rankClass = 'bronze';
                        ?>
                        <div class="leaderboard-item <?php echo $rankClass; ?>">
                            <span class="rank"><?php echo $rank; ?></span>
                            <div class="project-info">
                                <h4><?php echo htmlspecialchars($project['title']); ?></h4>
                                <p><?php echo htmlspecialchars($project['student_name'] ?? 'Unknown'); ?></p>
                            </div>
                            <div class="score">
                                <i class="ri-star-fill"></i>
                                <span><?php echo number_format($project['score'], 1); ?></span>
                            </div>
                            <span class="category-badge" style="margin-left:10px;font-size:0.8rem;background:#e9ecef;padding:3px 8px;border-radius:12px;">
                                <?php echo htmlspecialchars($project['category'] ?? 'N/A'); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
