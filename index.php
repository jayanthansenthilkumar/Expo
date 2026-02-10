<?php
require_once './includes/auth.php';
require_once 'db.php';

// Check access for public page (starts session, checks timeout, etc.)
checkUserAccess(true);

$isLoggedIn = isset($_SESSION['user_id']);
$dashboardLink = 'login.php';

if ($isLoggedIn) {
    switch ($_SESSION['role']) {
        case 'student':
            $dashboardLink = 'studentDashboard.php';
            break;
        case 'studentaffairs':
            $dashboardLink = 'studentAffairs.php';
            break;
        case 'departmentcoordinator':
            $dashboardLink = 'departmentCoordinator.php';
            break;
        case 'admin':
            $dashboardLink = 'sparkAdmin.php';
            break;
        default:
            $dashboardLink = 'login.php';
    }
}

// Dynamic stats from database
$totalStudents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'student'"))['cnt'];
$totalProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects"))['cnt'];
$approvedProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];
$totalDepartments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT department) as cnt FROM users WHERE department IS NOT NULL AND department != ''"))['cnt'];

// Load settings
$settingsResult = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = mysqli_fetch_assoc($settingsResult)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$eventName = $settings['event_name'] ?? "SPARK'26";
$eventDate = $settings['event_date'] ?? '2026-02-15';
$maxTeamSize = $settings['max_team_size'] ?? '4';
$registrationOpen = ($settings['registration_open'] ?? '1') === '1';

// Load schedule events from DB
$scheduleEvents = mysqli_query($conn, "SELECT * FROM schedule ORDER BY event_date ASC");

// Featured announcements for landing
$featuredAnn = mysqli_query($conn, "SELECT title, message FROM announcements WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 1");
$featuredAnnouncement = mysqli_fetch_assoc($featuredAnn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPARK'26 | Student Innovation Showcase</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Note: FontAwesome kit is a placeholder, strictly using CSS shapes/Unicode for icons if kit fails to avoid dependency issues -->
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                SPARK <span>'26</span>
            </a>
            <div class="nav-menu">
                <a href="#about" class="nav-link">About</a>
                <a href="#tracks" class="nav-link">Tracks</a>
                <a href="#schedule" class="nav-link">Schedule</a>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $dashboardLink; ?>" class="nav-link">Dashboard</a>
                    <a href="logout.php" class="nav-link" style="color: var(--primary);">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                <?php endif; ?>
            </div>
            <?php if (!$isLoggedIn): ?>
                <a href="register.php" class="btn-primary">Register Now</a>
            <?php else: ?>
                <a href="<?php echo $dashboardLink; ?>" class="btn-primary">Go to Console</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <span class="hero-chips">🚀 <?php echo date('M d, Y', strtotime($eventDate)); ?> • College Auditorium</span>
                <h1 class="hero-title">Where Student Ideas <br> <span class="text-gradient">Take Flight.</span></h1>
                <p class="hero-desc">
                    Join the largest annual gathering of student innovators, developers, and creators.
                    Showcase your final year project or first year prototype to industry leaders.
                </p>
                <div class="hero-actions">
                    <?php if (!$isLoggedIn): ?>
                        <a href="register.php" class="btn-primary">Submit Project</a>
                    <?php else: ?>
                        <a href="<?php echo $dashboardLink; ?>" class="btn-primary">Manage Projects</a>
                    <?php endif; ?>
                    <a href="#about" class="btn-outline">View Guidelines</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="shape-blob"></div>
                <div class="hero-img-card">
                    <div
                        style="position: relative; border-radius: 12px; overflow: hidden; height: 350px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <img src="https://images.unsplash.com/photo-1527977966376-1c8408f9f108?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80"
                            alt="Innovation Project" style="width: 100%; height: 100%; object-fit: cover;">
                        <div
                            style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); padding: 2rem 1.5rem 1rem; color: white;">
                            <div
                                style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); margin-bottom: 0.25rem;">
                                Featured Innovation</div>
                            <div style="font-weight: 700; font-size: 1.2rem;">Autonomous Agri-Drone</div>
                        </div>
                    </div>
                </div>
                <div class="floating-badge badge-1">
                    <span>⚡</span> 98% Efficiency
                </div>
                <div class="floating-badge badge-2">
                    <span>👥</span> 450+ Votes
                </div>
            </div>
        </div>
    </header>

    <!-- Stats Strip -->
    <div class="stats-strip">
        <div class="container stats-grid">
            <div class="stat-item">
                <h3 id="stat-stud" data-target="<?php echo $totalStudents; ?>">0</h3>
                <p>Students</p>
            </div>
            <div class="stat-item">
                <h3 id="stat-proj" data-target="<?php echo $totalProjects; ?>">0</h3>
                <p>Projects</p>
            </div>
            <div class="stat-item">
                <h3 id="stat-dept" data-target="<?php echo $totalDepartments; ?>">0</h3>
                <p>Departments</p>
            </div>
            <div class="stat-item">
                <h3 id="stat-approved" data-target="<?php echo $approvedProjects; ?>">0</h3>
                <p>Approved</p>
            </div>
        </div>
    </div>

    <!-- Features / About -->
    <section id="about" class="section about-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Why Join?</span>
                <h2 class="section-title">Elevate Your Engineering Journey</h2>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="f-icon">📢</div>
                    <h3>Industry Exposure</h3>
                    <p>Get evaluated by experts from top tech companies and receive constructive feedback.</p>
                </div>
                <div class="feature-card">
                    <div class="f-icon">🤝</div>
                    <h3>Collaborate</h3>
                    <p>Find teammates for future hackathons and network with peers from other departments.</p>
                </div>
                <div class="feature-card">
                    <div class="f-icon">🎯</div>
                    <h3>Placement Edge</h3>
                    <p>A winning project is a resume highlight that sets you apart during campus placements.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tracks -->
    <!-- Tracks Accordion -->
    <section id="tracks" class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Choose Your Domain</h2>
                <p style="color: var(--text-muted); margin-top: 1rem; font-size: 1.1rem;">Solutions can involve
                    Hardware, Software or a combination of both.</p>
            </div>

            <div class="track-container-accordion">
                <!-- Panel 1 -->
                <div class="track-panel tp-1 active">
                    <div class="track-number">1</div>
                    <div class="track-icon"><i class="ri-cube-line"></i></div>
                    <h3>Software Systems</h3>
                </div>
                <!-- Panel 2 (Active by default) -->
                <div class="track-panel tp-2">
                    <div class="track-number">2</div>
                    <div class="track-icon"><i class="ri-brain-line"></i></div>
                    <h3>Artificial Intelligence</h3>
                </div>
                <!-- Panel 3 -->
                <div class="track-panel tp-3">
                    <div class="track-number">3</div>
                    <div class="track-icon"><i class="ri-heartbeat-line"></i></div>
                    <h3>Health & MedTech</h3>
                </div>
                <!-- Panel 4 -->
                <div class="track-panel tp-4">
                    <div class="track-number">4</div>
                    <div class="track-icon"><i class="ri-leaf-line"></i></div>
                    <h3>Green Energy</h3>
                </div>
                <!-- Panel 5 -->
                <div class="track-panel tp-5">
                    <div class="track-number">5</div>
                    <div class="track-icon"><i class="ri-lightbulb-line"></i></div>
                    <h3>Open Innovation</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedule Cards -->
    <section id="schedule" class="section about-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Roadmap</span>
                <h2 class="section-title">Event Schedule</h2>
            </div>
            <div class="schedule-grid">
                <?php
                $now = time();
                $eventIndex = 0;
                $totalEvents = mysqli_num_rows($scheduleEvents);
                while ($event = mysqli_fetch_assoc($scheduleEvents)):
                    $eventIndex++;
                    $eventTimestamp = strtotime($event['event_date']);
                    $isPast = $eventTimestamp < $now;
                    $isToday = date('Y-m-d', $eventTimestamp) === date('Y-m-d');
                    $isLast = ($eventIndex === $totalEvents);

                    if ($isPast) {
                        $statusClass = 'open';
                        $statusText = 'Completed';
                    } elseif ($isToday) {
                        $statusClass = 'event';
                        $statusText = 'Today';
                    } else {
                        $statusClass = 'upcoming';
                        $statusText = 'Upcoming';
                    }

                    $highlightClass = $isLast ? ' highlight-card' : '';
                    if ($event['event_type'] === 'event' && !$isPast) {
                        $statusClass = 'event';
                        $statusText = ucfirst($event['event_type']);
                    }
                ?>
                <div class="schedule-card<?php echo $highlightClass; ?>">
                    <div class="sc-date">
                        <span class="sc-day"><?php echo date('d', $eventTimestamp); ?></span>
                        <span class="sc-month"><?php echo strtoupper(date('M', $eventTimestamp)); ?></span>
                    </div>
                    <div class="sc-content">
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p><?php echo htmlspecialchars($event['description'] ?? ''); ?></p>
                        <span class="sc-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                </div>
                <?php endwhile; ?>

                <?php if ($eventIndex === 0): ?>
                <div class="schedule-card">
                    <div class="sc-content">
                        <h3>Schedule Coming Soon</h3>
                        <p>Event schedule will be announced shortly. Stay tuned!</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Technical Startup Sponsors -->
    <section id="sponsors" class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Our Partners</span>
                <h2 class="section-title">Technical Startup Sponsors</h2>
            </div>
            <div class="sponsors-grid">
                <div class="sponsor-card">
                    <div class="sponsor-logo">
                        <i class="ri-cloud-line"></i>
                    </div>
                    <h3>CloudNine Tech</h3>
                    <p>Cloud Infrastructure</p>
                </div>
                <div class="sponsor-card">
                    <div class="sponsor-logo">
                        <i class="ri-robot-line"></i>
                    </div>
                    <h3>AI Nexus</h3>
                    <p>Artificial Intelligence</p>
                </div>
                <div class="sponsor-card">
                    <div class="sponsor-logo">
                        <i class="ri-code-s-slash-line"></i>
                    </div>
                    <h3>DevStack Labs</h3>
                    <p>Developer Tools</p>
                </div>
                <div class="sponsor-card">
                    <div class="sponsor-logo">
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <h3>CyberGuard</h3>
                    <p>Cybersecurity</p>
                </div>
                <div class="sponsor-card">
                    <div class="sponsor-logo">
                        <i class="ri-database-2-line"></i>
                    </div>
                    <h3>DataFlow Inc</h3>
                    <p>Data Analytics</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Detailed Guidelines (New Content) -->
    <section class="section">
        <div class="container">
            <div class="content-split-layout">
                <div class="content-text">
                    <span class="section-label">Rules & Regulations</span>
                    <h2 class="section-title">Participation Guidelines</h2>
                    <ul class="check-list">
                        <li>Each team must have a minimum of 2 and maximum of <?php echo $maxTeamSize; ?> members.</li>
                        <li>Inter-departmental teams are highly encouraged to promote multidisciplinary solutions.</li>
                        <li>Projects must be original. Plagiarism will lead to immediate disqualification.</li>
                        <li>Hardware projects must have a working prototype; software projects must have a live demo.
                        </li>
                        <li>All participants must wear their college ID cards during the event.</li>
                    </ul>
                </div>
                <div class="content-visual">
                    <div class="info-box">
                        <h3>Judges Criteria</h3>
                        <div class="criteria-row">
                            <span>Innovation</span>
                            <div class="bar">
                                <div style="width:30%"></div>
                            </div>
                        </div>
                        <div class="criteria-row">
                            <span>Feasibility</span>
                            <div class="bar">
                                <div style="width:25%"></div>
                            </div>
                        </div>
                        <div class="criteria-row">
                            <span>Presentation</span>
                            <div class="bar">
                                <div style="width:20%"></div>
                            </div>
                        </div>
                        <div class="criteria-row">
                            <span>Social Impact</span>
                            <div class="bar">
                                <div style="width:25%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section (New Content) -->
    <section class="section about-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Frequently Asked Questions</h2>
            </div>
            <div class="faq-grid">
                <div class="faq-item">
                    <h4>Who can participate?</h4>
                    <p>All students from 1st year to Final year of any department are eligible.</p>
                </div>
                <div class="faq-item">
                    <h4>Is there a registration fee?</h4>
                    <p>No, registration is completely free for all students of our college.</p>
                </div>
                <div class="faq-item">
                    <h4>Can I submit two projects?</h4>
                    <p>No, a student can be part of only one team/project.</p>
                </div>
                <div class="faq-item">
                    <h4>Will power supply be provided?</h4>
                    <p>Yes, each stall will have one 230V power socket. Please bring extension cords if needed.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section id="register" class="section">
        <div class="container">
            <div class="cta-box">
                <div class="cta-circle c-1"></div>
                <div class="cta-circle c-2"></div>
                <h2>Ready to Showcase?</h2>
                <p>Don't miss the chance to be recognized as the best innovator on campus.</p>
                <a href="register.php" class="btn-cta">Register Team</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <div class="f-brand">
                    <h2>SPARK'26</h2>
                    <p>Fostering a culture of innovation and engineering excellence among students.</p>
                </div>
                <div class="f-links">
                    <div class="f-col">
                        <h4>Links</h4>
                        <a href="#">Home</a>
                        <a href="#">Guidelines</a>
                        <a href="#">Past Winners</a>
                    </div>
                    <div class="f-col">
                        <h4>Contact</h4>
                        <a href="#">Email Coordinator</a>
                        <a href="#">Student Council</a>
                    </div>
                </div>
            </div>
            <div class="text-center" style="font-size: 0.9rem; color: #64748b;">
                &copy; 2026 College Innovation Council. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>

</html>