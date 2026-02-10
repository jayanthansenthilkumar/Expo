<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? 'Admin');

// ── AJAX handler: view table data ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'view_table') {
    header('Content-Type: application/json');
    $tableName = $_GET['table'] ?? '';
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
        echo json_encode(['error' => 'Invalid table name']);
        exit;
    }
    $rows = [];
    $columns = [];
    $result = $conn->query("SELECT * FROM `" . $conn->real_escape_string($tableName) . "` LIMIT 100");
    if ($result && $result->num_rows > 0) {
        $fields = $result->fetch_fields();
        foreach ($fields as $f) $columns[] = $f->name;
        while ($row = $result->fetch_assoc()) $rows[] = $row;
    }
    echo json_encode(['columns' => $columns, 'rows' => $rows]);
    exit;
}

// ── AJAX handler: backup database (SQL dump download) ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'backup') {
    $dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="spark_backup_' . date('Y-m-d_His') . '.sql"');
    $tablesRes = $conn->query("SHOW TABLES");
    $output = "-- SPARK'26 Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n-- Database: $dbName\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    while ($tRow = $tablesRes->fetch_row()) {
        $t = $tRow[0];
        $createRes = $conn->query("SHOW CREATE TABLE `$t`");
        if ($createRes) {
            $createRow = $createRes->fetch_row();
            $output .= "DROP TABLE IF EXISTS `$t`;\n" . $createRow[1] . ";\n\n";
        }
        $dataRes = $conn->query("SELECT * FROM `$t`");
        if ($dataRes && $dataRes->num_rows > 0) {
            while ($dRow = $dataRes->fetch_assoc()) {
                $vals = [];
                foreach ($dRow as $v) $vals[] = ($v === null) ? "NULL" : "'" . $conn->real_escape_string($v) . "'";
                $output .= "INSERT INTO `$t` VALUES (" . implode(',', $vals) . ");\n";
            }
            $output .= "\n";
        }
    }
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    echo $output;
    exit;
}

// ── AJAX handler: export table as CSV ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'export_csv') {
    $tableName = $_GET['table'] ?? '';
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid table name']);
        exit;
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $tableName . '_' . date('Y-m-d') . '.csv"');
    $fp = fopen('php://output', 'w');
    $result = $conn->query("SELECT * FROM `" . $conn->real_escape_string($tableName) . "`");
    if ($result && $result->num_rows > 0) {
        $fields = $result->fetch_fields();
        $headers = [];
        foreach ($fields as $f) $headers[] = $f->name;
        fputcsv($fp, $headers);
        while ($row = $result->fetch_assoc()) fputcsv($fp, $row);
    }
    fclose($fp);
    exit;
}

// ── AJAX handler: reset database ──
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'reset_database') {
    header('Content-Type: application/json');
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Only admin can reset the database']);
        exit;
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $tablesRes = $conn->query("SHOW TABLES");
    $errors = [];
    while ($tRow = $tablesRes->fetch_row()) {
        $t = $tRow[0];
        if ($t === 'settings') continue; // preserve settings
        if (!$conn->query("TRUNCATE TABLE `$t`")) {
            $errors[] = $t;
        }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    if (empty($errors)) {
        echo json_encode(['success' => true, 'message' => 'All tables cleared successfully (settings preserved)']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear: ' . implode(', ', $errors)]);
    }
    exit;
}

// ── AJAX handler: restore database from SQL ──
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'restore_database') {
    header('Content-Type: application/json');
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Only admin can restore the database']);
        exit;
    }
    if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }
    $ext = strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') {
        echo json_encode(['success' => false, 'message' => 'Only .sql files are accepted']);
        exit;
    }
    $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
    $conn->multi_query($sql);
    // Flush all results
    do {
        if ($res = $conn->store_result()) $res->free();
    } while ($conn->more_results() && $conn->next_result());
    if ($conn->error) {
        echo json_encode(['success' => false, 'message' => 'SQL error: ' . $conn->error]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Database restored successfully']);
    }
    exit;
}

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
                                <button class="btn-primary" onclick="backupDatabase()">Create Backup</button>
                            </div>
                            <div class="db-action-card">
                                <i class="ri-upload-line"></i>
                                <h4>Restore Database</h4>
                                <p>Restore from a previous backup</p>
                                <button class="btn-secondary" onclick="restoreDatabase()">Restore</button>
                            </div>
                            <div class="db-action-card">
                                <i class="ri-refresh-line"></i>
                                <h4>Reset Database</h4>
                                <p>Clear all data and reset to default</p>
                                <button class="btn-danger" onclick="resetDatabase()">Reset</button>
                            </div>
                            <div class="db-action-card">
                                <i class="ri-file-download-line"></i>
                                <h4>Export Data</h4>
                                <p>Export data to CSV or Excel format</p>
                                <button class="btn-secondary" onclick="exportData()">Export</button>
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
                                            <button class="btn-icon" title="View" onclick="viewTable('<?php echo htmlspecialchars($table['name']); ?>')"><i class="ri-eye-line"></i></button>
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
    <script>
    // Available tables for export picker
    const tableNames = <?php echo json_encode(array_column($tables, 'name')); ?>;

    // ── Backup Database ──
    function backupDatabase() {
        Swal.fire({
            title: 'Backup Database?',
            text: 'This will download a full SQL backup of all tables.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Download Backup',
            confirmButtonColor: '#4361ee'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'database.php?ajax=backup';
            }
        });
    }

    // ── Restore Database ──
    function restoreDatabase() {
        Swal.fire({
            title: 'Restore Database',
            html: '<p style="margin-bottom:1rem;color:#666;">Upload a <strong>.sql</strong> backup file to restore.</p>' +
                  '<input type="file" id="sqlFile" accept=".sql" class="swal2-file" style="display:block;margin:0 auto;">',
            icon: 'upload',
            showCancelButton: true,
            confirmButtonText: 'Restore',
            confirmButtonColor: '#4361ee',
            preConfirm: () => {
                const file = document.getElementById('sqlFile').files[0];
                if (!file) { Swal.showValidationMessage('Please select a .sql file'); return false; }
                if (!file.name.endsWith('.sql')) { Swal.showValidationMessage('Only .sql files are accepted'); return false; }
                return file;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('ajax_action', 'restore_database');
                formData.append('sql_file', result.value);
                Swal.fire({ title: 'Restoring...', text: 'Please wait', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                fetch('database.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        Swal.fire({ icon: data.success ? 'success' : 'error', title: data.success ? 'Restored!' : 'Error', text: data.message })
                            .then(() => { if (data.success) location.reload(); });
                    })
                    .catch(() => Swal.fire('Error', 'Restore failed', 'error'));
            }
        });
    }

    // ── Reset Database ──
    function resetDatabase() {
        Swal.fire({
            title: 'Reset Database?',
            html: '<p style="color:#e74c3c;font-weight:600;">This will permanently delete ALL data from all tables (except settings).</p><p>This action cannot be undone!</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Reset Everything',
            confirmButtonColor: '#e74c3c',
            cancelButtonText: 'Cancel',
            input: 'text',
            inputPlaceholder: 'Type RESET to confirm',
            inputValidator: (value) => {
                if (value !== 'RESET') return 'You need to type RESET to confirm';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('ajax_action', 'reset_database');
                Swal.fire({ title: 'Resetting...', text: 'Please wait', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                fetch('database.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        Swal.fire({ icon: data.success ? 'success' : 'error', title: data.success ? 'Reset Complete!' : 'Error', text: data.message })
                            .then(() => { if (data.success) location.reload(); });
                    })
                    .catch(() => Swal.fire('Error', 'Reset failed', 'error'));
            }
        });
    }

    // ── Export Data (CSV) ──
    function exportData() {
        const options = {};
        tableNames.forEach(t => { options[t] = t; });
        Swal.fire({
            title: 'Export Table as CSV',
            input: 'select',
            inputOptions: options,
            inputPlaceholder: 'Select a table',
            showCancelButton: true,
            confirmButtonText: 'Export CSV',
            confirmButtonColor: '#4361ee',
            inputValidator: (value) => {
                if (!value) return 'Please select a table';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'database.php?ajax=export_csv&table=' + encodeURIComponent(result.value);
            }
        });
    }

    // ── View Table Data ──
    function viewTable(tableName) {
        Swal.fire({ title: 'Loading...', text: 'Fetching table data', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('database.php?ajax=view_table&table=' + encodeURIComponent(tableName))
            .then(r => r.json())
            .then(data => {
                if (data.error) { Swal.fire('Error', data.error, 'error'); return; }
                if (data.rows.length === 0) {
                    Swal.fire({ title: tableName, text: 'Table is empty', icon: 'info' });
                    return;
                }
                let html = '<div style="max-height:400px;overflow:auto;"><table style="width:100%;border-collapse:collapse;font-size:13px;"><thead><tr>';
                data.columns.forEach(c => { html += '<th style="padding:8px 10px;border:1px solid #ddd;background:#f5f7fa;text-align:left;white-space:nowrap;">' + c + '</th>'; });
                html += '</tr></thead><tbody>';
                data.rows.forEach(row => {
                    html += '<tr>';
                    data.columns.forEach(c => {
                        let val = row[c] ?? '';
                        if (val.length > 60) val = val.substring(0, 60) + '...';
                        html += '<td style="padding:6px 10px;border:1px solid #eee;white-space:nowrap;">' + val.replace(/</g, '&lt;') + '</td>';
                    });
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                if (data.rows.length >= 100) html += '<p style="color:#888;margin-top:8px;font-size:12px;">Showing first 100 rows</p>';
                Swal.fire({ title: tableName, html: html, width: '80%', confirmButtonColor: '#4361ee' });
            })
            .catch(() => Swal.fire('Error', 'Failed to load table data', 'error'));
    }
    </script>
</body>

</html>
