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
    <title>SPARK'26 | Technology Project Expo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body class="tech-landing">
    <!-- Particle Canvas Background -->
    <canvas id="particleCanvas"></canvas>

    <!-- Scanline Overlay -->
    <div class="scanline-overlay"></div>

    <!-- Navbar -->
    <nav class="navbar tech-navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo tech-logo">
                <span class="logo-bracket">&lt;</span>SPARK<span class="logo-accent">'26</span><span class="logo-bracket">/&gt;</span>
            </a>
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleMobileMenu()" aria-label="Toggle menu">
                <i class="ri-menu-line" id="menuIcon"></i>
            </button>
            <div class="nav-links-desktop">
                <a href="#about" class="nav-link tech-nav-link">About</a>
                <a href="#tracks" class="nav-link tech-nav-link">Tracks</a>
                <a href="#schedule" class="nav-link tech-nav-link">Schedule</a>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $dashboardLink; ?>" class="nav-link tech-nav-link">Dashboard</a>
                    <a href="logout.php" class="nav-link tech-nav-link" style="color: #D97706;">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link tech-nav-link">Login</a>
                    <a href="register.php" class="btn-tech-primary sfx-btn">Register Now</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay tech-mobile-menu" id="navMenu">
        <button class="mobile-menu-close" onclick="toggleMobileMenu()" aria-label="Close menu">
            <i class="ri-close-line"></i>
        </button>
        <a href="#about" class="nav-link" onclick="toggleMobileMenu()">About</a>
        <a href="#tracks" class="nav-link" onclick="toggleMobileMenu()">Tracks</a>
        <a href="#schedule" class="nav-link" onclick="toggleMobileMenu()">Schedule</a>
        <?php if ($isLoggedIn): ?>
            <a href="<?php echo $dashboardLink; ?>" class="nav-link">Dashboard</a>
            <a href="logout.php" class="nav-link" style="color: #D97706;">Logout</a>
        <?php else: ?>
            <a href="login.php" class="nav-link">Login</a>
            <a href="register.php" class="btn-tech-primary sfx-btn">Register Now</a>
        <?php endif; ?>
    </div>

    <!-- Hero Section -->
    <header class="hero tech-hero">
        <div class="hero-grid-bg"></div>
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="hero-tag">
                    <span class="tag-dot"></span>
                    <span class="typing-text" data-text="Spark'26 - Welcomes You"></span>
                    <span class="cursor-blink">|</span>
                </div>
                <h1 class="hero-title tech-title">
                    <span class="line-reveal">Where Innovation</span><br>
                    <span class="line-reveal delay-1">Meets <span class="glitch-text" data-text="Technology.">Technology.</span></span>
                </h1>
                <p class="hero-desc tech-desc">
                    The ultimate stage for student innovators, developers & engineers.
                    Present your groundbreaking projects to industry leaders & win big.
                </p>

                <!-- Countdown Timer -->
                <div class="countdown-strip" id="countdown">
                    <div class="cd-item">
                        <span class="cd-num" id="cd-days">00</span>
                        <span class="cd-label">Days</span>
                    </div>
                    <div class="cd-sep">:</div>
                    <div class="cd-item">
                        <span class="cd-num" id="cd-hours">00</span>
                        <span class="cd-label">Hours</span>
                    </div>
                    <div class="cd-sep">:</div>
                    <div class="cd-item">
                        <span class="cd-num" id="cd-mins">00</span>
                        <span class="cd-label">Mins</span>
                    </div>
                    <div class="cd-sep">:</div>
                    <div class="cd-item">
                        <span class="cd-num" id="cd-secs">00</span>
                        <span class="cd-label">Secs</span>
                    </div>
                </div>

                <div class="hero-actions">
                    <?php if (!$isLoggedIn): ?>
                        <a href="register.php" class="btn-tech-primary sfx-btn"><i class="ri-rocket-line"></i> Submit Project</a>
                    <?php else: ?>
                        <a href="<?php echo $dashboardLink; ?>" class="btn-tech-primary sfx-btn"><i class="ri-dashboard-line"></i> Manage Projects</a>
                    <?php endif; ?>
                    <a href="#about" class="btn-tech-outline sfx-btn"><i class="ri-book-open-line"></i> View Guidelines</a>
                </div>
            </div>
            <div class="hero-visual tech-visual">
                <div class="hologram-ring ring-1"></div>
                <div class="hologram-ring ring-2"></div>
                <div class="hologram-ring ring-3"></div>
                <div class="tech-orb">
                    <div class="orb-inner">
                        <i class="ri-code-s-slash-line"></i>
                    </div>
                </div>
                <div class="floating-badge tech-badge badge-1">
                    <i class="ri-flashlight-line"></i> <span>98% Efficiency</span>
                </div>
                <div class="floating-badge tech-badge badge-2">
                    <i class="ri-team-line"></i> <span><?php echo $totalStudents; ?>+ Registered</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Stats Strip -->
    <div class="stats-strip tech-stats">
        <div class="container stats-grid">
            <div class="stat-item tech-stat">
                <div class="stat-icon"><i class="ri-user-line"></i></div>
                <h3 id="stat-stud" data-target="<?php echo $totalStudents; ?>">0</h3>
                <p>Students</p>
            </div>
            <div class="stat-item tech-stat">
                <div class="stat-icon"><i class="ri-folder-line"></i></div>
                <h3 id="stat-proj" data-target="<?php echo $totalProjects; ?>">0</h3>
                <p>Projects</p>
            </div>
            <div class="stat-item tech-stat">
                <div class="stat-icon"><i class="ri-building-line"></i></div>
                <h3 id="stat-dept" data-target="<?php echo $totalDepartments; ?>">0</h3>
                <p>Departments</p>
            </div>
            <div class="stat-item tech-stat">
                <div class="stat-icon"><i class="ri-checkbox-circle-line"></i></div>
                <h3 id="stat-approved" data-target="<?php echo $approvedProjects; ?>">0</h3>
                <p>Approved</p>
            </div>
        </div>
    </div>

    <!-- Features / About -->
    <section id="about" class="section tech-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label tech-label"><i class="ri-terminal-box-line"></i> Why Join?</span>
                <h2 class="section-title">Elevate Your Engineering Journey</h2>
            </div>
            <div class="features-grid">
                <div class="feature-card tech-card">
                    <div class="card-glow"></div>
                    <div class="f-icon tech-icon"><i class="ri-megaphone-line"></i></div>
                    <h3>Industry Exposure</h3>
                    <p>Get evaluated by experts from top tech companies and receive constructive feedback.</p>
                </div>
                <div class="feature-card tech-card">
                    <div class="card-glow"></div>
                    <div class="f-icon tech-icon"><i class="ri-links-line"></i></div>
                    <h3>Collaborate</h3>
                    <p>Find teammates for future hackathons and network with peers from other departments.</p>
                </div>
                <div class="feature-card tech-card">
                    <div class="card-glow"></div>
                    <div class="f-icon tech-icon"><i class="ri-trophy-line"></i></div>
                    <h3>Placement Edge</h3>
                    <p>A winning project is a resume highlight that sets you apart during campus placements.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tracks Accordion -->
    <section id="tracks" class="section tech-section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-label tech-label"><i class="ri-code-box-line"></i> Domains</span>
                <h2 class="section-title">Choose Your Domain</h2>
                <p class="section-sub">Solutions can involve Hardware, Software or a combination of both.</p>
            </div>

            <div class="track-container-accordion">
                <div class="track-panel tp-1 active">
                    <div class="track-number">1</div>
                    <div class="track-icon"><i class="ri-cube-line"></i></div>
                    <h3>Software Systems</h3>
                </div>
                <div class="track-panel tp-2">
                    <div class="track-number">2</div>
                    <div class="track-icon"><i class="ri-brain-line"></i></div>
                    <h3>Artificial Intelligence</h3>
                </div>
                <div class="track-panel tp-3">
                    <div class="track-number">3</div>
                    <div class="track-icon"><i class="ri-heartbeat-line"></i></div>
                    <h3>Health & MedTech</h3>
                </div>
                <div class="track-panel tp-4">
                    <div class="track-number">4</div>
                    <div class="track-icon"><i class="ri-leaf-line"></i></div>
                    <h3>Green Energy</h3>
                </div>
                <div class="track-panel tp-5">
                    <div class="track-number">5</div>
                    <div class="track-icon"><i class="ri-lightbulb-line"></i></div>
                    <h3>Open Innovation</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedule Cards -->
    <section id="schedule" class="section tech-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label tech-label"><i class="ri-calendar-todo-line"></i> Roadmap</span>
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
                    <div class="schedule-card tech-schedule-card<?php echo $highlightClass; ?>">
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
                    <div class="schedule-card tech-schedule-card">
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
    <section id="sponsors" class="section tech-section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-label tech-label"><i class="ri-hand-heart-line"></i> Our Partners</span>
                <h2 class="section-title">Technical Startup Sponsors</h2>
            </div>
            <div class="sponsors-grid">
                <div class="sponsor-card tech-sponsor">
                    <div class="sponsor-logo"><i class="ri-building-2-line"></i></div>
                    <h3>Syraa Groups</h3>
                    <p>Technical Partner</p>
                </div>
                <div class="sponsor-card tech-sponsor">
                    <div class="sponsor-logo"><i class="ri-book-open-line"></i></div>
                    <h3>FewInfos</h3>
                    <p>Knowledge Partner</p>
                </div>
                <div class="sponsor-card tech-sponsor">
                    <div class="sponsor-logo"><i class="ri-code-s-slash-line"></i></div>
                    <h3>Prisol Technologies</h3>
                    <p>Tech Solution Partner</p>
                </div>
                <div class="sponsor-card tech-sponsor">
                    <div class="sponsor-logo"><i class="ri-rocket-line"></i></div>
                    <h3>AFDC</h3>
                    <p>Startup Partner</p>
                </div>
                <div class="sponsor-card tech-sponsor">
                    <div class="sponsor-logo"><i class="ri-hand-heart-line"></i></div>
                    <h3>SignBridge AI</h3>
                    <p>Accessibility Partner</p>
                </div>
                <div class="sponsor-card tech-sponsor">
                    <div class="sponsor-logo"><i class="ri-flashlight-line"></i></div>
                    <h3>Nexis</h3>
                    <p>Startup Partner</p>
                </div>
                <div class="sponsor-card tech-sponsor">
                    <div class="sponsor-logo"><i class="ri-building-4-line"></i></div>
                    <h3>SENSAN</h3>
                    <p>Startup Partner</p>
                </div>
                <div class="sponsor-card tech-sponsor">
                    <div class="sponsor-logo"><i class="ri-brain-line"></i></div>
                    <h3>Thinkloop AI</h3>
                    <p>AI Innovation Partner</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Detailed Guidelines -->
    <section class="section tech-section">
        <div class="container">
            <div class="content-split-layout">
                <div class="content-text">
                    <span class="section-label tech-label"><i class="ri-shield-check-line"></i> Rules & Regulations</span>
                    <h2 class="section-title">Participation Guidelines</h2>
                    <ul class="check-list tech-checklist">
                        <li>Each team must have a minimum of 2 and maximum of <?php echo $maxTeamSize; ?> members.</li>
                        <li>Inter-departmental teams are highly encouraged to promote multidisciplinary solutions.</li>
                        <li>Projects must be original. Plagiarism will lead to immediate disqualification.</li>
                        <li>Hardware projects must have a working prototype; software projects must have a live demo.</li>
                        <li>All participants must wear their college ID cards during the event.</li>
                    </ul>
                </div>
                <div class="content-visual">
                    <div class="info-box tech-info-box">
                        <h3><i class="ri-bar-chart-box-line"></i> Judges Criteria</h3>
                        <div class="criteria-row">
                            <span>Innovation</span>
                            <div class="bar"><div class="bar-fill" style="--bar-width:30%"></div></div>
                        </div>
                        <div class="criteria-row">
                            <span>Feasibility</span>
                            <div class="bar"><div class="bar-fill" style="--bar-width:25%"></div></div>
                        </div>
                        <div class="criteria-row">
                            <span>Presentation</span>
                            <div class="bar"><div class="bar-fill" style="--bar-width:20%"></div></div>
                        </div>
                        <div class="criteria-row">
                            <span>Social Impact</span>
                            <div class="bar"><div class="bar-fill" style="--bar-width:25%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section tech-section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-label tech-label"><i class="ri-question-line"></i> Help</span>
                <h2 class="section-title">Frequently Asked Questions</h2>
            </div>
            <div class="faq-grid">
                <div class="faq-item tech-faq">
                    <h4><i class="ri-user-star-line"></i> Who can participate?</h4>
                    <p>All students from 1st year to Final year of any department are eligible.</p>
                </div>
                <div class="faq-item tech-faq">
                    <h4><i class="ri-money-dollar-circle-line"></i> Is there a registration fee?</h4>
                    <p>No, registration is completely free for all students of our college.</p>
                </div>
                <div class="faq-item tech-faq">
                    <h4><i class="ri-file-copy-line"></i> Can I submit two projects?</h4>
                    <p>No, a student can be part of only one team/project.</p>
                </div>
                <div class="faq-item tech-faq">
                    <h4><i class="ri-plug-line"></i> Will power supply be provided?</h4>
                    <p>Yes, each stall will have one 230V power socket. Please bring extension cords if needed.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section id="register" class="section">
        <div class="container">
            <div class="cta-box tech-cta">
                <div class="cta-grid-bg"></div>
                <div class="cta-circle c-1"></div>
                <div class="cta-circle c-2"></div>
                <h2>Ready to <span class="glitch-text" data-text="Innovate?">Innovate?</span></h2>
                <p>Don't miss the chance to be recognized as the best innovator on campus.</p>
                <a href="register.php" class="btn-cta sfx-btn"><i class="ri-rocket-2-line"></i> Register Team</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer tech-footer">
        <div class="container">
            <div class="footer-top">
                <div class="f-brand">
                    <h2><span class="logo-bracket">&lt;</span>SPARK'26<span class="logo-bracket">/&gt;</span></h2>
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
            <div class="text-center" style="font-size: 0.9rem; color: #A8A29E;">
                &copy; 2026 Syraa Groups. All rights reserved. | Hosted by HariX
            </div>
        </div>
    </footer>

    <!-- Syraa AI Chat Widget -->
    <?php include 'includes/bot.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    <script>window.SPARK_EVENT_DATE = '<?php echo $eventDate; ?>';</script>
    <script src="assets/js/landing.js"></script>
</body>

</html>