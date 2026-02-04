<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart City Waste System | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .proj-banner {
            height: 350px;
            background: #1e293b;
            position: relative;
            overflow: hidden;
        }
        .proj-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.6;
        }
        .proj-header-content {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            padding: 3rem 0 1.5rem;
            color: white;
        }
        
        .p-stat {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }
        @media (max-width: 768px) {
            .details-grid { grid-template-columns: 1fr; }
        }

        .vote-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            position: sticky;
            top: 100px;
            text-align: center;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
                SPARK <span>'26</span>
            </a>
            <div class="nav-menu">
                <a href="index.php" class="nav-link">Tracks</a>
                <a href="index.php#register" class="nav-link">Register</a>
            </div>
            <a href="index.php" class="btn-outline">Back to Expo</a>
        </div>
    </nav>

    <!-- Banner -->
    <div class="proj-banner" style="margin-top: 60px;"> <!-- Margin for fixed nav -->
        <img src="https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Project Cover">
        <div class="proj-header-content">
            <div class="container">
                <div style="margin-bottom: 0.5rem; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; color: var(--accent); font-weight: 700;">IoT & Smart Cities</div>
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Smart City Waste Management System</h1>
                <div>
                    <span class="p-stat"><i class="ri-eye-line"></i> 1.2k Views</span>
                    <span class="p-stat"><i class="ri-heart-line"></i> 450 Likes</span>
                    <span class="p-stat"><i class="ri-university-line"></i> Computer Science Dept</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="container section" style="padding-top: 1rem;">
        <div class="details-grid">
            
            <!-- Left: Description -->
            <div>
                <h3 style="margin-bottom: 1rem;">About the Innovation</h3>
                <p style="margin-bottom: 1.5rem; color: #475569; line-height: 1.7;">
                    The rapid growth of urban populations has made traditional waste management inefficient. Overflowing bins create health hazards, while fixed collection routes waste fuel. Our system solves this by embedding ultrasonic sensors in bins to measure fill levels in real-time.
                </p>
                <p style="margin-bottom: 2rem; color: #475569; line-height: 1.7;">
                    Data is transmitted via LoRaWAN to a cloud dashboard where waste collection agencies can monitor status. An AI algorithm then generates the most optimized route for garbage trucks, prioritizing only full bins.
                </p>

                <h4 style="margin-bottom: 1rem;">Tech Stack</h4>
                <div style="margin-bottom: 2rem;">
                    <span style="display:inline-block; padding:0.3rem 0.8rem; background:#e2e8f0; border-radius:6px; margin-right:0.5rem; margin-bottom:0.5rem; font-size:0.9rem;">Python</span>
                    <span style="display:inline-block; padding:0.3rem 0.8rem; background:#e2e8f0; border-radius:6px; margin-right:0.5rem; margin-bottom:0.5rem; font-size:0.9rem;">Arduino</span>
                    <span style="display:inline-block; padding:0.3rem 0.8rem; background:#e2e8f0; border-radius:6px; margin-right:0.5rem; margin-bottom:0.5rem; font-size:0.9rem;">LoRaWAN</span>
                    <span style="display:inline-block; padding:0.3rem 0.8rem; background:#e2e8f0; border-radius:6px; margin-right:0.5rem; margin-bottom:0.5rem; font-size:0.9rem;">React.js</span>
                </div>

                <h4 style="margin-bottom: 1rem;">Team Members</h4>
                <div style="display:flex; gap: 1rem; flex-wrap: wrap;">
                    <div style="display:flex; align-items:center; gap:0.8rem; background:white; border:1px solid var(--border); padding:0.8rem; border-radius:var(--radius-md); min-width: 200px;">
                        <div style="width:40px; height:40px; background:#cbd5e1; border-radius:50%;"></div>
                        <div>
                            <div style="font-weight:700; font-size:0.95rem;">Rahul Verma</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">Lead Developer</div>
                        </div>
                    </div>
                     <div style="display:flex; align-items:center; gap:0.8rem; background:white; border:1px solid var(--border); padding:0.8rem; border-radius:var(--radius-md); min-width: 200px;">
                        <div style="width:40px; height:40px; background:#cbd5e1; border-radius:50%;"></div>
                        <div>
                            <div style="font-weight:700; font-size:0.95rem;">Anita S.</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">Hardware Lead</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Actions -->
            <aside>
                <div class="vote-card">
                    <h3 style="margin-bottom: 0.5rem;">Support this Project</h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem;">Vote for this project to help them win the "People's Choice Award"!</p>
                    
                    <button class="btn-primary" style="width: 100%; justify-content: center; margin-bottom: 1rem;">
                        <i class="ri-thumbs-up-line" style="margin-right: 0.5rem;"></i> Vote Now
                    </button>
                    <button class="btn-outline" style="width: 100%; justify-content: center;">
                        <i class="ri-share-alt-line" style="margin-right: 0.5rem;"></i> Share
                    </button>
                    
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 0.85rem; color: var(--text-muted);">
                        Voting closes on Feb 16, 2026.
                    </div>
                </div>
            </aside>

        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="text-center" style="font-size: 0.9rem; color: #64748b;">
                &copy; 2026 College Innovation Council. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
