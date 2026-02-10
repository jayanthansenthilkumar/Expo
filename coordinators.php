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
            <?php
            $pageTitle = 'Coordinators';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">
                <div class="content-header">
                    <h2>Department Coordinators</h2>
                    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                        <button class="btn-primary" onclick="showAddCoordinator()">
                            <i class="ri-user-add-line"></i> Add Coordinator
                        </button>
                        <button class="btn-secondary" onclick="showAssignCoordinator()">
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
                                    <p class="coordinator-dept"><?php echo htmlspecialchars($coord['department'] ?? 'N/A'); ?>
                                    </p>
                                    <p class="coordinator-email"><?php echo htmlspecialchars($coord['email'] ?? '-'); ?></p>
                                </div>
                                <div class="coordinator-stats">
                                    <div class="stat">
                                        <span class="stat-value"><?php echo (int) $coord['project_count']; ?></span>
                                        <span class="stat-label">Projects</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo (int) $coord['reviewed_count']; ?></span>
                                        <span class="stat-label">Reviewed</span>
                                    </div>
                                </div>
                                <div class="coordinator-actions">
                                    <button class="btn-secondary" onclick='viewCoordinator(<?php echo json_encode([
                                        "id" => $coord["id"],
                                        "name" => $coord["name"] ?? "Unknown",
                                        "username" => $coord["username"] ?? "-",
                                        "email" => $coord["email"] ?? "-",
                                        "department" => $coord["department"] ?? "N/A",
                                        "project_count" => (int) $coord["project_count"],
                                        "reviewed_count" => (int) $coord["reviewed_count"],
                                        "status" => $coord["status"] ?? "active",
                                        "created_at" => $coord["created_at"] ?? ""
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>View</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Coordinator modals handled via SweetAlert -->

    <script src="assets/js/script.js"></script>
    <script>
        const nonCoordinators = <?php echo json_encode($nonCoordinators); ?>;

        function showAddCoordinator() {
            Swal.fire({
                title: 'Add New Coordinator',
                html: `
                <div style="text-align:left;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                        <div style="grid-column:1/-1;">
                            <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Full Name *</label>
                            <input id="swal-cName" class="swal2-input" placeholder="e.g. CSE Coordinator" style="margin:0;width:100%;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Username *</label>
                            <input id="swal-cUsername" class="swal2-input" placeholder="e.g. coordcse" style="margin:0;width:100%;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Password *</label>
                            <input id="swal-cPassword" type="password" class="swal2-input" placeholder="Enter password" style="margin:0;width:100%;box-sizing:border-box;">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Email *</label>
                            <input id="swal-cEmail" type="email" class="swal2-input" placeholder="e.g. coord.cse@spark.com" style="margin:0;width:100%;box-sizing:border-box;">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Department *</label>
                            <select id="swal-cDept" class="swal2-select" style="margin:0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                                <option value="">Select Department</option>
                                <option value="FE">FE (Freshmen Engineering)</option>
                                <option value="CSE">CSE</option><option value="AIDS">AIDS</option><option value="AIML">AIML</option>
                                <option value="ECE">ECE</option><option value="EEE">EEE</option><option value="MECH">MECH</option>
                                <option value="CIVIL">CIVIL</option><option value="IT">IT</option><option value="CSBS">CSBS</option>
                                <option value="CYBER">CYBER</option><option value="VLSI">VLSI</option><option value="MBA">MBA</option><option value="MCA">MCA</option>
                            </select>
                        </div>
                    </div>
                </div>
            `,
                confirmButtonText: '<i class="ri-save-line"></i> Create Coordinator',
                confirmButtonColor: '#2563eb',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                width: '550px',
                focusConfirm: false,
                preConfirm: () => {
                    const name = document.getElementById('swal-cName').value.trim();
                    const username = document.getElementById('swal-cUsername').value.trim();
                    const password = document.getElementById('swal-cPassword').value;
                    const email = document.getElementById('swal-cEmail').value.trim();
                    const dept = document.getElementById('swal-cDept').value;
                    if (!name || !username || !password || !email || !dept) {
                        Swal.showValidationMessage('All required fields must be filled');
                        return false;
                    }
                    return { name, username, password, email, department: dept };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const d = result.value;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="add_coordinator">
                    <input type="hidden" name="name" value="${escapeHtml(d.name)}">
                    <input type="hidden" name="username" value="${escapeHtml(d.username)}">
                    <input type="hidden" name="password" value="${escapeHtml(d.password)}">
                    <input type="hidden" name="email" value="${escapeHtml(d.email)}">
                    <input type="hidden" name="department" value="${escapeHtml(d.department)}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function showAssignCoordinator() {
            let userOptions = '<option value="">-- Select a user --</option>';
            nonCoordinators.forEach(u => {
                userOptions += `<option value="${u.id}">${escapeHtml(u.name)} (${escapeHtml(u.email)})</option>`;
            });

            Swal.fire({
                title: 'Assign Coordinator',
                html: `
                <div style="text-align:left;">
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Select User</label>
                    <select id="swal-assignUser" class="swal2-select" style="margin:0 0 1rem 0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">${userOptions}</select>
                    <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Department</label>
                    <input id="swal-assignDept" class="swal2-input" placeholder="Enter department name" style="margin:0;width:100%;box-sizing:border-box;">
                </div>
            `,
                confirmButtonText: '<i class="ri-user-settings-line"></i> Assign',
                confirmButtonColor: '#2563eb',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                focusConfirm: false,
                preConfirm: () => {
                    const userId = document.getElementById('swal-assignUser').value;
                    const dept = document.getElementById('swal-assignDept').value.trim();
                    if (!userId || !dept) {
                        Swal.showValidationMessage('Please select a user and enter department');
                        return false;
                    }
                    return { userId, department: dept };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="assign_coordinator">
                    <input type="hidden" name="user_id" value="${result.value.userId}">
                    <input type="hidden" name="department" value="${escapeHtml(result.value.department)}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function viewCoordinator(coord) {
            const createdDate = coord.created_at ? new Date(coord.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
            const statusColor = coord.status === 'active' ? '#22c55e' : '#ef4444';
            const statusLabel = coord.status === 'active' ? 'Active' : 'Inactive';

            Swal.fire({
                title: '',
                html: `
                <div style="text-align:center;margin-bottom:1rem;">
                    <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#2563eb);color:white;display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;">
                        ${escapeHtml((coord.name || '').substring(0, 2).toUpperCase())}
                    </div>
                    <h3 style="margin:0;font-size:1.2rem;color:#1e293b;">${escapeHtml(coord.name)}</h3>
                    <span style="display:inline-block;background:${statusColor}18;color:${statusColor};padding:0.2rem 0.75rem;border-radius:20px;font-size:0.75rem;font-weight:600;margin-top:0.25rem;">${statusLabel}</span>
                </div>
                <div style="text-align:left;background:#f8fafc;border-radius:10px;padding:1rem;border:1px solid #e2e8f0;margin-bottom:1rem;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem 1.5rem;font-size:0.88rem;">
                        <div><span style="color:#64748b;font-weight:500;">Department</span><br><strong style="color:#1e293b;">${escapeHtml(coord.department)}</strong></div>
                        <div><span style="color:#64748b;font-weight:500;">Username</span><br><strong style="color:#1e293b;">${escapeHtml(coord.username)}</strong></div>
                        <div style="grid-column:1/-1;"><span style="color:#64748b;font-weight:500;">Email</span><br><strong style="color:#1e293b;">${escapeHtml(coord.email)}</strong></div>
                        <div><span style="color:#64748b;font-weight:500;">Joined</span><br><strong style="color:#1e293b;">${createdDate}</strong></div>
                    </div>
                </div>
                <div style="display:flex;gap:1rem;justify-content:center;margin-bottom:0.5rem;">
                    <div style="text-align:center;background:#eff6ff;padding:0.75rem 1.5rem;border-radius:10px;flex:1;">
                        <div style="font-size:1.5rem;font-weight:700;color:#2563eb;">${coord.project_count}</div>
                        <div style="font-size:0.8rem;color:#64748b;">Projects</div>
                    </div>
                    <div style="text-align:center;background:#f0fdf4;padding:0.75rem 1.5rem;border-radius:10px;flex:1;">
                        <div style="font-size:1.5rem;font-weight:700;color:#22c55e;">${coord.reviewed_count}</div>
                        <div style="font-size:0.8rem;color:#64748b;">Reviewed</div>
                    </div>
                </div>
            `,
                showCloseButton: true,
                showCancelButton: true,
                confirmButtonText: '<i class="ri-delete-bin-line"></i> Remove Coordinator',
                confirmButtonColor: '#ef4444',
                cancelButtonText: 'Close',
                cancelButtonColor: '#6b7280',
                width: '480px',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `This will remove ${coord.name} as coordinator. They will become a regular user.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, remove',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280'
                    }).then((confirm) => {
                        if (confirm.isConfirmed) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'sparkBackend.php';
                            form.innerHTML = `
                            <input type="hidden" name="action" value="remove_coordinator">
                            <input type="hidden" name="user_id" value="${coord.id}">
                        `;
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                }
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        <?php if ($successMsg): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#2563eb' });
        <?php endif; ?>
    </script>
</body>

</html>