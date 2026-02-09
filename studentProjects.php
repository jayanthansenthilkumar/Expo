<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects | SPARK'26</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* Simple Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    ?>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="d-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo">
                    <div style="width:30px; height:30px; background:var(--primary); border-radius:8px;"></div>
                    SPARK <span>'26</span>
                </a>
            </div>
            <div class="sidebar-menu">
                <a href="studentDashboard.php" class="menu-item"><i class="ri-home-line"></i> Home</a>
                <a href="studentProjects.php" class="menu-item active"><i class="ri-stack-line"></i> My Projects</a>
                <a href="studentMessages.php" class="menu-item"><i class="ri-mail-line"></i> Messages</a>
                <a href="studentResources.php" class="menu-item"><i class="ri-book-open-line"></i> Resources</a>
            </div>
            <div style="padding: 1.5rem;">
                <a href="login.php" class="menu-item" style="color: #ef4444;"><i class="ri-logout-box-r-line"></i>
                    Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="d-main">
            <!-- Header -->
            <header class="d-header">
                <div class="header-search">
                    <i class="ri-search-line"></i>
                    <input type="text" placeholder="Search projects...">
                </div>
                <!-- Profile -->
                <div class="header-profile" onclick="toggleDropdown()">
                    <div class="user-info">
                        <span class="user-name">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student'); ?>
                        </span>
                        <span class="user-role">Student</span>
                    </div>
                    <div class="user-avatar">AM</div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="userProfile.php" class="dropdown-item"><i class="ri-user-line"></i> My Profile</a>
                        <div class="dropdown-divider"></div>
                        <a href="login.php" class="dropdown-item" style="color: #ef4444;"><i
                                class="ri-logout-box-r-line"></i> Logout</a>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="d-content">
                <!-- Page Header -->
                <div class="welcome-card"
                    style="min-height: 150px; display: flex; justify-content: space-between; align-items: center; padding-right: 2rem;">
                    <div class="welcome-text">
                        <h2>My Projects</h2>
                        <p>Manage and track your innovation projects.</p>
                    </div>
                    <button class="btn-primary" id="addProjectBtn">+ New Project</button>
                </div>

                <div id="projectsList" class="dashboard-grid" style="margin-top: 2rem;">
                    <!-- Projects will be loaded here via AJAX -->
                    <p style="grid-column: 1/-1; text-align: center;">Loading projects...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Project Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 style="margin-bottom: 1rem;">Add New Project</h2>
            <form id="addProjectForm">
                <div class="form-group">
                    <label class="form-label">Project Title</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <option value="">Select Category...</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Software">Software</option>
                        <option value="Research">Research</option>
                        <option value="Social Innovation">Social Innovation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Create Project</button>
            </form>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Modal Logic
        var modal = document.getElementById("projectModal");
        var btn = document.getElementById("addProjectBtn");
        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function () {
            modal.style.display = "block";
        }
        span.onclick = function () {
            modal.style.display = "none";
        }
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function toggleDropdown() {
            document.getElementById('profileDropdown').classList.toggle('show');
        }

        // Fetch Projects
        function loadProjects() {
            $.ajax({
                url: 'api/get_my_projects.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        var html = '';
                        if (response.data.length === 0) {
                            html = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted);">No projects found. Create one to get started!</p>';
                        } else {
                            response.data.forEach(function (project) {
                                html += `
                                <div class="dash-card">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <span class="status-badge status-${project.status}">${project.status}</span>
                                        <i class="ri-more-2-fill" style="color: var(--text-muted); cursor: pointer;"></i>
                                    </div>
                                    <h3 class="proj-title">${project.title}</h3>
                                    <p class="proj-meta">Category: ${project.category}</p>
                                    <p style="margin-bottom: 1.5rem; font-size: 0.95rem; color: #64748b; line-height: 1.5;">
                                        ${project.description.substring(0, 100)}...
                                    </p>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="viewProject.php?id=${project.id}" class="btn-outline" style="width: 100%; text-align: center; display: inline-block; text-decoration: none;">View Details</a>
                                    </div>
                                </div>
                                `;
                            });
                        }
                        $('#projectsList').html(html);
                    } else {
                        $('#projectsList').html('<p style="color: red;">Error loading projects.</p>');
                    }
                },
                error: function () {
                    $('#projectsList').html('<p style="color: red;">Connection error.</p>');
                }
            });
        }

        // Submit Project
        $('#addProjectForm').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: 'api/create_project.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        modal.style.display = "none";
                        $('#addProjectForm')[0].reset();
                        loadProjects(); // Reload list
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert('Error creating project.');
                }
            });
        });

        // Initial Load
        $(document).ready(function () {
            loadProjects();
        });
    </script>
</body>

</html>