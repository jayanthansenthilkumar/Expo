<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Coordinator';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Coordinator');
$userDept = $_SESSION['department'] ?? '';
$isFE = (strtoupper($userDept) === 'FE');

// Multi-department support (AIDS & AIML share one coordinator)
$deptFilter = buildDeptFilter($userDept);
$dp = $deptFilter['placeholders'];
$dt = $deptFilter['types'];
$dv = $deptFilter['values'];

// Count total projects in department
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp)");
mysqli_stmt_bind_param($stmt, $dt, ...$dv);
mysqli_stmt_execute($stmt);
$totalProjects = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Count students in department (FE: all first-year non-MBA/MCA)
if ($isFE) {
    $feFilter = buildFEStudentFilter();
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users u WHERE " . $feFilter['where'] . " AND u.role = 'student'");
    mysqli_stmt_bind_param($stmt, $feFilter['types'], ...$feFilter['values']);
} elseif (in_array(strtoupper($userDept), ['MBA', 'MCA'])) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users WHERE department IN ($dp) AND role = 'student'");
    mysqli_stmt_bind_param($stmt, $dt, ...$dv);
} else {
    $excl = buildExcludeFirstYearFilter();
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users u WHERE u.department IN ($dp) AND u.role = 'student' AND " . $excl['where']);
    $allTypes = $dt . $excl['types'];
    $allValues = array_merge($dv, $excl['values']);
    mysqli_stmt_bind_param($stmt, $allTypes, ...$allValues);
}
mysqli_stmt_execute($stmt);
$totalStudents = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Count pending projects in department
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp) AND status = 'pending'");
mysqli_stmt_bind_param($stmt, $dt, ...$dv);
mysqli_stmt_execute($stmt);
$pendingProjects = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Count teams in department
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM teams WHERE department IN ($dp)");
mysqli_stmt_bind_param($stmt, $dt, ...$dv);
mysqli_stmt_execute($stmt);
$totalTeams = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
mysqli_stmt_close($stmt);

// Category breakdown
$stmt = mysqli_prepare($conn, "SELECT category, COUNT(*) as cnt FROM projects WHERE department IN ($dp) GROUP BY category");
mysqli_stmt_bind_param($stmt, $dt, ...$dv);
mysqli_stmt_execute($stmt);
$catResult = mysqli_stmt_get_result($stmt);
$categoryBreakdown = [];
while ($row = mysqli_fetch_assoc($catResult)) {
    $categoryBreakdown[] = $row;
}
mysqli_stmt_close($stmt);

// Status breakdown
$stmt = mysqli_prepare($conn, "SELECT status, COUNT(*) as cnt FROM projects WHERE department IN ($dp) GROUP BY status");
mysqli_stmt_bind_param($stmt, $dt, ...$dv);
mysqli_stmt_execute($stmt);
$statResult = mysqli_stmt_get_result($stmt);
$statusBreakdown = [];
while ($row = mysqli_fetch_assoc($statResult)) {
    $statusBreakdown[] = $row;
}
mysqli_stmt_close($stmt);

// Build status counts
$approvedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;
foreach ($statusBreakdown as $row) {
    if ($row['status'] === 'approved')
        $approvedCount = $row['cnt'];
    elseif ($row['status'] === 'pending')
        $pendingCount = $row['cnt'];
    elseif ($row['status'] === 'rejected')
        $rejectedCount = $row['cnt'];
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
            <?php
            $pageTitle = 'Department Statistics';
            include 'includes/header.php';
            ?>

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
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Submissions Overview</h3>
                        <div
                            style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:300px;">
                            <i class="ri-line-chart-line"
                                style="font-size:3rem;color:var(--primary);margin-bottom:1rem;"></i>
                            <span
                                style="font-size:3.5rem;font-weight:800;color:var(--text-main);line-height:1;"><?php echo $totalProjects; ?></span>
                            <span style="color:var(--text-muted);font-size:1rem;margin-top:0.5rem;">Total
                                Submissions</span>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Approval Distribution</h3>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Student Engagement</h3>
                        <div
                            style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:300px;">
                            <i class="ri-group-line" style="font-size:3rem;color:#F59E0B;margin-bottom:1rem;"></i>
                            <span
                                style="font-size:3.5rem;font-weight:800;color:var(--text-main);line-height:1;"><?php echo $totalStudents; ?></span>
                            <span style="color:var(--text-muted);font-size:1rem;margin-top:0.5rem;">Active
                                Students</span>
                            <span
                                style="background:#f3f4f6;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.8rem;color:#6b7280;margin-top:1rem;">
                                Across <?php echo $totalTeams; ?> Teams
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare Data
        const categoryData = <?php echo json_encode($categoryBreakdown); ?>;
        const statusData = <?php echo json_encode($statusBreakdown); ?>;

        // Process Category Data
        const catLabels = categoryData.map(c => c.category ? c.category.charAt(0).toUpperCase() + c.category.slice(1) : 'Uncategorized');
        const catCounts = categoryData.map(c => c.cnt);

        // Process Status Data
        const statusMap = { 'approved': 0, 'pending': 0, 'rejected': 0 };
        statusData.forEach(s => { if (statusMap.hasOwnProperty(s.status)) statusMap[s.status] = s.cnt; });

        // Category Chart
        const ctxCat = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCat, {
            type: 'bar', // or 'pie'
            data: {
                labels: catLabels.length ? catLabels : ['No Data'],
                datasets: [{
                    label: 'Projects',
                    data: catCounts.length ? catCounts : [0],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(236, 72, 153, 0.7)'
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)'
                    ],
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // Status Chart
        const ctxStat = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStat, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    data: [statusMap.approved, statusMap.pending, statusMap.rejected],
                    backgroundColor: [
                        '#22c55e', // Green
                        '#f59e0b', // Amber
                        '#ef4444'  // Red
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 20 }
                    }
                },
                cutout: '70%'
            }
        });
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>