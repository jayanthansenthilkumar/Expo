<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');
$role = $_SESSION['role'];
$canCreate = in_array($role, ['admin', 'studentaffairs']);

// Fetch announcements for this user's role
$announcements = [];
$result = mysqli_query($conn, "SELECT a.*, u.name as author_name FROM announcements a JOIN users u ON a.author_id = u.id WHERE a.target_role IN ('all', '$role') ORDER BY a.is_featured DESC, a.created_at DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $announcements[] = $row;
}

$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | SPARK'26</title>
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
                    <h1>Announcements</h1>
                </div>
                <div class="header-right">
                    <div class="header-icon">
                        <i class="ri-notification-3-line"></i>
                        <span class="badge"></span>
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
                <?php if ($successMsg): ?>
                    <div style="background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;margin-bottom:1rem;"><i class="ri-checkbox-circle-line"></i> <?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div style="background:#fef2f2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;"><i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

                <?php if ($canCreate): ?>
                <div class="content-header" style="margin-bottom:1.5rem;">
                    <h2>Announcements</h2>
                    <button class="btn-primary" onclick="document.getElementById('announcementModal').style.display='flex'">
                        <i class="ri-add-line"></i> New Announcement
                    </button>
                </div>
                <?php endif; ?>

                <div class="announcements-container">
                    <?php if (empty($announcements)): ?>
                        <div class="empty-state">
                            <i class="ri-notification-off-line"></i>
                            <h3>No Announcements</h3>
                            <p>There are no announcements at this time.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                        <div class="announcement-card <?php echo $ann['is_featured'] ? 'featured' : ''; ?>">
                            <div class="announcement-header">
                                <?php if ($ann['is_featured']): ?>
                                    <span class="announcement-badge new">Featured</span>
                                <?php endif; ?>
                                <span class="announcement-date"><?php echo date('F j, Y', strtotime($ann['created_at'])); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
                            <div class="announcement-footer">
                                <span><i class="ri-user-line"></i> <?php echo htmlspecialchars($ann['author_name']); ?></span>
                                <span style="color:var(--text-muted);font-size:0.8rem;">For: <?php echo ucfirst($ann['target_role']); ?></span>
                                <?php if ($canCreate): ?>
                                <form action="sparkBackend.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?');">
                                    <input type="hidden" name="action" value="delete_announcement">
                                    <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                                    <button type="submit" class="btn-icon" style="color:#ef4444;font-size:0.85rem;"><i class="ri-delete-bin-line"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($canCreate): ?>
                <!-- Create Announcement Modal -->
                <div class="compose-modal" id="announcementModal" style="display:none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>New Announcement</h3>
                            <button class="btn-icon" onclick="document.getElementById('announcementModal').style.display='none'"><i class="ri-close-line"></i></button>
                        </div>
                        <form action="sparkBackend.php" method="POST">
                            <input type="hidden" name="action" value="create_announcement">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="announcementTitle" required placeholder="Announcement title">
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="announcementMessage" rows="5" required placeholder="Write your announcement..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Target Audience</label>
                                <select name="targetRole">
                                    <option value="all">All Users</option>
                                    <option value="student">Students Only</option>
                                    <option value="departmentcoordinator">Coordinators Only</option>
                                    <option value="studentaffairs">Student Affairs Only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:0.5rem;">
                                    <input type="checkbox" name="isFeatured"> Mark as Featured
                                </label>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="document.getElementById('announcementModal').style.display='none'">Cancel</button>
                                <button type="submit" class="btn-primary">Post Announcement</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
