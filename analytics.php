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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Analytics';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="ri-folder-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo (int) $totalProjects; ?></h3>
                            <p>Total Projects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="ri-user-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo (int) $totalUsers; ?></h3>
                            <p>Registered Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <i class="ri-building-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo (int) $totalDepartments; ?></h3>
                            <p>Departments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo (int) $approvedProjects; ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                </div>

                <div class="analytics-charts">
                    <div class="chart-card">
                        <h3>Projects by Category</h3>
                        <div class="chart-content chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Submissions Over Time</h3>
                        <div class="chart-content chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Projects by Department</h3>
                        <div class="chart-content chart-container">
                            <canvas id="deptChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Approval Status</h3>
                        <div class="chart-content chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Prepare Data
        const categoryData = <?php echo json_encode(array_column($categories, 'cnt')); ?>;
        const categoryLabels = <?php echo json_encode(array_column($categories, 'category')); ?>;

        const trendData = <?php echo json_encode(array_column(array_reverse($monthlySubmissions), 'cnt')); ?>;
        const trendLabels = <?php echo json_encode(array_column(array_reverse($monthlySubmissions), 'month')); ?>;

        const deptData = <?php echo json_encode(array_column($deptBreakdown, 'cnt')); ?>;
        const deptLabels = <?php echo json_encode(array_column($deptBreakdown, 'department')); ?>;

        const statusData = <?php echo json_encode(array_column($statusBreakdown, 'cnt')); ?>;
        const statusLabels = <?php echo json_encode(array_column($statusBreakdown, 'status')); ?>;

        // Chart Configs
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        };

        // Category Chart
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: categoryLabels.map(l => l ? l.toUpperCase() : 'OTHER'),
                datasets: [{
                    data: categoryData,
                    backgroundColor: ['#B45309', '#ec4899', '#f59e0b', '#10b981', '#D97706', '#F59E0B'],
                    borderWidth: 0
                }]
            },
            options: commonOptions
        });

        // Trend Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Submissions',
                    data: trendData,
                    borderColor: '#D97706',
                    backgroundColor: 'rgba(217, 119, 6, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                ...commonOptions,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // Department Chart
        new Chart(document.getElementById('deptChart'), {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: [{
                    label: 'Projects',
                    data: deptData,
                    backgroundColor: '#F59E0B',
                    borderRadius: 4
                }]
            },
            options: {
                ...commonOptions,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } }
                }
            }
        });

        // Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: statusLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                datasets: [{
                    data: statusData,
                    backgroundColor: statusLabels.map(s => {
                        if (s === 'approved') return '#22c55e';
                        if (s === 'rejected') return '#ef4444';
                        if (s === 'pending') return '#f59e0b';
                        return '#3b82f6';
                    }),
                    borderWidth: 0
                }]
            },
            options: commonOptions
        });
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>