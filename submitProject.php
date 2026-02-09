<?php
require_once 'includes/auth.php';
require_once 'db.php';

checkUserAccess();

$userName = $_SESSION['name'] ?? 'Student';
$userInitials = strtoupper(substr($userName, 0, 2));
$userRole = ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Student');

$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Project | SPARK'26</title>
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
                    <h1>Submit Project</h1>
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
                <div class="form-container">
                    <div class="form-card">
                        <h2>Project Submission Form</h2>
                        <p class="form-description">Fill in the details below to submit your project for SPARK'26</p>
                        
                        <form action="sparkBackend.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="submit_project">
                            
                            <div class="form-group">
                                <label for="projectTitle">Project Title *</label>
                                <input type="text" id="projectTitle" name="projectTitle" required placeholder="Enter your project title">
                            </div>

                            <div class="form-group">
                                <label for="projectCategory">Category *</label>
                                <select id="projectCategory" name="projectCategory" required>
                                    <option value="">Select a category</option>
                                    <option value="web">Web Development</option>
                                    <option value="mobile">Mobile Application</option>
                                    <option value="ai">AI/Machine Learning</option>
                                    <option value="iot">IoT</option>
                                    <option value="blockchain">Blockchain</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="projectDescription">Description *</label>
                                <textarea id="projectDescription" name="projectDescription" rows="5" required placeholder="Describe your project in detail"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="teamMembers">Team Members</label>
                                <input type="text" id="teamMembers" name="teamMembers" placeholder="Enter team member names (comma separated)">
                            </div>

                            <div class="form-group">
                                <label for="projectFile">Project Documentation (PDF)</label>
                                <input type="file" id="projectFile" name="projectFile" accept=".pdf">
                            </div>

                            <div class="form-group">
                                <label for="githubLink">GitHub Repository</label>
                                <input type="url" id="githubLink" name="githubLink" placeholder="https://github.com/username/repo">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Submit Project</button>
                                <a href="myProjects.php" class="btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
