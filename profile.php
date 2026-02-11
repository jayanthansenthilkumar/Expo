<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userEmail = $_SESSION['email'] ?? '';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');
$userId = $_SESSION['user_id'] ?? 0;
$department = $_SESSION['department'] ?? '';
$year = $_SESSION['year'] ?? '';
$regNo = $_SESSION['reg_no'] ?? '';
$username = $_SESSION['username'] ?? '';

// Check if user is in a team (for team-based project counts)
$userTeamId = null;
$teamStmt = $conn->prepare("SELECT team_id FROM team_members WHERE user_id = ?");
$teamStmt->bind_param("i", $userId);
$teamStmt->execute();
$teamResult = $teamStmt->get_result();
if ($teamRow = $teamResult->fetch_assoc()) {
    $userTeamId = $teamRow['team_id'];
}
$teamStmt->close();

// Query project counts (team-based if in a team, otherwise individual)
$totalProjects = 0;
$approvedProjects = 0;
$pendingProjects = 0;

if ($userTeamId) {
    $stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM projects WHERE team_id = ? GROUP BY status");
    $stmt->bind_param("i", $userTeamId);
} else {
    $stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM projects WHERE student_id = ? GROUP BY status");
    $stmt->bind_param("i", $userId);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $totalProjects += $row['cnt'];
    if ($row['status'] === 'approved')
        $approvedProjects = $row['cnt'];
    if ($row['status'] === 'pending')
        $pendingProjects = $row['cnt'];
}
$stmt->close();

// Get user's joined date
$joinedDate = 'Unknown';
$stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $joinedDate = date('F Y', strtotime($row['created_at']));
}
$stmt->close();

// Flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Profile';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">
                <div class="profile-container">
                    <div class="profile-header-card">
                        <div class="profile-avatar-large"><?php echo $userInitials; ?></div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($userName); ?></h2>
                            <p class="profile-role"><?php echo htmlspecialchars($userRole); ?></p>
                            <p class="profile-email"><?php echo htmlspecialchars($userEmail); ?></p>
                        </div>
                        <a href="settings.php" class="btn-secondary">
                            <i class="ri-edit-line"></i> Edit Profile
                        </a>
                    </div>

                    <div class="profile-details">
                        <div class="profile-section">
                            <h3>Personal Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Full Name</label>
                                    <p><?php echo htmlspecialchars($userName); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Email Address</label>
                                    <p><?php echo htmlspecialchars($userEmail); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Role</label>
                                    <p><?php echo htmlspecialchars($userRole); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Department</label>
                                    <p><?php echo htmlspecialchars($department); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Year</label>
                                    <p><?php echo htmlspecialchars($year); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Register No</label>
                                    <p><?php echo htmlspecialchars($regNo); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Joined</label>
                                    <p><?php echo htmlspecialchars($joinedDate); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3>Activity Summary</h3>
                            <div class="stats-row">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $totalProjects; ?></span>
                                    <span class="stat-label">Projects</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $approvedProjects; ?></span>
                                    <span class="stat-label">Approved</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $pendingProjects; ?></span>
                                    <span class="stat-label">Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        <?php if ($success): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($success); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($error): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($error); ?>', confirmButtonColor: '#2563eb' });
        <?php endif; ?>
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>