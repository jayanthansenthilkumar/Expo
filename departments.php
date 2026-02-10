<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Admin');

// ── AJAX handler: view department details ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'view_department') {
    header('Content-Type: application/json');
    $dept = $_GET['dept'] ?? '';
    if (empty($dept)) {
        echo json_encode(['error' => 'Missing department']);
        exit;
    }

    // Students in department
    $stmtS = mysqli_prepare($conn, "SELECT id, name, username, email, year, reg_no FROM users WHERE role='student' AND department = ? ORDER BY name");
    mysqli_stmt_bind_param($stmtS, 's', $dept);
    mysqli_stmt_execute($stmtS);
    $students = mysqli_stmt_get_result($stmtS)->fetch_all(MYSQLI_ASSOC);
    mysqli_stmt_close($stmtS);

    // Projects in department
    $stmtP = mysqli_prepare($conn, "SELECT id, title, description, category, status, created_at FROM projects WHERE department = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmtP, 's', $dept);
    mysqli_stmt_execute($stmtP);
    $projects = mysqli_stmt_get_result($stmtP)->fetch_all(MYSQLI_ASSOC);
    mysqli_stmt_close($stmtP);

    // Coordinator
    $stmtC = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE role='departmentcoordinator' AND department = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtC, 's', $dept);
    mysqli_stmt_execute($stmtC);
    $coordinator = mysqli_stmt_get_result($stmtC)->fetch_assoc();
    mysqli_stmt_close($stmtC);

    echo json_encode(['students' => $students, 'projects' => $projects, 'coordinator' => $coordinator]);
    exit;
}

// ── AJAX handler: get coordinators list for reassignment ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_coordinators') {
    header('Content-Type: application/json');
    $result = mysqli_query($conn, "SELECT id, name, department FROM users WHERE role='departmentcoordinator' ORDER BY name");
    $coordinators = [];
    while ($row = mysqli_fetch_assoc($result))
        $coordinators[] = $row;
    echo json_encode($coordinators);
    exit;
}

// ── AJAX handler: update coordinator assignment ──
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_coordinator') {
    header('Content-Type: application/json');
    $dept = trim($_POST['department'] ?? '');
    $coordId = intval($_POST['coordinator_id'] ?? 0);
    if (empty($dept)) {
        echo json_encode(['success' => false, 'message' => 'Missing department']);
        exit;
    }

    // Remove current coordinator from this department (set their dept to empty or keep but unassign)
    $stmtOld = mysqli_prepare($conn, "UPDATE users SET department = '' WHERE role='departmentcoordinator' AND department = ?");
    mysqli_stmt_bind_param($stmtOld, 's', $dept);
    mysqli_stmt_execute($stmtOld);
    mysqli_stmt_close($stmtOld);

    if ($coordId > 0) {
        // Assign new coordinator to this department
        $stmtNew = mysqli_prepare($conn, "UPDATE users SET department = ? WHERE id = ? AND role='departmentcoordinator'");
        mysqli_stmt_bind_param($stmtNew, 'si', $dept, $coordId);
        mysqli_stmt_execute($stmtNew);
        mysqli_stmt_close($stmtNew);
    }
    echo json_encode(['success' => true, 'message' => 'Coordinator updated successfully']);
    exit;
}

// ── AJAX handler: add department ──
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'add_department') {
    header('Content-Type: application/json');
    $deptName = strtoupper(trim($_POST['department_name'] ?? ''));
    if (empty($deptName)) {
        echo json_encode(['success' => false, 'message' => 'Department name is required']);
        exit;
    }

    // Check if department already exists
    $check = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users WHERE department = ?");
    mysqli_stmt_bind_param($check, 's', $deptName);
    mysqli_stmt_execute($check);
    $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($check))['cnt'];
    mysqli_stmt_close($check);

    if ($exists > 0) {
        echo json_encode(['success' => false, 'message' => 'Department already exists']);
        exit;
    }

    // Create a placeholder coordinator account for the new department
    $placeholderName = $deptName . ' Coordinator';
    $placeholderUsername = strtolower($deptName) . '_coord';
    $placeholderEmail = strtolower($deptName) . '@spark.edu';
    $placeholderPass = password_hash('spark2026', PASSWORD_DEFAULT);
    $role = 'departmentcoordinator';

    $stmt = mysqli_prepare($conn, "INSERT INTO users (name, username, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssssss', $placeholderName, $placeholderUsername, $placeholderEmail, $placeholderPass, $role, $deptName);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true, 'message' => "Department '$deptName' created with a default coordinator account (username: $placeholderUsername, password: spark2026)"]);
    } else {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'message' => 'Failed to create department: ' . mysqli_error($conn)]);
    }
    exit;
}

// ── AJAX handler: edit department name ──
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'rename_department') {
    header('Content-Type: application/json');
    $oldName = trim($_POST['old_name'] ?? '');
    $newName = strtoupper(trim($_POST['new_name'] ?? ''));
    if (empty($oldName) || empty($newName)) {
        echo json_encode(['success' => false, 'message' => 'Both old and new names required']);
        exit;
    }

    // Update department name in users and projects tables
    $conn->begin_transaction();
    try {
        $stmt1 = mysqli_prepare($conn, "UPDATE users SET department = ? WHERE department = ?");
        mysqli_stmt_bind_param($stmt1, 'ss', $newName, $oldName);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);

        $stmt2 = mysqli_prepare($conn, "UPDATE projects SET department = ? WHERE department = ?");
        mysqli_stmt_bind_param($stmt2, 'ss', $newName, $oldName);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Department renamed from '$oldName' to '$newName'"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to rename: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch all distinct departments from users and projects tables
$deptResult = mysqli_query($conn, "SELECT DISTINCT department FROM (SELECT department FROM users WHERE department IS NOT NULL AND department != '' UNION SELECT department FROM projects WHERE department IS NOT NULL AND department != '') as depts ORDER BY department");
$deptNames = [];
while ($row = mysqli_fetch_assoc($deptResult)) {
    $deptNames[] = $row['department'];
}

$departments = [];
foreach ($deptNames as $deptName) {
    // Count students
    $stmtS = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='student' AND department = ?");
    mysqli_stmt_bind_param($stmtS, 's', $deptName);
    mysqli_stmt_execute($stmtS);
    $studentCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtS))['cnt'];
    mysqli_stmt_close($stmtS);

    // Count projects
    $stmtP = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department = ?");
    mysqli_stmt_bind_param($stmtP, 's', $deptName);
    mysqli_stmt_execute($stmtP);
    $projectCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtP))['cnt'];
    mysqli_stmt_close($stmtP);

    // Find coordinator
    $stmtC = mysqli_prepare($conn, "SELECT name FROM users WHERE role='departmentcoordinator' AND department = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtC, 's', $deptName);
    mysqli_stmt_execute($stmtC);
    $coordResult = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtC));
    $coordinatorName = $coordResult ? $coordResult['name'] : null;
    mysqli_stmt_close($stmtC);

    $departments[] = [
        'name' => $deptName,
        'student_count' => $studentCount,
        'project_count' => $projectCount,
        'coordinator_name' => $coordinatorName
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Departments';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">
                <div class="content-header">
                    <h2>Department Management</h2>
                    <button class="btn-primary" onclick="addDepartment()">
                        <i class="ri-add-line"></i> Add Department
                    </button>
                </div>

                <div class="departments-grid">
                    <?php if (empty($departments)): ?>
                        <div class="empty-state" style="grid-column: 1/-1; text-align:center; padding:2rem;">
                            <i class="ri-building-line" style="font-size:3rem; color:var(--text-secondary);"></i>
                            <p>No departments found.</p>
                        </div>
                    <?php else: ?>
                        <?php
                        $deptIcons = ['ri-computer-line', 'ri-cpu-line', 'ri-settings-line', 'ri-building-line', 'ri-flask-line'];
                        foreach ($departments as $index => $dept):
                            $icon = $deptIcons[$index % count($deptIcons)];
                            ?>
                            <div class="department-card">
                                <div class="dept-icon">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <h3><?php echo htmlspecialchars($dept['name']); ?></h3>
                                <div class="dept-stats">
                                    <span><i class="ri-user-line"></i> <?php echo (int) $dept['student_count']; ?>
                                        Students</span>
                                    <span><i class="ri-folder-line"></i> <?php echo (int) $dept['project_count']; ?>
                                        Projects</span>
                                </div>
                                <div class="dept-coordinator">
                                    <span>Coordinator:
                                        <?php echo $dept['coordinator_name'] ? htmlspecialchars($dept['coordinator_name']) : 'Not Assigned'; ?></span>
                                </div>
                                <div class="dept-actions">
                                    <button class="btn-icon" title="Edit"
                                        onclick="editDepartment('<?php echo htmlspecialchars($dept['name']); ?>', '<?php echo htmlspecialchars($dept['coordinator_name'] ?? ''); ?>')"><i
                                            class="ri-edit-line"></i></button>
                                    <button class="btn-icon" title="View"
                                        onclick="viewDepartment('<?php echo htmlspecialchars($dept['name']); ?>')"><i
                                            class="ri-eye-line"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // ── View Department ──
        function viewDepartment(deptName) {
            Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            fetch('departments.php?ajax=view_department&dept=' + encodeURIComponent(deptName))
                .then(r => r.json())
                .then(data => {
                    if (data.error) { Swal.fire('Error', data.error, 'error'); return; }
                    let html = '';

                    // Coordinator
                    html += '<div style="text-align:left;margin-bottom:1rem;padding:10px;background:#f0f4ff;border-radius:8px;">';
                    html += '<strong><i class="ri-user-star-line"></i> Coordinator:</strong> ';
                    html += data.coordinator ? (data.coordinator.name + ' (' + data.coordinator.email + ')') : '<em>Not Assigned</em>';
                    html += '</div>';

                    // Students
                    html += '<div style="text-align:left;margin-bottom:0.5rem;"><strong><i class="ri-group-line"></i> Students (' + data.students.length + ')</strong></div>';
                    if (data.students.length > 0) {
                        html += '<div style="max-height:200px;overflow:auto;margin-bottom:1rem;"><table style="width:100%;border-collapse:collapse;font-size:13px;"><thead><tr>';
                        html += '<th style="padding:6px 8px;border:1px solid #ddd;background:#f5f7fa;">Name</th>';
                        html += '<th style="padding:6px 8px;border:1px solid #ddd;background:#f5f7fa;">Reg No</th>';
                        html += '<th style="padding:6px 8px;border:1px solid #ddd;background:#f5f7fa;">Year</th>';
                        html += '<th style="padding:6px 8px;border:1px solid #ddd;background:#f5f7fa;">Email</th>';
                        html += '</tr></thead><tbody>';
                        data.students.forEach(s => {
                            html += '<tr><td style="padding:5px 8px;border:1px solid #eee;">' + s.name + '</td>';
                            html += '<td style="padding:5px 8px;border:1px solid #eee;">' + (s.reg_no || '-') + '</td>';
                            html += '<td style="padding:5px 8px;border:1px solid #eee;">' + (s.year || '-') + '</td>';
                            html += '<td style="padding:5px 8px;border:1px solid #eee;">' + s.email + '</td></tr>';
                        });
                        html += '</tbody></table></div>';
                    } else {
                        html += '<p style="color:#888;margin-bottom:1rem;">No students in this department.</p>';
                    }

                    // Projects
                    html += '<div style="text-align:left;margin-bottom:0.5rem;"><strong><i class="ri-folder-line"></i> Projects (' + data.projects.length + ')</strong></div>';
                    if (data.projects.length > 0) {
                        html += '<div style="max-height:200px;overflow:auto;"><table style="width:100%;border-collapse:collapse;font-size:13px;"><thead><tr>';
                        html += '<th style="padding:6px 8px;border:1px solid #ddd;background:#f5f7fa;">Title</th>';
                        html += '<th style="padding:6px 8px;border:1px solid #ddd;background:#f5f7fa;">Category</th>';
                        html += '<th style="padding:6px 8px;border:1px solid #ddd;background:#f5f7fa;">Status</th>';
                        html += '</tr></thead><tbody>';
                        data.projects.forEach(p => {
                            const statusColor = p.status === 'approved' ? '#28a745' : (p.status === 'rejected' ? '#dc3545' : '#ffc107');
                            html += '<tr><td style="padding:5px 8px;border:1px solid #eee;">' + p.title + '</td>';
                            html += '<td style="padding:5px 8px;border:1px solid #eee;">' + (p.category || '-') + '</td>';
                            html += '<td style="padding:5px 8px;border:1px solid #eee;"><span style="color:' + statusColor + ';font-weight:600;">' + (p.status || 'pending') + '</span></td></tr>';
                        });
                        html += '</tbody></table></div>';
                    } else {
                        html += '<p style="color:#888;">No projects in this department.</p>';
                    }

                    Swal.fire({ title: deptName + ' Department', html: html, width: '700px', confirmButtonColor: '#4361ee' });
                })
                .catch(() => Swal.fire('Error', 'Failed to load department data', 'error'));
        }

        // ── Edit Department ──
        function editDepartment(deptName, currentCoordinator) {
            // Fetch all coordinators for the dropdown
            fetch('departments.php?ajax=get_coordinators')
                .then(r => r.json())
                .then(coordinators => {
                    let coordOptions = '<option value="0">-- No Coordinator --</option>';
                    coordinators.forEach(c => {
                        const selected = (c.department === deptName) ? 'selected' : '';
                        const label = c.name + (c.department && c.department !== deptName ? ' (' + c.department + ')' : '');
                        coordOptions += '<option value="' + c.id + '" ' + selected + '>' + label + '</option>';
                    });

                    Swal.fire({
                        title: 'Edit ' + deptName,
                        html:
                            '<div style="text-align:left;">' +
                            '<label style="font-weight:600;display:block;margin-bottom:4px;">Department Name</label>' +
                            '<input id="editDeptName" class="swal2-input" value="' + deptName + '" style="margin:0 0 1rem 0;width:100%;box-sizing:border-box;">' +
                            '<label style="font-weight:600;display:block;margin-bottom:4px;">Assign Coordinator</label>' +
                            '<select id="editCoordinator" class="swal2-select" style="margin:0;width:100%;box-sizing:border-box;">' + coordOptions + '</select>' +
                            '</div>',
                        showCancelButton: true,
                        confirmButtonText: 'Save Changes',
                        confirmButtonColor: '#4361ee',
                        preConfirm: () => {
                            const newName = document.getElementById('editDeptName').value.trim();
                            const coordId = document.getElementById('editCoordinator').value;
                            if (!newName) { Swal.showValidationMessage('Department name is required'); return false; }
                            return { newName: newName.toUpperCase(), coordId: coordId };
                        }
                    }).then((result) => {
                        if (!result.isConfirmed) return;
                        const { newName, coordId } = result.value;

                        // Chain: rename if changed, then update coordinator
                        let promises = [];

                        // Rename department if name changed
                        if (newName !== deptName) {
                            const renameData = new FormData();
                            renameData.append('ajax_action', 'rename_department');
                            renameData.append('old_name', deptName);
                            renameData.append('new_name', newName);
                            promises.push(fetch('departments.php', { method: 'POST', body: renameData }).then(r => r.json()));
                        }

                        // Update coordinator
                        const coordData = new FormData();
                        coordData.append('ajax_action', 'update_coordinator');
                        coordData.append('department', newName);
                        coordData.append('coordinator_id', coordId);
                        promises.push(fetch('departments.php', { method: 'POST', body: coordData }).then(r => r.json()));

                        Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        Promise.all(promises).then(results => {
                            const failed = results.find(r => !r.success);
                            if (failed) {
                                Swal.fire('Error', failed.message, 'error');
                            } else {
                                Swal.fire({ icon: 'success', title: 'Updated!', text: 'Department updated successfully', confirmButtonColor: '#4361ee' })
                                    .then(() => location.reload());
                            }
                        }).catch(() => Swal.fire('Error', 'Failed to save changes', 'error'));
                    });
                })
                .catch(() => Swal.fire('Error', 'Failed to load coordinators', 'error'));
        }

        // ── Add Department ──
        function addDepartment() {
            Swal.fire({
                title: 'Add New Department',
                html:
                    '<div style="text-align:left;">' +
                    '<label style="font-weight:600;display:block;margin-bottom:4px;">Department Name (abbreviation)</label>' +
                    '<input id="newDeptName" class="swal2-input" placeholder="e.g. ECE, MECH, IT" style="margin:0 0 0.5rem 0;width:100%;box-sizing:border-box;">' +
                    '<p style="font-size:12px;color:#888;">A default coordinator account will be created for this department.</p>' +
                    '</div>',
                showCancelButton: true,
                confirmButtonText: 'Create Department',
                confirmButtonColor: '#4361ee',
                preConfirm: () => {
                    const name = document.getElementById('newDeptName').value.trim();
                    if (!name) { Swal.showValidationMessage('Department name is required'); return false; }
                    return name;
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                const formData = new FormData();
                formData.append('ajax_action', 'add_department');
                formData.append('department_name', result.value);
                Swal.fire({ title: 'Creating...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                fetch('departments.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        Swal.fire({ icon: data.success ? 'success' : 'error', title: data.success ? 'Created!' : 'Error', text: data.message, confirmButtonColor: '#4361ee' })
                            .then(() => { if (data.success) location.reload(); });
                    })
                    .catch(() => Swal.fire('Error', 'Failed to create department', 'error'));
            });
        }
    </script>
</body>

</html>