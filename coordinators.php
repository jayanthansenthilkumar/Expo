<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Admin');

// Fetch all coordinators with project and review counts
$coordResult = mysqli_query($conn, "SELECT u.*, (SELECT COUNT(*) FROM projects WHERE department = u.department) as project_count, (SELECT COUNT(*) FROM projects WHERE department = u.department AND reviewed_by = u.id) as reviewed_count FROM users u WHERE u.role = 'departmentcoordinator' ORDER BY u.department");
$coordinators = [];
while ($row = mysqli_fetch_assoc($coordResult)) {
    $coordinators[] = $row;
}

// Fetch non-coordinator users for the assign dropdown
$nonCoordResult = mysqli_query($conn, "SELECT id, name, email, department FROM users WHERE role != 'departmentcoordinator' ORDER BY name");
$nonCoordinators = [];
while ($row = mysqli_fetch_assoc($nonCoordResult)) {
    $nonCoordinators[] = $row;
}

// Flash messages
$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinators | SPARK'26</title>
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
                    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                        <button class="btn-primary" onclick="document.getElementById('addCoordModal').style.display='flex'">
                            <i class="ri-user-add-line"></i> Add Coordinator
                        </button>
                        <button class="btn-secondary" onclick="document.getElementById('assignModal').style.display='flex'">
                            <i class="ri-user-settings-line"></i> Assign Existing User
                        </button>
                    </div>
                </div>

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

    <!-- Add Coordinator Modal -->
    <div id="addCoordModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;overflow-y:auto;">
        <div style="background:var(--bg-primary, #fff);border-radius:12px;padding:2rem;width:90%;max-width:550px;position:relative;margin:2rem auto;max-height:90vh;overflow-y:auto;">
            <button onclick="document.getElementById('addCoordModal').style.display='none'" style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-primary);">&times;</button>
            <h3 style="margin-bottom:1.5rem;"><i class="ri-user-add-line"></i> Add New Coordinator</h3>
            <form action="sparkBackend.php" method="POST" id="addCoordForm">
                <input type="hidden" name="action" value="add_coordinator">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div style="grid-column:1/-1;">
                        <label style="display:block;margin-bottom:0.4rem;font-weight:500;">Full Name *</label>
                        <input type="text" name="name" required placeholder="e.g. CSE Coordinator" style="width:100%;padding:0.65rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:0.4rem;font-weight:500;">Username *</label>
                        <input type="text" name="username" required placeholder="e.g. coordcse" style="width:100%;padding:0.65rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:0.4rem;font-weight:500;">Password *</label>
                        <input type="password" name="password" required placeholder="Enter password" style="width:100%;padding:0.65rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="display:block;margin-bottom:0.4rem;font-weight:500;">Email *</label>
                        <input type="email" name="email" required placeholder="e.g. coord.cse@spark.com" style="width:100%;padding:0.65rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="display:block;margin-bottom:0.4rem;font-weight:500;">Department *</label>
                        <select name="department" required style="width:100%;padding:0.65rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:0.95rem;background:var(--bg-primary, #fff);">
                            <option value="">Select Department</option>
                            <option value="CSE">CSE</option>
                            <option value="AIDS">AIDS</option>
                            <option value="AIML">AIML</option>
                            <option value="ECE">ECE</option>
                            <option value="EEE">EEE</option>
                            <option value="MECH">MECH</option>
                            <option value="CIVIL">CIVIL</option>
                            <option value="IT">IT</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:0.4rem;font-weight:500;">Register No</label>
                        <input type="text" name="reg_no" placeholder="e.g. 612223104088" style="width:100%;padding:0.65rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:0.4rem;font-weight:500;">Status</label>
                        <select name="status" style="width:100%;padding:0.65rem;border:1px solid var(--border-color, #ddd);border-radius:8px;font-size:0.95rem;background:var(--bg-primary, #fff);">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem;">
                    <button type="button" onclick="document.getElementById('addCoordModal').style.display='none'" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="ri-save-line"></i> Create Coordinator</button>
                </div>
            </form>
        </div>
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
    <script>
    <?php if ($successMsg): ?>
    Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#2563eb' });
    <?php endif; ?>

    // SweetAlert confirmation for Add Coordinator form
    document.getElementById('addCoordForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Add Coordinator?',
            text: 'This will create a new department coordinator account.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, create!'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
    </script>
</body>

</html>
