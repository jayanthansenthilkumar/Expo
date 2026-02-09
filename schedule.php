<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');
$role = $_SESSION['role'];
$canManage = in_array($role, ['admin']);

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
                    <h1>Event Schedule</h1>
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
                <?php if ($successMsg): ?>
                    <div style="background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;margin-bottom:1rem;"><i class="ri-checkbox-circle-line"></i> <?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div style="background:#fef2f2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;"><i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

                <div class="schedule-container">
                    <div class="schedule-header">
                        <div>
                            <h2>SPARK'26 Timeline</h2>
                            <p>Important dates and deadlines for the event</p>
                        </div>
                        <?php if ($canManage): ?>
                        <button class="btn-primary" onclick="document.getElementById('scheduleModal').style.display='flex'">
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
                                <div class="timeline-marker <?php echo $isUpcoming ? 'upcoming' : ($isPast ? '' : ''); ?>"></div>
                                <div class="timeline-content">
                                    <span class="timeline-date"><?php echo date('F j, Y - g:i A', strtotime($event['event_date'])); ?></span>
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($event['description']); ?></p>
                                    <span style="font-size:0.75rem;padding:0.15rem 0.5rem;border-radius:10px;background:var(--bg-surface);color:var(--text-muted);"><?php echo ucfirst($event['event_type']); ?></span>
                                    <?php if ($canManage): ?>
                                    <form action="sparkBackend.php" method="POST" style="display:inline;margin-left:0.5rem;" onsubmit="return confirm('Remove this event?');">
                                        <input type="hidden" name="action" value="delete_schedule">
                                        <input type="hidden" name="schedule_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" class="btn-icon" style="color:#ef4444;font-size:0.8rem;"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($canManage): ?>
                <!-- Add Event Modal -->
                <div class="compose-modal" id="scheduleModal" style="display:none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Add Schedule Event</h3>
                            <button class="btn-icon" onclick="document.getElementById('scheduleModal').style.display='none'"><i class="ri-close-line"></i></button>
                        </div>
                        <form action="sparkBackend.php" method="POST">
                            <input type="hidden" name="action" value="add_schedule">
                            <div class="form-group">
                                <label>Event Title</label>
                                <input type="text" name="eventTitle" required placeholder="Event title">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="eventDescription" rows="3" placeholder="Event description"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Date & Time</label>
                                <input type="datetime-local" name="eventDate" required>
                            </div>
                            <div class="form-group">
                                <label>Event Type</label>
                                <select name="eventType">
                                    <option value="general">General</option>
                                    <option value="milestone">Milestone</option>
                                    <option value="deadline">Deadline</option>
                                    <option value="event">Event</option>
                                </select>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="document.getElementById('scheduleModal').style.display='none'">Cancel</button>
                                <button type="submit" class="btn-primary">Add Event</button>
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
