<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');

// Total counts
$totalProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects"))['cnt'];
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
$totalDepartments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT department) as cnt FROM projects WHERE department IS NOT NULL AND department != ''"))['cnt'];
$approvedProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];

// Category breakdown
$catResult = mysqli_query($conn, "SELECT category, COUNT(*) as cnt FROM projects GROUP BY category ORDER BY cnt DESC");
$categories = [];
while ($row = mysqli_fetch_assoc($catResult)) {
    $categories[] = $row;
}

// Department breakdown
$deptResult = mysqli_query($conn, "SELECT department, COUNT(*) as cnt FROM projects WHERE department != '' GROUP BY department ORDER BY cnt DESC");
$deptBreakdown = [];
while ($row = mysqli_fetch_assoc($deptResult)) {
    $deptBreakdown[] = $row;
}

// Status breakdown
$statusResult = mysqli_query($conn, "SELECT status, COUNT(*) as cnt FROM projects GROUP BY status");
$statusBreakdown = [];
while ($row = mysqli_fetch_assoc($statusResult)) {
    $statusBreakdown[] = $row;
}

// Monthly submissions
$monthlyResult = mysqli_query($conn, "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM projects GROUP BY month ORDER BY month DESC LIMIT 6");
$monthlySubmissions = [];
while ($row = mysqli_fetch_assoc($monthlyResult)) {
    $monthlySubmissions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | SPARK'26</title>
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
                    <h1>Analytics</h1>
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
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="ri-folder-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo (int)$totalProjects; ?></h3>
                            <p>Total Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-user-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo (int)$totalUsers; ?></h3>
                            <p>Registered Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-building-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo (int)$totalDepartments; ?></h3>
                            <p>Departments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo (int)$approvedProjects; ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                </div>

                <div class="analytics-charts">
                    <div class="chart-card">
                        <h3>Projects by Category</h3>
                        <div class="chart-content">
                            <?php if (empty($categories)): ?>
                                <p style="color:var(--text-secondary);text-align:center;padding:1rem;">No data yet</p>
                            <?php else: ?>
                                <?php
                                $maxCat = max(array_column($categories, 'cnt'));
                                foreach ($categories as $cat):
                                    $pct = $maxCat > 0 ? round(($cat['cnt'] / $maxCat) * 100) : 0;
                                    $pctOfTotal = $totalProjects > 0 ? round(($cat['cnt'] / $totalProjects) * 100) : 0;
                                ?>
                                <div style="margin-bottom:0.75rem;">
                                    <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem;font-size:0.9rem;">
                                        <span><?php echo htmlspecialchars($cat['category'] ?: 'Uncategorized'); ?></span>
                                        <span><?php echo (int)$cat['cnt']; ?> (<?php echo $pctOfTotal; ?>%)</span>
                                    </div>
                                    <div style="background:var(--bg-secondary, #eee);border-radius:4px;height:8px;overflow:hidden;">
                                        <div style="background:var(--primary, #4f46e5);height:100%;width:<?php echo $pct; ?>%;border-radius:4px;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Submissions Over Time</h3>
                        <div class="chart-content">
                            <?php if (empty($monthlySubmissions)): ?>
                                <p style="color:var(--text-secondary);text-align:center;padding:1rem;">No data yet</p>
                            <?php else: ?>
                                <?php foreach (array_reverse($monthlySubmissions) as $m): ?>
                                <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--border-color, #eee);font-size:0.9rem;">
                                    <span><?php echo htmlspecialchars($m['month']); ?></span>
                                    <span style="font-weight:600;"><?php echo (int)$m['cnt']; ?> project<?php echo $m['cnt'] != 1 ? 's' : ''; ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Projects by Department</h3>
                        <div class="chart-content">
                            <?php if (empty($deptBreakdown)): ?>
                                <p style="color:var(--text-secondary);text-align:center;padding:1rem;">No data yet</p>
                            <?php else: ?>
                                <?php
                                $maxDept = max(array_column($deptBreakdown, 'cnt'));
                                foreach ($deptBreakdown as $dept):
                                    $pct = $maxDept > 0 ? round(($dept['cnt'] / $maxDept) * 100) : 0;
                                ?>
                                <div style="margin-bottom:0.75rem;">
                                    <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem;font-size:0.9rem;">
                                        <span><?php echo htmlspecialchars($dept['department']); ?></span>
                                        <span><?php echo (int)$dept['cnt']; ?></span>
                                    </div>
                                    <div style="background:var(--bg-secondary, #eee);border-radius:4px;height:8px;overflow:hidden;">
                                        <div style="background:var(--primary, #4f46e5);height:100%;width:<?php echo $pct; ?>%;border-radius:4px;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Approval Status</h3>
                        <div class="chart-content">
                            <?php if (empty($statusBreakdown)): ?>
                                <p style="color:var(--text-secondary);text-align:center;padding:1rem;">No data yet</p>
                            <?php else: ?>
                                <?php foreach ($statusBreakdown as $st):
                                    $pctOfTotal = $totalProjects > 0 ? round(($st['cnt'] / $totalProjects) * 100) : 0;
                                    $statusColors = ['approved' => '#22c55e', 'pending' => '#f59e0b', 'rejected' => '#ef4444', 'under_review' => '#3b82f6'];
                                    $color = $statusColors[strtolower($st['status'])] ?? '#6b7280';
                                ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid var(--border-color, #eee);font-size:0.9rem;">
                                    <span style="display:flex;align-items:center;gap:0.5rem;">
                                        <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $color; ?>;display:inline-block;"></span>
                                        <?php echo htmlspecialchars(ucfirst($st['status'] ?? 'Unknown')); ?>
                                    </span>
                                    <span style="font-weight:600;"><?php echo (int)$st['cnt']; ?> (<?php echo $pctOfTotal; ?>%)</span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
