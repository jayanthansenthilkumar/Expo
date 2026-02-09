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

// Query project counts
$totalProjects = 0;
$approvedProjects = 0;
$pendingProjects = 0;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM projects WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$totalProjects = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM projects WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$approvedProjects = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM projects WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$pendingProjects = $result->fetch_assoc()['total'] ?? 0;
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
                    <h1>Profile</h1>
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
</body>

</html>
