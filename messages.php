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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Messages';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="messages-container">
                    <div class="messages-sidebar">
                        <div class="messages-header">
                            <h3>Inbox <?php if ($unreadCount > 0): ?><span
                                        class="badge badge-primary"><?php echo $unreadCount; ?></span><?php endif; ?></h3>
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
                                    <a href="?msg=<?php echo $msg['id']; ?>"
                                        class="message-item <?php echo (!$msg['is_read']) ? 'unread' : ''; ?> <?php echo ($selectedMsgId == $msg['id']) ? 'active' : ''; ?>"
                                        style="display:block;padding:0.75rem 1rem;border-bottom:1px solid #e5e7eb;text-decoration:none;color:inherit;<?php echo (!$msg['is_read']) ? 'background:#eff6ff;font-weight:600;' : ''; ?><?php echo ($selectedMsgId == $msg['id']) ? 'background:#dbeafe;' : ''; ?>">
                                        <div
                                            style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                                            <span
                                                style="font-size:0.9rem;"><?php echo htmlspecialchars($msg['sender_name'] ?? 'Unknown'); ?></span>
                                            <span
                                                style="font-size:0.75rem;color:#6b7280;"><?php echo date('M d', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                        <div
                                            style="font-size:0.85rem;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?php echo htmlspecialchars(mb_strimwidth($msg['subject'] ?? '(No subject)', 0, 40, '...')); ?>
                                        </div>
                                        <?php if (!$msg['is_read']): ?>
                                            <span
                                                style="display:inline-block;width:8px;height:8px;background:#3b82f6;border-radius:50%;position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);"></span>
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
                                    <h3 style="margin:0 0 0.75rem 0;">
                                        <?php echo htmlspecialchars($selectedMessage['subject'] ?? '(No subject)'); ?></h3>
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <div>
                                            <span style="font-weight:600;">From:</span>
                                            <span><?php echo htmlspecialchars($selectedMessage['sender_name'] ?? 'Unknown'); ?></span>
                                        </div>
                                        <span
                                            style="font-size:0.85rem;color:#6b7280;"><?php echo date('M d, Y \a\t h:i A', strtotime($selectedMessage['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="message-body" style="line-height:1.7;color:#374151;white-space:pre-wrap;">
                                    <?php echo htmlspecialchars($selectedMessage['message']); ?></div>
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

                <!-- Compose modal handled via SweetAlert -->
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        const allUsers = <?php echo json_encode($allUsers); ?>;

        function openModal() {
            let userOptions = '<option value="">Select a recipient...</option>';
            allUsers.forEach(u => {
                userOptions += `<option value="${escapeHtml(u.email)}">${escapeHtml(u.name)} (${escapeHtml(u.email)})</option>`;
            });

            Swal.fire({
                title: 'New Message',
                html: `
                <div style="text-align:left;">
                    <div style="margin-bottom:0.75rem;">
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">To *</label>
                        <select id="swal-recipient" class="swal2-select" style="margin:0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">${userOptions}</select>
                    </div>
                    <div style="margin-bottom:0.75rem;">
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Subject</label>
                        <input id="swal-subject" class="swal2-input" placeholder="Enter subject" style="margin:0;width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Message *</label>
                        <textarea id="swal-message" class="swal2-textarea" rows="6" placeholder="Type your message here..." style="margin:0;width:100%;box-sizing:border-box;"></textarea>
                    </div>
                </div>
            `,
                confirmButtonText: '<i class="ri-send-plane-line"></i> Send Message',
                confirmButtonColor: '#2563eb',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                width: Math.min(550, window.innerWidth - 40) + 'px',
                focusConfirm: false,
                preConfirm: () => {
                    const recipient = document.getElementById('swal-recipient').value;
                    const message = document.getElementById('swal-message').value.trim();
                    if (!recipient || !message) {
                        Swal.showValidationMessage('Recipient and message are required');
                        return false;
                    }
                    return {
                        recipient: recipient,
                        subject: document.getElementById('swal-subject').value.trim(),
                        message: message
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const d = result.value;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="recipient" value="${escapeHtml(d.recipient)}">
                    <input type="hidden" name="subject" value="${escapeHtml(d.subject)}">
                    <input type="hidden" name="message" value="${escapeHtml(d.message)}">
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

        <?php if ($successMsg): ?>
            Swal.fire({ icon: 'success', title: 'Sent!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#2563eb' });
        <?php endif; ?>
    </script>

    <?php include 'includes/bot.php'; ?>
</body>

</html>