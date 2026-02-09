<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');
$userId = $_SESSION['user_id'];
$selectedMsgId = isset($_GET['msg']) ? intval($_GET['msg']) : null;

// Fetch inbox messages
$inboxStmt = mysqli_prepare($conn, "SELECT m.*, u.name as sender_name FROM messages m LEFT JOIN users u ON m.sender_id = u.id WHERE m.recipient_id = ? ORDER BY m.created_at DESC");
mysqli_stmt_bind_param($inboxStmt, "i", $userId);
mysqli_stmt_execute($inboxStmt);
$inboxMessages = mysqli_fetch_all(mysqli_stmt_get_result($inboxStmt), MYSQLI_ASSOC);
mysqli_stmt_close($inboxStmt);

// Fetch sent messages
$sentStmt = mysqli_prepare($conn, "SELECT m.*, u.name as recipient_name FROM messages m LEFT JOIN users u ON m.recipient_id = u.id WHERE m.sender_id = ? ORDER BY m.created_at DESC");
mysqli_stmt_bind_param($sentStmt, "i", $userId);
mysqli_stmt_execute($sentStmt);
$sentMessages = mysqli_fetch_all(mysqli_stmt_get_result($sentStmt), MYSQLI_ASSOC);
mysqli_stmt_close($sentStmt);

// If a message is selected, fetch it and mark as read
$selectedMessage = null;
if ($selectedMsgId) {
    $msgStmt = mysqli_prepare($conn, "SELECT m.*, u.name as sender_name FROM messages m LEFT JOIN users u ON m.sender_id = u.id WHERE m.id = ?");
    mysqli_stmt_bind_param($msgStmt, "i", $selectedMsgId);
    mysqli_stmt_execute($msgStmt);
    $selectedMessage = mysqli_fetch_assoc(mysqli_stmt_get_result($msgStmt));
    mysqli_stmt_close($msgStmt);

    if ($selectedMessage && $selectedMessage['recipient_id'] == $userId) {
        $readStmt = mysqli_prepare($conn, "UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?");
        mysqli_stmt_bind_param($readStmt, "ii", $selectedMsgId, $userId);
        mysqli_stmt_execute($readStmt);
        mysqli_stmt_close($readStmt);
    }
}

// Count unread messages
$unreadStmt = mysqli_prepare($conn, "SELECT COUNT(*) as unread FROM messages WHERE recipient_id = ? AND is_read = 0");
mysqli_stmt_bind_param($unreadStmt, "i", $userId);
mysqli_stmt_execute($unreadStmt);
$unreadCount = mysqli_fetch_assoc(mysqli_stmt_get_result($unreadStmt))['unread'];
mysqli_stmt_close($unreadStmt);

// Fetch all users for compose dropdown (except current user)
$usersStmt = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE id != ? ORDER BY name ASC");
mysqli_stmt_bind_param($usersStmt, "i", $userId);
mysqli_stmt_execute($usersStmt);
$allUsers = mysqli_fetch_all(mysqli_stmt_get_result($usersStmt), MYSQLI_ASSOC);
mysqli_stmt_close($usersStmt);

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
    <title>Messages | SPARK'26</title>
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
                    <h1>Messages</h1>
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
                    <div class="alert alert-success" style="background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <i class="ri-checkbox-circle-line"></i> <?php echo htmlspecialchars($successMsg); ?>
                    </div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-error" style="background:#fef2f2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($errorMsg); ?>
                    </div>
                <?php endif; ?>

                <div class="messages-container">
                    <div class="messages-sidebar">
                        <div class="messages-header">
                            <h3>Inbox <?php if ($unreadCount > 0): ?><span class="badge badge-primary"><?php echo $unreadCount; ?></span><?php endif; ?></h3>
                            <button class="btn-icon" title="Compose" onclick="openModal()">
                                <i class="ri-edit-line"></i>
                            </button>
                        </div>
                        <div class="messages-search">
                            <i class="ri-search-line"></i>
                            <input type="text" placeholder="Search messages...">
                        </div>
                        <div class="messages-list">
                            <?php if (count($inboxMessages) > 0): ?>
                                <?php foreach ($inboxMessages as $msg): ?>
                                    <a href="?msg=<?php echo $msg['id']; ?>" class="message-item <?php echo (!$msg['is_read']) ? 'unread' : ''; ?> <?php echo ($selectedMsgId == $msg['id']) ? 'active' : ''; ?>" style="display:block;padding:0.75rem 1rem;border-bottom:1px solid #e5e7eb;text-decoration:none;color:inherit;<?php echo (!$msg['is_read']) ? 'background:#eff6ff;font-weight:600;' : ''; ?><?php echo ($selectedMsgId == $msg['id']) ? 'background:#dbeafe;' : ''; ?>">
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                                            <span style="font-size:0.9rem;"><?php echo htmlspecialchars($msg['sender_name'] ?? 'Unknown'); ?></span>
                                            <span style="font-size:0.75rem;color:#6b7280;"><?php echo date('M d', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                        <div style="font-size:0.85rem;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?php echo htmlspecialchars(mb_strimwidth($msg['subject'] ?? '(No subject)', 0, 40, '...')); ?>
                                        </div>
                                        <?php if (!$msg['is_read']): ?>
                                            <span style="display:inline-block;width:8px;height:8px;background:#3b82f6;border-radius:50;position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);"></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state small">
                                    <i class="ri-mail-line"></i>
                                    <p>No messages yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="messages-content">
                        <?php if ($selectedMessage): ?>
                            <div class="message-view" style="padding:1.5rem;">
                                <div style="margin-bottom:1.5rem;border-bottom:1px solid #e5e7eb;padding-bottom:1rem;">
                                    <h3 style="margin:0 0 0.75rem 0;"><?php echo htmlspecialchars($selectedMessage['subject'] ?? '(No subject)'); ?></h3>
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <div>
                                            <span style="font-weight:600;">From:</span>
                                            <span><?php echo htmlspecialchars($selectedMessage['sender_name'] ?? 'Unknown'); ?></span>
                                        </div>
                                        <span style="font-size:0.85rem;color:#6b7280;"><?php echo date('M d, Y \a\t h:i A', strtotime($selectedMessage['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="message-body" style="line-height:1.7;color:#374151;white-space:pre-wrap;"><?php echo htmlspecialchars($selectedMessage['message']); ?></div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="ri-mail-open-line"></i>
                                <h3>No Message Selected</h3>
                                <p>Select a message from the list to view its contents</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="compose-modal" id="composeModal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>New Message</h3>
                            <button class="btn-icon" onclick="closeModal()">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <form action="sparkBackend.php" method="POST">
                            <input type="hidden" name="action" value="send_message">
                            <div class="form-group">
                                <label for="recipient">To</label>
                                <select id="recipient" name="recipient" required style="width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                                    <option value="">Select a recipient...</option>
                                    <?php foreach ($allUsers as $user): ?>
                                        <option value="<?php echo htmlspecialchars($user['email']); ?>">
                                            <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" placeholder="Enter subject">
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" rows="6" placeholder="Type your message here..."></textarea>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                                <button type="submit" class="btn-primary">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function openModal() {
            document.getElementById('composeModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('composeModal').style.display = 'none';
        }
    </script>
</body>

</html>
