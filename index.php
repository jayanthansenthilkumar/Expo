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
            <a href="#" class="logo">
                <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
                SPARK <span>'26</span>
            </a>
            <div class="nav-menu">
                <a href="#about" class="nav-link">About</a>
                <a href="#tracks" class="nav-link">Tracks</a>
                <a href="#schedule" class="nav-link">Schedule</a>
                <a href="#sponsors" class="nav-link">Sponsors</a>
            </div>
            <a href="#register" class="btn-primary">Register Now</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <span class="hero-chips">🚀 Feb 15, 2026 • College Auditorium</span>
                <h1 class="hero-title">Where Student Ideas <br> <span class="text-gradient">Take Flight.</span></h1>
                <p class="hero-desc">
                    Join the largest annual gathering of student innovators, developers, and creators.
                    Showcase your final year project or first year prototype to industry leaders.
                </p>
                <div class="hero-actions">
                    <a href="#register" class="btn-primary">Submit Project</a>
                    <a href="#about" class="btn-outline">View Guidelines</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="shape-blob"></div>
                <div class="hero-img-card">
                    <div style="position: relative; border-radius: 12px; overflow: hidden; height: 350px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <img src="https://images.unsplash.com/photo-1527977966376-1c8408f9f108?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Innovation Project" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); padding: 2rem 1.5rem 1rem; color: white;">
                             <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); margin-bottom: 0.25rem;">Featured Innovation</div>
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
                <h3 id="stat-stud">0</h3>
                <p>Students</p>
            </div>
            <div class="stat-item">
                <h3 id="stat-proj">0</h3>
                <p>Projects</p>
            </div>
            <div class="stat-item">
                <h3 id="stat-prize">0</h3>
                <p>Prize Pool</p>
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
                    <div class="f-icon">�</div>
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
                <p style="color: var(--text-muted); margin-top: 1rem; font-size: 1.1rem;">Solutions can involve Hardware, Software or a combination of both.</p>
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
                <!-- Card 1 -->
                <div class="schedule-card">
                    <div class="sc-date">
                        <span class="sc-day">10</span>
                        <span class="sc-month">FEB</span>
                    </div>
                    <div class="sc-content">
                        <h3>Registration Opens</h3>
                        <p>Portal opens for team registration. Form your teams (max 4 members) and start drafting your
                            abstract.</p>
                        <span class="sc-status open">Open Now</span>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="schedule-card">
                    <div class="sc-date">
                        <span class="sc-day">25</span>
                        <span class="sc-month">FEB</span>
                    </div>
                    <div class="sc-content">
                        <h3>Abstract Submission</h3>
                        <p>Deadline to submit your project abstract. Ensure it covers the problem statement and proposed
                            solution clearly.</p>
                        <span class="sc-status upcoming">Upcoming</span>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="schedule-card">
                    <div class="sc-date">
                        <span class="sc-day">05</span>
                        <span class="sc-month">MAR</span>
                    </div>
                    <div class="sc-content">
                        <h3>Shortlist & Mentoring</h3>
                        <p>Selected teams announced. Assigned mentors will guide you to refine the project before the
                            finale.</p>
                        <span class="sc-status upcoming">Upcoming</span>
                    </div>
                </div>
                <!-- Card 4 -->
                <div class="schedule-card highlight-card">
                    <div class="sc-date">
                        <span class="sc-day">15</span>
                        <span class="sc-month">MAR</span>
                    </div>
                    <div class="sc-content">
                        <h3>Grand Expo Day</h3>
                        <p>8:00 AM - Stall Setup<br>10:00 AM - Judging Begins<br>4:00 PM - Valedictory & Prize
                            Distribution</p>
                        <span class="sc-status event">Main Event</span>
                    </div>
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
                        <li>Each team must have a minimum of 2 and maximum of 4 members.</li>
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
                <a href="#" class="btn-cta">Register Team</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <div class="f-brand">
                    <h2>EXPO 2026</h2>
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