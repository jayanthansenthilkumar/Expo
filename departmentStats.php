<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDept = $_SESSION['department'] ?? '';

// Count total projects in department
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department = ?");
mysqli_stmt_bind_param($stmt, 's', $userDept);
mysqli_stmt_execute($stmt);
$totalProjects = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Count students in department
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users WHERE department = ? AND role = 'student'");
mysqli_stmt_bind_param($stmt, 's', $userDept);
mysqli_stmt_execute($stmt);
$totalStudents = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Count pending projects in department
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, 's', $userDept);
mysqli_stmt_execute($stmt);
$pendingProjects = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Count teams in department
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM teams WHERE department = ?");
mysqli_stmt_bind_param($stmt, 's', $userDept);
mysqli_stmt_execute($stmt);
$totalTeams = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Category breakdown
$stmt = mysqli_prepare($conn, "SELECT category, COUNT(*) as cnt FROM projects WHERE department = ? GROUP BY category");
mysqli_stmt_bind_param($stmt, 's', $userDept);
mysqli_stmt_execute($stmt);
$catResult = mysqli_stmt_get_result($stmt);
$categoryBreakdown = [];
while ($row = mysqli_fetch_assoc($catResult)) { $categoryBreakdown[] = $row; }
mysqli_stmt_close($stmt);

// Status breakdown
$stmt = mysqli_prepare($conn, "SELECT status, COUNT(*) as cnt FROM projects WHERE department = ? GROUP BY status");
mysqli_stmt_bind_param($stmt, 's', $userDept);
mysqli_stmt_execute($stmt);
$statResult = mysqli_stmt_get_result($stmt);
$statusBreakdown = [];
while ($row = mysqli_fetch_assoc($statResult)) { $statusBreakdown[] = $row; }
mysqli_stmt_close($stmt);

// Build status counts
$approvedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;
foreach ($statusBreakdown as $row) {
    if ($row['status'] === 'approved') $approvedCount = $row['cnt'];
    elseif ($row['status'] === 'pending') $pendingCount = $row['cnt'];
    elseif ($row['status'] === 'rejected') $rejectedCount = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Stats | SPARK'26</title>
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
                    <h1>Department Statistics</h1>
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
                            <h3><?php echo $totalProjects; ?></h3>
                            <p>Total Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-user-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalStudents; ?></h3>
                            <p>Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pendingProjects; ?></h3>
                            <p>Pending Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-team-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalTeams; ?></h3>
                            <p>Teams</p>
                        </div>
                    </div>
                </div>

                <div class="analytics-charts">
                    <div class="chart-card">
                        <h3>Projects by Category</h3>
                        <div class="chart-data">
                            <?php if (empty($categoryBreakdown)): ?>
                                <div class="chart-placeholder">
                                    <i class="ri-pie-chart-line"></i>
                                    <p>No categories yet</p>
                                </div>
                            <?php else: ?>
                                <?php
                                $maxCat = max(array_column($categoryBreakdown, 'cnt'));
                                foreach ($categoryBreakdown as $cat): 
                                    $pct = $maxCat > 0 ? round(($cat['cnt'] / $maxCat) * 100) : 0;
                                ?>
                                <div style="margin-bottom:8px;">
                                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                        <span style="font-size:0.9rem;"><?php echo htmlspecialchars($cat['category'] ?: 'Uncategorized'); ?></span>
                                        <span style="font-weight:600;"><?php echo $cat['cnt']; ?></span>
                                    </div>
                                    <div style="background:#e9ecef;border-radius:4px;height:8px;">
                                        <div style="background:var(--primary-color, #4361ee);height:100%;border-radius:4px;width:<?php echo $pct; ?>%;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Submissions This Month</h3>
                        <div class="chart-data" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;">
                            <i class="ri-line-chart-line" style="font-size:2rem;color:var(--primary-color, #4361ee);margin-bottom:8px;"></i>
                            <p style="font-size:2rem;font-weight:700;margin:0;"><?php echo $totalProjects; ?></p>
                            <p style="color:#6c757d;margin:4px 0 0;">Total submissions in department</p>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Approval Rate</h3>
                        <div class="chart-data" style="padding:15px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #e9ecef;">
                                <span style="display:flex;align-items:center;gap:8px;"><span style="width:12px;height:12px;border-radius:50%;background:#2ecc71;display:inline-block;"></span> Approved</span>
                                <strong><?php echo $approvedCount; ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #e9ecef;">
                                <span style="display:flex;align-items:center;gap:8px;"><span style="width:12px;height:12px;border-radius:50%;background:#f39c12;display:inline-block;"></span> Pending</span>
                                <strong><?php echo $pendingCount; ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;">
                                <span style="display:flex;align-items:center;gap:8px;"><span style="width:12px;height:12px;border-radius:50%;background:#e74c3c;display:inline-block;"></span> Rejected</span>
                                <strong><?php echo $rejectedCount; ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Student Participation</h3>
                        <div class="chart-data" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;">
                            <i class="ri-bar-chart-line" style="font-size:2rem;color:#8e44ad;margin-bottom:8px;"></i>
                            <p style="font-size:2rem;font-weight:700;margin:0;"><?php echo $totalStudents; ?></p>
                            <p style="color:#6c757d;margin:4px 0 0;">Students across <?php echo $totalTeams; ?> teams</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
