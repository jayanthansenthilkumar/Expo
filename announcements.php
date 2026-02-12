<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');
$role = $_SESSION['role'];
$canCreate = in_array($role, ['admin', 'studentaffairs']);

// Fetch announcements - admin/studentaffairs see ALL, others see only their role + 'all'
$announcements = [];
if ($canCreate) {
    $annResult = mysqli_query($conn, "SELECT a.*, u.name as author_name FROM announcements a JOIN users u ON a.author_id = u.id ORDER BY a.is_featured DESC, a.created_at DESC");
    while ($row = mysqli_fetch_assoc($annResult)) {
        $announcements[] = $row;
    }
} else {
    $annStmt = mysqli_prepare($conn, "SELECT a.*, u.name as author_name FROM announcements a JOIN users u ON a.author_id = u.id WHERE a.target_role IN ('all', ?) ORDER BY a.is_featured DESC, a.created_at DESC");
    mysqli_stmt_bind_param($annStmt, "s", $role);
    mysqli_stmt_execute($annStmt);
    $annResult = mysqli_stmt_get_result($annStmt);
    while ($row = mysqli_fetch_assoc($annResult)) {
        $announcements[] = $row;
    }
    mysqli_stmt_close($annStmt);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Announcements';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <?php if ($canCreate): ?>
                    <div class="content-header" style="margin-bottom:1.5rem;">
                        <h2>Announcements</h2>
                        <button class="btn-primary" onclick="showCreateAnnouncement()">
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
                                    <span
                                        class="announcement-date"><?php echo date('F j, Y', strtotime($ann['created_at'])); ?></span>
                                </div>
                                <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
                                <div class="announcement-footer">
                                    <span><i class="ri-user-line"></i>
                                        <?php echo htmlspecialchars($ann['author_name']); ?></span>
                                    <span style="color:var(--text-muted);font-size:0.8rem;">For:
                                        <?php echo ucfirst($ann['target_role']); ?></span>
                                    <?php if ($canCreate): ?>
                                        <form action="sparkBackend.php" method="POST" style="display:inline;"
                                            class="confirm-delete-form">
                                            <input type="hidden" name="action" value="delete_announcement">
                                            <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                                            <button type="submit" class="btn-icon" style="color:#ef4444;font-size:0.85rem;"><i
                                                    class="ri-delete-bin-line"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($canCreate): ?>
                    <!-- Announcements handled via SweetAlert -->
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        <?php if ($successMsg): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#D97706', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#D97706' });
        <?php endif; ?>

        function showCreateAnnouncement() {
            Swal.fire({
                title: 'New Announcement',
                html: `
                <div style="text-align:left;">
                    <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:0.3rem;">Title *</label>
                    <input id="swal-annTitle" class="swal2-input" placeholder="Announcement title" style="margin:0 0 1rem 0;width:100%;box-sizing:border-box;">
                    <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:0.3rem;">Message *</label>
                    <textarea id="swal-annMessage" class="swal2-textarea" placeholder="Write your announcement..." style="margin:0 0 1rem 0;width:100%;box-sizing:border-box;"></textarea>
                    <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:0.3rem;">Target Audience</label>
                    <select id="swal-annTarget" class="swal2-select" style="margin:0 0 1rem 0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                        <option value="all">All Users</option>
                        <option value="student">Students Only</option>
                        <option value="departmentcoordinator">Coordinators Only</option>
                        <option value="studentaffairs">Student Affairs Only</option>
                    </select>
                    <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.9rem;">
                        <input type="checkbox" id="swal-annFeatured"> Mark as Featured
                    </label>
                </div>
            `,
                confirmButtonText: '<i class="ri-megaphone-line"></i> Post Announcement',
                confirmButtonColor: '#D97706',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                focusConfirm: false,
                preConfirm: () => {
                    const title = document.getElementById('swal-annTitle').value.trim();
                    const message = document.getElementById('swal-annMessage').value.trim();
                    if (!title || !message) {
                        Swal.showValidationMessage('Title and message are required');
                        return false;
                    }
                    return {
                        title: title,
                        message: message,
                        target: document.getElementById('swal-annTarget').value,
                        featured: document.getElementById('swal-annFeatured').checked
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="create_announcement">
                    <input type="hidden" name="announcementTitle" value="${escapeHtml(result.value.title)}">
                    <input type="hidden" name="announcementMessage" value="${escapeHtml(result.value.message)}">
                    <input type="hidden" name="targetRole" value="${result.value.target}">
                    ${result.value.featured ? '<input type="hidden" name="isFeatured" value="1">' : ''}
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.querySelectorAll('.confirm-delete-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const formEl = this;
                Swal.fire({
                    title: 'Delete Announcement?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) formEl.submit();
                });
            });
        });
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>