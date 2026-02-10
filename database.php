<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? 'Admin');

// Get database name
$dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];

// Get table list and row counts
$tables = [];
$tablesResult = $conn->query("SHOW TABLES");
if ($tablesResult) {
    while ($row = $tablesResult->fetch_row()) {
        $tableName = $row[0];
        $countResult = $conn->query("SELECT COUNT(*) FROM `" . $conn->real_escape_string($tableName) . "`");
        $rowCount = $countResult ? $countResult->fetch_row()[0] : 0;
        $tables[] = ['name' => $tableName, 'row_count' => $rowCount];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database | SPARK'26</title>
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
                    <h1>Database Management</h1>
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
                <div class="database-container">
                    <div class="db-status-card">
                        <div class="db-status-header">
                            <h3>Database Status</h3>
                            <span class="status-badge online">Online</span>
                        </div>
                        <div class="db-info">
                            <div class="db-info-item">
                                <span class="db-label">Database Name</span>
                                <span class="db-value"><?php echo htmlspecialchars($dbName); ?></span>
                            </div>
                            <div class="db-info-item">
                                <span class="db-label">Server</span>
                                <span class="db-value"><?php echo htmlspecialchars($conn->host_info); ?></span>
                            </div>
                            <div class="db-info-item">
                                <span class="db-label">Tables</span>
                                <span class="db-value"><?php echo count($tables); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="db-actions-section">
                        <h3>Database Operations</h3>
                        <div class="db-actions-grid">
                            <div class="db-action-card">
                                <i class="ri-download-line"></i>
                                <h4>Backup Database</h4>
                                <p>Create a backup of the entire database</p>
                                <button class="btn-primary">Create Backup</button>
                            </div>
                            <div class="db-action-card">
                                <i class="ri-upload-line"></i>
                                <h4>Restore Database</h4>
                                <p>Restore from a previous backup</p>
                                <button class="btn-secondary">Restore</button>
                            </div>
                            <div class="db-action-card">
                                <i class="ri-refresh-line"></i>
                                <h4>Reset Database</h4>
                                <p>Clear all data and reset to default</p>
                                <button class="btn-danger">Reset</button>
                            </div>
                            <div class="db-action-card">
                                <i class="ri-file-download-line"></i>
                                <h4>Export Data</h4>
                                <p>Export data to CSV or Excel format</p>
                                <button class="btn-secondary">Export</button>
                            </div>
                        </div>
                    </div>

                    <div class="db-tables-section">
                        <h3>Database Tables</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Table Name</th>
                                        <th>Rows</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tables as $table): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($table['name']); ?></td>
                                        <td><?php echo $table['row_count']; ?></td>
                                        <td>
                                            <button class="btn-icon" title="View"><i class="ri-eye-line"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
