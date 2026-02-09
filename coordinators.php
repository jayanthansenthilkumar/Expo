<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Admin');

// Fetch all coordinators with project and review counts
$coordQuery = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM projects WHERE department = u.department) as project_count, (SELECT COUNT(*) FROM projects WHERE department = u.department AND reviewed_by = u.id) as reviewed_count FROM users u WHERE u.role = 'departmentcoordinator' ORDER BY u.department");
$coordinators = $coordQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch non-coordinator users for the assign dropdown
$nonCoordQuery = $pdo->query("SELECT id, name, email, department FROM users WHERE role != 'departmentcoordinator' ORDER BY name");
$nonCoordinators = $nonCoordQuery->fetchAll(PDO::FETCH_ASSOC);

// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinators | SPARK'26</title>
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
                    <h1>Coordinators</h1>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Search coordinators...">
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
                <div class="content-header">
                    <h2>Department Coordinators</h2>
                    <button class="btn-primary" onclick="document.getElementById('assignModal').style.display='flex'">
                        <i class="ri-add-line"></i> Assign Coordinator
                    </button>
                </div>

                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success" style="background:#d4edda;color:#155724;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <?php echo htmlspecialchars($flashSuccess); ?>
                    </div>
                <?php endif; ?>
                <?php if ($flashError): ?>
                    <div class="alert alert-error" style="background:#f8d7da;color:#721c24;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <?php echo htmlspecialchars($flashError); ?>
                    </div>
                <?php endif; ?>

                <div class="coordinators-grid">
                    <?php if (empty($coordinators)): ?>
                        <div class="empty-state" style="grid-column:1/-1;text-align:center;padding:2rem;">
                            <i class="ri-user-settings-line" style="font-size:3rem;color:var(--text-secondary);"></i>
                            <p>No coordinators assigned yet. Click "Assign Coordinator" to add one.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($coordinators as $coord):
                            $coordInitials = strtoupper(substr($coord['name'] ?? '', 0, 2));
                        ?>
                        <div class="coordinator-card">
                            <div class="coordinator-avatar">
                                <?php echo $coordInitials ?: '<i class="ri-user-line"></i>'; ?>
                            </div>
                            <div class="coordinator-info">
                                <h3><?php echo htmlspecialchars($coord['name'] ?? 'Unknown'); ?></h3>
                                <p class="coordinator-dept"><?php echo htmlspecialchars($coord['department'] ?? 'N/A'); ?></p>
                                <p class="coordinator-email"><?php echo htmlspecialchars($coord['email'] ?? '-'); ?></p>
                            </div>
                            <div class="coordinator-stats">
                                <div class="stat">
                                    <span class="stat-value"><?php echo (int)$coord['project_count']; ?></span>
                                    <span class="stat-label">Projects</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value"><?php echo (int)$coord['reviewed_count']; ?></span>
                                    <span class="stat-label">Reviewed</span>
                                </div>
                            </div>
                            <div class="coordinator-actions">
                                <button class="btn-secondary">View</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Assign Coordinator Modal -->
    <div id="assignModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:var(--bg-primary, #fff);border-radius:12px;padding:2rem;width:90%;max-width:500px;position:relative;">
            <button onclick="document.getElementById('assignModal').style.display='none'" style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-primary);">&times;</button>
            <h3 style="margin-bottom:1.5rem;">Assign Coordinator</h3>
            <form action="sparkBackend.php" method="POST">
                <input type="hidden" name="action" value="assign_coordinator">
                <div style="margin-bottom:1rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Select User</label>
                    <select name="user_id" required style="width:100%;padding:0.75rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:1rem;background:var(--bg-primary, #fff);">
                        <option value="">-- Select a user --</option>
                        <?php foreach ($nonCoordinators as $user): ?>
                            <option value="<?php echo (int)$user['id']; ?>"><?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Department</label>
                    <input type="text" name="department" required placeholder="Enter department name" style="width:100%;padding:0.75rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:1rem;box-sizing:border-box;">
                </div>
                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('assignModal').style.display='none'" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
