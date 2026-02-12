<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');
$role = $_SESSION['role'];
$canManage = in_array($role, ['admin', 'studentaffairs']);

// Fetch schedule events
$events = [];
$result = mysqli_query($conn, "SELECT * FROM schedule ORDER BY event_date ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $events[] = $row;
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
    <title>Schedule | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Event Schedule';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">

                <div class="schedule-container">
                    <div class="schedule-header">
                        <div>
                            <h2>SPARK'26 Timeline</h2>
                            <p>Important dates and deadlines for the event</p>
                        </div>
                        <?php if ($canManage): ?>
                            <button class="btn-primary" onclick="showAddEvent()">
                                <i class="ri-add-line"></i> Add Event
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="timeline">
                        <?php if (empty($events)): ?>
                            <div class="empty-state">
                                <i class="ri-calendar-line"></i>
                                <h3>No Events Scheduled</h3>
                                <p>No events have been added to the schedule yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($events as $event):
                                $isPast = strtotime($event['event_date']) < time();
                                $isUpcoming = !$isPast && strtotime($event['event_date']) < time() + (7 * 86400);
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?php echo $isUpcoming ? 'upcoming' : ($isPast ? '' : ''); ?>">
                                    </div>
                                    <div class="timeline-content">
                                        <span
                                            class="timeline-date"><?php echo date('F j, Y - g:i A', strtotime($event['event_date'])); ?></span>
                                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                        <p><?php echo htmlspecialchars($event['description']); ?></p>
                                        <span
                                            style="font-size:0.75rem;padding:0.15rem 0.5rem;border-radius:10px;background:var(--bg-surface);color:var(--text-muted);"><?php echo ucfirst($event['event_type']); ?></span>
                                        <?php if ($canManage): ?>
                                            <form action="sparkBackend.php" method="POST" style="display:inline;margin-left:0.5rem;"
                                                class="confirm-delete-form">
                                                <input type="hidden" name="action" value="delete_schedule">
                                                <input type="hidden" name="schedule_id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" class="btn-icon" style="color:#ef4444;font-size:0.8rem;"><i
                                                        class="ri-delete-bin-line"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($canManage): ?>
                    <!-- Schedule modal handled via SweetAlert -->
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function showAddEvent() {
            Swal.fire({
                title: 'Add Schedule Event',
                html: `
                <div style="text-align:left;">
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Event Title *</label>
                        <input id="swal-eTitle" class="swal2-input" placeholder="Event title" style="margin:0;width:100%;box-sizing:border-box;">
                    </div>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Description</label>
                        <textarea id="swal-eDesc" class="swal2-textarea" placeholder="Event description" style="margin:0;width:100%;box-sizing:border-box;"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Date & Time *</label>
                        <input id="swal-eDate" type="datetime-local" class="swal2-input" style="margin:0;width:100%;box-sizing:border-box;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-weight:600;font-size:0.85rem;display:block;margin-bottom:0.3rem;">Event Type</label>
                        <select id="swal-eType" class="swal2-select" style="margin:0;width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                            <option value="general">General</option>
                            <option value="milestone">Milestone</option>
                            <option value="deadline">Deadline</option>
                            <option value="event">Event</option>
                        </select>
                    </div>
                </div>
            `,
                confirmButtonText: '<i class="ri-calendar-event-line"></i> Add Event',
                confirmButtonColor: '#D97706',
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                width: Math.min(500, window.innerWidth - 40) + 'px',
                focusConfirm: false,
                preConfirm: () => {
                    const title = document.getElementById('swal-eTitle').value.trim();
                    const date = document.getElementById('swal-eDate').value;
                    if (!title || !date) {
                        Swal.showValidationMessage('Title and Date are required');
                        return false;
                    }
                    return {
                        title: title,
                        description: document.getElementById('swal-eDesc').value.trim(),
                        date: date,
                        type: document.getElementById('swal-eType').value
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const d = result.value;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'sparkBackend.php';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="add_schedule">
                    <input type="hidden" name="eventTitle" value="${escapeHtml(d.title)}">
                    <input type="hidden" name="eventDescription" value="${escapeHtml(d.description)}">
                    <input type="hidden" name="eventDate" value="${escapeHtml(d.date)}">
                    <input type="hidden" name="eventType" value="${escapeHtml(d.type)}">
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
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo addslashes($successMsg); ?>', confirmButtonColor: '#D97706', timer: 3000, timerProgressBar: true });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            Swal.fire({ icon: 'error', title: 'Oops!', text: '<?php echo addslashes($errorMsg); ?>', confirmButtonColor: '#D97706' });
        <?php endif; ?>

        document.querySelectorAll('.confirm-delete-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const formEl = this;
                Swal.fire({
                    title: 'Delete Event?',
                    text: 'This scheduled event will be permanently removed.',
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