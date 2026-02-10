<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'User');

// Load settings for dynamic values
$settingsResult = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = mysqli_fetch_assoc($settingsResult)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$eventName = $settings['event_name'] ?? "SPARK'26";
$maxTeamSize = $settings['max_team_size'] ?? '4';
$submissionDeadline = $settings['submission_deadline'] ?? '2026-02-15 23:59:00';
$registrationOpen = ($settings['registration_open'] ?? '1') === '1';

// Get real stats
$totalProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects"))['cnt'];
$approvedProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];
$totalStudents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'student'"))['cnt'];

// Get upcoming schedule events
$upcomingEvents = mysqli_query($conn, "SELECT * FROM schedule WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guidelines | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php
            $pageTitle = 'Guidelines';
            include 'includes/header.php';
            ?>

            <div class="dashboard-content">
                <div class="guidelines-container">
                    <!-- Quick Stats Bar -->
                    <div class="stats-grid" style="margin-bottom: 2rem;">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="ri-calendar-line"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo date('M d, Y', strtotime($submissionDeadline)); ?></h3>
                                <p>Submission Deadline</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="ri-team-line"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Max <?php echo $maxTeamSize; ?></h3>
                                <p>Team Members</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon amber">
                                <i class="ri-folder-line"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $totalProjects; ?></h3>
                                <p>Projects Submitted</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon <?php echo $registrationOpen ? 'green' : 'red'; ?>">
                                <i class="ri-<?php echo $registrationOpen ? 'check' : 'close'; ?>-circle-line"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $registrationOpen ? 'Open' : 'Closed'; ?></h3>
                                <p>Registration Status</p>
                            </div>
                        </div>
                    </div>

                    <div class="guidelines-grid">
                        <div class="guideline-section">
                            <h2><i class="ri-information-line"></i> General Guidelines</h2>
                            <ul>
                                <li>All projects must be original work created by the team members</li>
                                <li>Teams can have a maximum of <?php echo $maxTeamSize; ?> members</li>
                                <li>Each participant can only be part of one team</li>
                                <li>Projects must be submitted before the deadline:
                                    <strong><?php echo date('F d, Y \a\t h:i A', strtotime($submissionDeadline)); ?></strong>
                                </li>
                                <li>All team members must be currently enrolled students</li>
                            </ul>
                        </div>

                        <div class="guideline-section">
                            <h2><i class="ri-folder-line"></i> Project Requirements</h2>
                            <ul>
                                <li>Project must have a clear problem statement and solution</li>
                                <li>Documentation must include project overview, architecture, and usage instructions
                                </li>
                                <li>Source code must be submitted via GitHub repository</li>
                                <li>A working demo or prototype is mandatory</li>
                                <li>Projects should be innovative and practical</li>
                                <li>Only PDF files are accepted for documentation upload (max 10MB)</li>
                            </ul>
                        </div>

                        <div class="guideline-section">
                            <h2><i class="ri-presentation-line"></i> Presentation Guidelines</h2>
                            <ul>
                                <li>Presentations should be 10-15 minutes long</li>
                                <li>Include live demo of your project</li>
                                <li>Be prepared for Q&A session</li>
                                <li>All team members should participate in the presentation</li>
                                <li>Presentation slides should be submitted 24 hours before</li>
                            </ul>
                        </div>

                        <div class="guideline-section">
                            <h2><i class="ri-award-line"></i> Judging Criteria</h2>
                            <ul>
                                <li><strong>Innovation (25%)</strong> - Uniqueness and creativity of the solution</li>
                                <li><strong>Technical Complexity (25%)</strong> - Technical implementation and
                                    architecture
                                </li>
                                <li><strong>Practicality (20%)</strong> - Real-world applicability and impact</li>
                                <li><strong>Presentation (15%)</strong> - Quality of presentation and demo</li>
                                <li><strong>Documentation (15%)</strong> - Code quality and documentation</li>
                            </ul>
                        </div>

                        <?php if (mysqli_num_rows($upcomingEvents) > 0): ?>
                            <div class="guideline-section">
                                <h2><i class="ri-calendar-event-line"></i> Upcoming Deadlines</h2>
                                <ul>
                                    <?php while ($event = mysqli_fetch_assoc($upcomingEvents)): ?>
                                        <li>
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong> &mdash;
                                            <?php echo date('F d, Y \a\t h:i A', strtotime($event['event_date'])); ?>
                                            <?php if (!empty($event['description'])): ?>
                                                <br><span
                                                    style="color: var(--text-muted); font-size: 0.9rem;"><?php echo htmlspecialchars($event['description']); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>