<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php';

// ==========================================
// HELPER: Redirect with message
// ==========================================
function redirectWith($url, $type, $message) {
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit();
}

// ==========================================
// HELPER: Get role-based dashboard URL
// ==========================================
function getDashboardUrl($role) {
    switch ($role) {
        case 'student': return 'studentDashboard.php';
        case 'studentaffairs': return 'studentAffairs.php';
        case 'departmentcoordinator': return 'departmentCoordinator.php';
        case 'admin': return 'sparkAdmin.php';
        default: return 'studentDashboard.php';
    }
}

// ==========================================
// HANDLE REGISTRATION
// ==========================================
if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $reg_no = trim($_POST['reg_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($username) || empty($department) || empty($year) || empty($reg_no) || empty($email) || empty($password)) {
        redirectWith('register.php', 'error', 'All fields are required');
    } elseif (strlen($reg_no) !== 12) {
        redirectWith('register.php', 'error', 'Register number must be 12 characters');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWith('register.php', 'error', 'Invalid email address');
    }

    // Check if username, email or reg_no already exists
    $checkQuery = "SELECT id FROM users WHERE username = ? OR email = ? OR reg_no = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $reg_no);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        redirectWith('register.php', 'error', 'Username, Email or Register Number already exists');
    }

    // Insert new user
    $insertQuery = "INSERT INTO users (name, username, department, year, reg_no, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'student')";
    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "sssssss", $name, $username, $department, $year, $reg_no, $email, $password);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        redirectWith('register.php', 'success', 'Registration successful! You can now login.');
    } else {
        mysqli_stmt_close($stmt);
        redirectWith('register.php', 'error', 'Registration failed. Please try again.');
    }
}

// ==========================================
// HANDLE LOGIN
// ==========================================
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        redirectWith('login.php', 'error', 'Please enter both username and password');
    }

    $query = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $username, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user) {
        if ($user['status'] === 'inactive') {
            redirectWith('login.php', 'error', 'Your account has been deactivated. Contact admin.');
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['userid'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department'] = $user['department'] ?? '';
        $_SESSION['year'] = $user['year'] ?? '';
        $_SESSION['reg_no'] = $user['reg_no'] ?? '';
        $_SESSION['login_time'] = time();

        $redirectUrl = getDashboardUrl($user['role']);
        $_SESSION['success'] = 'Welcome back, ' . $user['name'] . '!';
        $_SESSION['redirect_url'] = $redirectUrl;

        header("Location: login.php");
        exit();
    } else {
        redirectWith('login.php', 'error', 'Invalid username or password');
    }
}

// ==========================================
// HANDLE LOGOUT
// ==========================================
if (isset($_GET['logout']) || isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// ==========================================
// HANDLE ACTION-BASED REQUESTS
// ==========================================
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ==========================================
    // PROJECT: Submit new project
    // ==========================================
    case 'submit_project':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $title = trim($_POST['projectTitle'] ?? '');
        $category = trim($_POST['projectCategory'] ?? '');
        $description = trim($_POST['projectDescription'] ?? '');
        $teamMembers = trim($_POST['teamMembers'] ?? '');
        $githubLink = trim($_POST['githubLink'] ?? '');
        $studentId = $_SESSION['user_id'];
        $department = $_SESSION['department'] ?? '';
        $studentYear = $_SESSION['year'] ?? '';

        // First-year students belong to Freshmen Engineering (FE) department
        if (strtolower(trim($studentYear)) === 'i year') {
            $department = 'FE';
        }

        if (empty($title) || empty($category) || empty($description)) {
            redirectWith('submitProject.php', 'error', 'Title, category, and description are required');
        }

        // Check if student is in a team (required for submission)
        $teamCheckStmt = mysqli_prepare($conn, "SELECT t.id FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ?");
        mysqli_stmt_bind_param($teamCheckStmt, 'i', $studentId);
        mysqli_stmt_execute($teamCheckStmt);
        $teamRow = mysqli_fetch_assoc(mysqli_stmt_get_result($teamCheckStmt));
        mysqli_stmt_close($teamCheckStmt);

        if (!$teamRow) {
            redirectWith('submitProject.php', 'error', 'You must be part of a team to submit a project. Please create or join a team first.');
        }
        $teamId = $teamRow['id'];

        // Only team leader can submit projects
        $leaderCheckStmt = mysqli_prepare($conn, "SELECT leader_id FROM teams WHERE id = ?");
        mysqli_stmt_bind_param($leaderCheckStmt, 'i', $teamId);
        mysqli_stmt_execute($leaderCheckStmt);
        $leaderRow = mysqli_fetch_assoc(mysqli_stmt_get_result($leaderCheckStmt));
        mysqli_stmt_close($leaderCheckStmt);

        if (!$leaderRow || (int)$leaderRow['leader_id'] !== (int)$studentId) {
            redirectWith('myProjects.php', 'error', 'Only the team leader can submit projects.');
        }

        // Handle file upload
        $filePath = null;
        if (isset($_FILES['projectFile']) && $_FILES['projectFile']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/uploads/projects/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['projectFile']['name']);
            $targetPath = $uploadDir . $fileName;

            $allowedTypes = ['application/pdf'];
            if (!in_array($_FILES['projectFile']['type'], $allowedTypes)) {
                redirectWith('submitProject.php', 'error', 'Only PDF files are allowed');
            }
            if ($_FILES['projectFile']['size'] > 10 * 1024 * 1024) {
                redirectWith('submitProject.php', 'error', 'File size must be under 10MB');
            }

            if (move_uploaded_file($_FILES['projectFile']['tmp_name'], $targetPath)) {
                $filePath = $targetPath;
            }
        }

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO projects (title, description, category, student_id, team_id, department, team_members, github_link, file_path) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sssiissss", $title, $description, $category, $studentId, $teamId, $department, $teamMembers, $githubLink, $filePath);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('myProjects.php', 'success', 'Project submitted successfully!');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('submitProject.php', 'error', 'Failed to submit project. Please try again.');
        }
        break;

    // ==========================================
    // PROJECT: Delete project (student owns it)
    // ==========================================
    case 'delete_project':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $projectId = intval($_POST['project_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        // Students: only team leader can delete pending projects
        if ($role === 'student') {
            // Check if user is team leader
            $leaderChk = mysqli_prepare($conn, "SELECT t.leader_id FROM projects p JOIN teams t ON p.team_id = t.id WHERE p.id = ?");
            mysqli_stmt_bind_param($leaderChk, "i", $projectId);
            mysqli_stmt_execute($leaderChk);
            $lRow = mysqli_fetch_assoc(mysqli_stmt_get_result($leaderChk));
            mysqli_stmt_close($leaderChk);

            if (!$lRow || (int)$lRow['leader_id'] !== (int)$userId) {
                redirectWith('myProjects.php', 'error', 'Only the team leader can delete projects.');
            }

            $stmt = mysqli_prepare($conn, "DELETE FROM projects WHERE id = ? AND student_id = ? AND status = 'pending'");
            mysqli_stmt_bind_param($stmt, "ii", $projectId, $userId);
        } else {
            // Admin/affairs can delete any project
            $stmt = mysqli_prepare($conn, "DELETE FROM projects WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $projectId);
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $redirect = ($role === 'student') ? 'myProjects.php' : 'allProjects.php';
            redirectWith($redirect, 'success', 'Project deleted successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('myProjects.php', 'error', 'Failed to delete project');
        }
        break;

    // ==========================================
    // PROJECT: Review/Approve/Reject (Coordinator & Student Affairs)
    // ==========================================
    case 'review_project':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $projectId = intval($_POST['project_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $comments = trim($_POST['comments'] ?? '');
        $reviewerId = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        if (!in_array($decision, ['approved', 'rejected'])) {
            $redirect = ($role === 'departmentcoordinator') ? 'reviewApprove.php' : 'approvals.php';
            redirectWith($redirect, 'error', 'Invalid review decision');
        }

        $stmt = mysqli_prepare($conn, 
            "UPDATE projects SET status = ?, reviewed_by = ?, review_comments = ?, reviewed_at = NOW() WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "sisi", $decision, $reviewerId, $comments, $projectId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $redirect = ($role === 'departmentcoordinator') ? 'reviewApprove.php' : 'approvals.php';
            redirectWith($redirect, 'success', 'Project ' . $decision . ' successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('reviewApprove.php', 'error', 'Failed to update project status');
        }
        break;

    // ==========================================
    // PROFILE: Update profile
    // ==========================================
    case 'update_profile':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $fullName = trim($_POST['fullName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $userId = $_SESSION['user_id'];

        if (empty($fullName) || empty($email)) {
            redirectWith('settings.php', 'error', 'Name and email are required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWith('settings.php', 'error', 'Invalid email address');
        }

        // Check email uniqueness (exclude current user)
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($checkStmt, "si", $email, $userId);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            mysqli_stmt_close($checkStmt);
            redirectWith('settings.php', 'error', 'Email already in use by another account');
        }
        mysqli_stmt_close($checkStmt);

        $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $fullName, $email, $userId);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['name'] = $fullName;
            $_SESSION['email'] = $email;
            mysqli_stmt_close($stmt);
            redirectWith('settings.php', 'success', 'Profile updated successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('settings.php', 'error', 'Failed to update profile');
        }
        break;

    // ==========================================
    // PROFILE: Change password
    // ==========================================
    case 'change_password':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $userId = $_SESSION['user_id'];

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            redirectWith('settings.php', 'error', 'All password fields are required');
        }
        if ($newPassword !== $confirmPassword) {
            redirectWith('settings.php', 'error', 'New passwords do not match');
        }
        if (strlen($newPassword) < 6) {
            redirectWith('settings.php', 'error', 'Password must be at least 6 characters');
        }

        // Verify current password
        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user || $user['password'] !== $currentPassword) {
            redirectWith('settings.php', 'error', 'Current password is incorrect');
        }

        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $newPassword, $userId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('settings.php', 'success', 'Password changed successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('settings.php', 'error', 'Failed to change password');
        }
        break;

    // ==========================================
    // MESSAGES: Send message
    // ==========================================
    case 'send_message':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $recipientEmail = trim($_POST['recipient'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $senderId = $_SESSION['user_id'];

        if (empty($recipientEmail) || empty($subject) || empty($message)) {
            redirectWith('messages.php', 'error', 'All fields are required');
        }

        // Find recipient by email
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $recipientEmail);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $recipient = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$recipient) {
            redirectWith('messages.php', 'error', 'Recipient not found');
        }

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO messages (sender_id, recipient_id, subject, message) VALUES (?, ?, ?, ?)"
        );
        $recipientId = $recipient['id'];
        mysqli_stmt_bind_param($stmt, "iiss", $senderId, $recipientId, $subject, $message);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('messages.php', 'success', 'Message sent successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('messages.php', 'error', 'Failed to send message');
        }
        break;

    // ==========================================
    // ANNOUNCEMENTS: Create announcement (admin/affairs)
    // ==========================================
    case 'create_announcement':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $title = trim($_POST['announcementTitle'] ?? '');
        $message = trim($_POST['announcementMessage'] ?? '');
        $targetRole = $_POST['targetRole'] ?? 'all';
        $isFeatured = isset($_POST['isFeatured']) ? 1 : 0;
        $authorId = $_SESSION['user_id'];

        if (empty($title) || empty($message)) {
            redirectWith('announcements.php', 'error', 'Title and message are required');
        }

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO announcements (title, message, author_id, target_role, is_featured) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssisi", $title, $message, $authorId, $targetRole, $isFeatured);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('announcements.php', 'success', 'Announcement posted successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('announcements.php', 'error', 'Failed to post announcement');
        }
        break;

    // ==========================================
    // ANNOUNCEMENTS: Delete announcement
    // ==========================================
    case 'delete_announcement':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $announcementId = intval($_POST['announcement_id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM announcements WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $announcementId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('announcements.php', 'success', 'Announcement deleted');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('announcements.php', 'error', 'Failed to delete announcement');
        }
        break;

    // ==========================================
    // SCHEDULE: Add event (admin)
    // ==========================================
    case 'add_schedule':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $title = trim($_POST['eventTitle'] ?? '');
        $description = trim($_POST['eventDescription'] ?? '');
        $eventDate = $_POST['eventDate'] ?? '';
        $eventType = $_POST['eventType'] ?? 'general';
        $createdBy = $_SESSION['user_id'];

        if (empty($title) || empty($eventDate)) {
            redirectWith('schedule.php', 'error', 'Title and date are required');
        }

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO schedule (title, description, event_date, event_type, created_by) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $eventDate, $eventType, $createdBy);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('schedule.php', 'success', 'Event added to schedule');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('schedule.php', 'error', 'Failed to add event');
        }
        break;

    // ==========================================
    // SCHEDULE: Delete event
    // ==========================================
    case 'delete_schedule':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $scheduleId = intval($_POST['schedule_id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM schedule WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $scheduleId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('schedule.php', 'success', 'Event removed from schedule');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('schedule.php', 'error', 'Failed to remove event');
        }
        break;

    // ==========================================
    // ADMIN: Add user
    // ==========================================
    case 'add_user':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'student';
        $department = trim($_POST['department'] ?? '');

        if (empty($name) || empty($username) || empty($email) || empty($password)) {
            redirectWith('users.php', 'error', 'Name, username, email, and password are required');
        }

        // Check uniqueness
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($checkStmt, "ss", $username, $email);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            mysqli_stmt_close($checkStmt);
            redirectWith('users.php', 'error', 'Username or email already exists');
        }
        mysqli_stmt_close($checkStmt);

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO users (name, username, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssssss", $name, $username, $email, $password, $role, $department);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('users.php', 'success', 'User added successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('users.php', 'error', 'Failed to add user');
        }
        break;

    // ==========================================
    // ADMIN: Delete user
    // ==========================================
    case 'delete_user':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $deleteUserId = intval($_POST['user_id'] ?? 0);

        // Prevent self-deletion
        if ($deleteUserId === (int)$_SESSION['user_id']) {
            redirectWith('users.php', 'error', 'You cannot delete your own account');
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $deleteUserId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('users.php', 'success', 'User deleted successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('users.php', 'error', 'Failed to delete user');
        }
        break;

    // ==========================================
    // ADMIN: Edit user
    // ==========================================
    case 'edit_user':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $editUserId = intval($_POST['user_id'] ?? 0);
        $editName = trim($_POST['name'] ?? '');
        $editEmail = trim($_POST['email'] ?? '');
        $editRole = $_POST['role'] ?? '';
        $editDept = trim($_POST['department'] ?? '');

        if (empty($editName) || empty($editEmail)) {
            redirectWith('users.php', 'error', 'Name and email are required');
        }
        if (!filter_var($editEmail, FILTER_VALIDATE_EMAIL)) {
            redirectWith('users.php', 'error', 'Invalid email address');
        }

        $validRoles = ['student', 'admin', 'departmentcoordinator', 'studentaffairs'];
        if (!in_array($editRole, $validRoles)) {
            redirectWith('users.php', 'error', 'Invalid role');
        }

        // Check email uniqueness (exclude current user)
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($checkStmt, "si", $editEmail, $editUserId);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            mysqli_stmt_close($checkStmt);
            redirectWith('users.php', 'error', 'Email already in use by another account');
        }
        mysqli_stmt_close($checkStmt);

        $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, role = ?, department = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssssi", $editName, $editEmail, $editRole, $editDept, $editUserId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('users.php', 'success', 'User updated successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('users.php', 'error', 'Failed to update user');
        }
        break;

    // ==========================================
    // ADMIN: Update user role
    // ==========================================
    case 'update_user_role':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $targetUserId = intval($_POST['user_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? '';
        $newDept = trim($_POST['department'] ?? '');

        $validRoles = ['student', 'admin', 'departmentcoordinator', 'studentaffairs'];
        if (!in_array($newRole, $validRoles)) {
            redirectWith('users.php', 'error', 'Invalid role');
        }

        $stmt = mysqli_prepare($conn, "UPDATE users SET role = ?, department = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $newRole, $newDept, $targetUserId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('users.php', 'success', 'User role updated successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('users.php', 'error', 'Failed to update user role');
        }
        break;

    // ==========================================
    // ADMIN: Toggle user status
    // ==========================================
    case 'toggle_user_status':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $targetUserId = intval($_POST['user_id'] ?? 0);

        // Get current status
        $stmt = mysqli_prepare($conn, "SELECT status FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $targetUserId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
            $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $targetUserId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $redirect = $_POST['redirect'] ?? 'users.php';
            redirectWith($redirect, 'success', 'User status updated to ' . $newStatus);
        } else {
            $redirect = $_POST['redirect'] ?? 'users.php';
            redirectWith($redirect, 'error', 'User not found');
        }
        break;

    // ==========================================
    // TEAMS: Create team (student registers for SPARK)
    // ==========================================
    case 'create_team':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $teamName = trim($_POST['teamName'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $leaderId = $_SESSION['user_id'];
        $department = $_SESSION['department'] ?? '';
        $leaderYear = $_SESSION['year'] ?? '';

        // First-year students belong to Freshmen Engineering (FE) department
        if (strtolower(trim($leaderYear)) === 'i year') {
            $department = 'FE';
        }

        if (empty($teamName)) {
            redirectWith('myTeam.php', 'error', 'Team name is required');
        }

        // Check if student is already in a team
        $checkStmt = mysqli_prepare($conn, "SELECT tm.team_id FROM team_members tm WHERE tm.user_id = ?");
        mysqli_stmt_bind_param($checkStmt, "i", $leaderId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            redirectWith('myTeam.php', 'error', 'You are already part of a team');
        }
        mysqli_stmt_close($checkStmt);

        // Generate unique team code
        $teamCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO teams (team_name, description, team_code, leader_id, department) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sssis", $teamName, $description, $teamCode, $leaderId, $department);

        if (mysqli_stmt_execute($stmt)) {
            $teamId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Add leader as team member
            $memberStmt = mysqli_prepare($conn, 
                "INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, 'leader')"
            );
            mysqli_stmt_bind_param($memberStmt, "ii", $teamId, $leaderId);
            mysqli_stmt_execute($memberStmt);
            mysqli_stmt_close($memberStmt);

            redirectWith('myTeam.php', 'success', 'Team created! Your team code is: ' . $teamCode);
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('myTeam.php', 'error', 'Failed to create team');
        }
        break;

    // ==========================================
    // TEAMS: Join team using team code
    // ==========================================
    case 'join_team':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $teamCode = strtoupper(trim($_POST['teamCode'] ?? ''));
        $userId = $_SESSION['user_id'];

        if (empty($teamCode)) {
            redirectWith('myTeam.php', 'error', 'Team code is required');
        }

        // Check if student is already in a team
        $checkStmt = mysqli_prepare($conn, "SELECT tm.team_id FROM team_members tm WHERE tm.user_id = ?");
        mysqli_stmt_bind_param($checkStmt, "i", $userId);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            mysqli_stmt_close($checkStmt);
            redirectWith('myTeam.php', 'error', 'You are already part of a team');
        }
        mysqli_stmt_close($checkStmt);

        // Find team by code
        $teamStmt = mysqli_prepare($conn, "SELECT * FROM teams WHERE team_code = ? AND status = 'open'");
        mysqli_stmt_bind_param($teamStmt, "s", $teamCode);
        mysqli_stmt_execute($teamStmt);
        $team = mysqli_fetch_assoc(mysqli_stmt_get_result($teamStmt));
        mysqli_stmt_close($teamStmt);

        if (!$team) {
            redirectWith('myTeam.php', 'error', 'Invalid team code or team is closed');
        }

        // Check max members
        $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM team_members WHERE team_id = ?");
        mysqli_stmt_bind_param($countStmt, "i", $team['id']);
        mysqli_stmt_execute($countStmt);
        $memberCount = mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['cnt'];
        mysqli_stmt_close($countStmt);

        if ($memberCount >= $team['max_members']) {
            redirectWith('myTeam.php', 'error', 'Team is full (max ' . $team['max_members'] . ' members)');
        }

        // Add to team
        $joinStmt = mysqli_prepare($conn, "INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, 'member')");
        mysqli_stmt_bind_param($joinStmt, "ii", $team['id'], $userId);

        if (mysqli_stmt_execute($joinStmt)) {
            mysqli_stmt_close($joinStmt);

            // Auto-close team if now full
            if ($memberCount + 1 >= $team['max_members']) {
                $closeStmt = mysqli_prepare($conn, "UPDATE teams SET status = 'closed' WHERE id = ?");
                mysqli_stmt_bind_param($closeStmt, "i", $team['id']);
                mysqli_stmt_execute($closeStmt);
                mysqli_stmt_close($closeStmt);
            }

            redirectWith('myTeam.php', 'success', 'Successfully joined team: ' . $team['team_name']);
        } else {
            mysqli_stmt_close($joinStmt);
            redirectWith('myTeam.php', 'error', 'Failed to join team');
        }
        break;

    // ==========================================
    // TEAMS: Leave team
    // ==========================================
    case 'leave_team':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $userId = $_SESSION['user_id'];
        $teamId = intval($_POST['team_id'] ?? 0);

        // Check if leader
        $leaderCheck = mysqli_prepare($conn, "SELECT leader_id FROM teams WHERE id = ?");
        mysqli_stmt_bind_param($leaderCheck, "i", $teamId);
        mysqli_stmt_execute($leaderCheck);
        $teamInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($leaderCheck));
        mysqli_stmt_close($leaderCheck);

        if ($teamInfo && (int)$teamInfo['leader_id'] === (int)$userId) {
            redirectWith('myTeam.php', 'error', 'Team leader cannot leave. Delete the team instead.');
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $teamId, $userId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Reopen team if it was closed
            $reopenStmt = mysqli_prepare($conn, "UPDATE teams SET status = 'open' WHERE id = ?");
            mysqli_stmt_bind_param($reopenStmt, "i", $teamId);
            mysqli_stmt_execute($reopenStmt);
            mysqli_stmt_close($reopenStmt);
            redirectWith('myTeam.php', 'success', 'You have left the team');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('myTeam.php', 'error', 'Failed to leave team');
        }
        break;

    // ==========================================
    // TEAMS: Delete team (leader only)
    // ==========================================
    case 'delete_team':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $userId = $_SESSION['user_id'];
        $teamId = intval($_POST['team_id'] ?? 0);

        // Verify leader
        $leaderCheck = mysqli_prepare($conn, "SELECT leader_id FROM teams WHERE id = ?");
        mysqli_stmt_bind_param($leaderCheck, "i", $teamId);
        mysqli_stmt_execute($leaderCheck);
        $teamInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($leaderCheck));
        mysqli_stmt_close($leaderCheck);

        if (!$teamInfo || (int)$teamInfo['leader_id'] !== (int)$userId) {
            $role = $_SESSION['role'] ?? '';
            if (!in_array($role, ['admin', 'studentaffairs'])) {
                redirectWith('myTeam.php', 'error', 'Only the team leader or admin can delete the team');
            }
        }

        // Delete team members first, then team
        $delMembers = mysqli_prepare($conn, "DELETE FROM team_members WHERE team_id = ?");
        mysqli_stmt_bind_param($delMembers, "i", $teamId);
        mysqli_stmt_execute($delMembers);
        mysqli_stmt_close($delMembers);

        $delTeam = mysqli_prepare($conn, "DELETE FROM teams WHERE id = ?");
        mysqli_stmt_bind_param($delTeam, "i", $teamId);

        if (mysqli_stmt_execute($delTeam)) {
            mysqli_stmt_close($delTeam);
            $redirect = ($_SESSION['role'] === 'student') ? 'myTeam.php' : 'teams.php';
            redirectWith($redirect, 'success', 'Team deleted successfully');
        } else {
            mysqli_stmt_close($delTeam);
            redirectWith('myTeam.php', 'error', 'Failed to delete team');
        }
        break;

    // ==========================================
    // TEAMS: Remove member (leader only)
    // ==========================================
    case 'remove_member':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $leaderId = $_SESSION['user_id'];
        $memberId = intval($_POST['member_id'] ?? 0);
        $teamId = intval($_POST['team_id'] ?? 0);

        // Verify requester is the leader
        $leaderCheck = mysqli_prepare($conn, "SELECT leader_id FROM teams WHERE id = ?");
        mysqli_stmt_bind_param($leaderCheck, "i", $teamId);
        mysqli_stmt_execute($leaderCheck);
        $teamInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($leaderCheck));
        mysqli_stmt_close($leaderCheck);

        if (!$teamInfo || (int)$teamInfo['leader_id'] !== (int)$leaderId) {
            redirectWith('myTeam.php', 'error', 'Only the team leader can remove members');
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $teamId, $memberId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $reopenStmt = mysqli_prepare($conn, "UPDATE teams SET status = 'open' WHERE id = ?");
            mysqli_stmt_bind_param($reopenStmt, "i", $teamId);
            mysqli_stmt_execute($reopenStmt);
            mysqli_stmt_close($reopenStmt);
            redirectWith('myTeam.php', 'success', 'Member removed from team');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('myTeam.php', 'error', 'Failed to remove member');
        }
        break;

    // ==========================================
    // ADMIN: Add new coordinator (create user)
    // ==========================================
    case 'add_coordinator':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $year = null;
        $regNo = trim($_POST['reg_no'] ?? '') ?: null;
        $status = trim($_POST['status'] ?? 'active');

        if (empty($name) || empty($username) || empty($email) || empty($password) || empty($department)) {
            redirectWith('coordinators.php', 'error', 'Name, username, email, password and department are required');
        }

        // Check if username or email already exists
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($checkStmt, 'ss', $username, $email);
        mysqli_stmt_execute($checkStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
            mysqli_stmt_close($checkStmt);
            redirectWith('coordinators.php', 'error', 'Username or email already exists');
        }
        mysqli_stmt_close($checkStmt);

        $role = 'departmentcoordinator';
        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (name, username, email, password, department, year, reg_no, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, 'sssssssss', $name, $username, $email, $password, $department, $year, $regNo, $role, $status);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('coordinators.php', 'success', 'Coordinator "' . $name . '" created successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('coordinators.php', 'error', 'Failed to create coordinator. Please try again.');
        }
        break;

    // ==========================================
    // ADMIN: Assign coordinator to department
    // ==========================================
    case 'assign_coordinator':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $userId = intval($_POST['user_id'] ?? 0);
        $department = trim($_POST['department'] ?? '');

        if (empty($department)) {
            redirectWith('coordinators.php', 'error', 'Department is required');
        }

        $stmt = mysqli_prepare($conn, 
            "UPDATE users SET role = 'departmentcoordinator', department = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "si", $department, $userId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('coordinators.php', 'success', 'Coordinator assigned successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('coordinators.php', 'error', 'Failed to assign coordinator');
        }
        break;

    // ==========================================
    // PROJECT: Score project (for judging)
    // ==========================================
    case 'score_project':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $projectId = intval($_POST['project_id'] ?? 0);
        $score = intval($_POST['score'] ?? 0);

        if ($score < 0 || $score > 100) {
            redirectWith('judging.php', 'error', 'Score must be between 0 and 100');
        }

        $stmt = mysqli_prepare($conn, "UPDATE projects SET score = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $score, $projectId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('judging.php', 'success', 'Score submitted successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('judging.php', 'error', 'Failed to submit score');
        }
        break;

    // ==========================================
    // TEAMS: Send invitation to a student (leader only)
    // ==========================================
    case 'send_invite':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $leaderId = $_SESSION['user_id'];
        $invitedUserId = intval($_POST['invited_user_id'] ?? 0);

        if (!$invitedUserId) {
            redirectWith('myTeam.php', 'error', 'Please select a student to invite');
        }

        // Find the leader's team
        $teamStmt = mysqli_prepare($conn, "SELECT id, max_members FROM teams WHERE leader_id = ?");
        mysqli_stmt_bind_param($teamStmt, "i", $leaderId);
        mysqli_stmt_execute($teamStmt);
        $team = mysqli_fetch_assoc(mysqli_stmt_get_result($teamStmt));
        mysqli_stmt_close($teamStmt);

        if (!$team) {
            redirectWith('myTeam.php', 'error', 'You are not a team leader');
        }

        $teamId = $team['id'];

        // Check current member count vs max
        $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM team_members WHERE team_id = ?");
        mysqli_stmt_bind_param($countStmt, "i", $teamId);
        mysqli_stmt_execute($countStmt);
        $memberCount = mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['cnt'];
        mysqli_stmt_close($countStmt);

        if ($memberCount >= $team['max_members']) {
            redirectWith('myTeam.php', 'error', 'Team is already full');
        }

        // Check if invited user is already in a team
        $inTeamStmt = mysqli_prepare($conn, "SELECT team_id FROM team_members WHERE user_id = ?");
        mysqli_stmt_bind_param($inTeamStmt, "i", $invitedUserId);
        mysqli_stmt_execute($inTeamStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($inTeamStmt))) {
            mysqli_stmt_close($inTeamStmt);
            redirectWith('myTeam.php', 'error', 'This student is already in a team');
        }
        mysqli_stmt_close($inTeamStmt);

        // Check if invitation already pending
        $existStmt = mysqli_prepare($conn, "SELECT id FROM team_invitations WHERE team_id = ? AND invited_user_id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($existStmt, "ii", $teamId, $invitedUserId);
        mysqli_stmt_execute($existStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($existStmt))) {
            mysqli_stmt_close($existStmt);
            redirectWith('myTeam.php', 'error', 'Invitation already sent to this student');
        }
        mysqli_stmt_close($existStmt);

        // Send invitation
        $inviteStmt = mysqli_prepare($conn, "INSERT INTO team_invitations (team_id, invited_by, invited_user_id) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($inviteStmt, "iii", $teamId, $leaderId, $invitedUserId);

        if (mysqli_stmt_execute($inviteStmt)) {
            mysqli_stmt_close($inviteStmt);
            redirectWith('myTeam.php', 'success', 'Invitation sent successfully!');
        } else {
            mysqli_stmt_close($inviteStmt);
            redirectWith('myTeam.php', 'error', 'Failed to send invitation');
        }
        break;

    // ==========================================
    // TEAMS: Accept invitation
    // ==========================================
    case 'accept_invite':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $userId = $_SESSION['user_id'];
        $inviteId = intval($_POST['invite_id'] ?? 0);

        // Get invitation details
        $invStmt = mysqli_prepare($conn, "SELECT ti.*, t.max_members, t.team_name FROM team_invitations ti JOIN teams t ON ti.team_id = t.id WHERE ti.id = ? AND ti.invited_user_id = ? AND ti.status = 'pending'");
        mysqli_stmt_bind_param($invStmt, "ii", $inviteId, $userId);
        mysqli_stmt_execute($invStmt);
        $invite = mysqli_fetch_assoc(mysqli_stmt_get_result($invStmt));
        mysqli_stmt_close($invStmt);

        if (!$invite) {
            redirectWith('myTeam.php', 'error', 'Invalid or expired invitation');
        }

        // Check if user is already in a team
        $inTeamStmt = mysqli_prepare($conn, "SELECT team_id FROM team_members WHERE user_id = ?");
        mysqli_stmt_bind_param($inTeamStmt, "i", $userId);
        mysqli_stmt_execute($inTeamStmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($inTeamStmt))) {
            mysqli_stmt_close($inTeamStmt);
            // Decline this invite since user already in a team
            $decStmt = mysqli_prepare($conn, "UPDATE team_invitations SET status = 'declined', responded_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($decStmt, "i", $inviteId);
            mysqli_stmt_execute($decStmt);
            mysqli_stmt_close($decStmt);
            redirectWith('myTeam.php', 'error', 'You are already in a team');
        }
        mysqli_stmt_close($inTeamStmt);

        // Check max members
        $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM team_members WHERE team_id = ?");
        mysqli_stmt_bind_param($countStmt, "i", $invite['team_id']);
        mysqli_stmt_execute($countStmt);
        $memberCount = mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['cnt'];
        mysqli_stmt_close($countStmt);

        if ($memberCount >= $invite['max_members']) {
            $decStmt = mysqli_prepare($conn, "UPDATE team_invitations SET status = 'declined', responded_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($decStmt, "i", $inviteId);
            mysqli_stmt_execute($decStmt);
            mysqli_stmt_close($decStmt);
            redirectWith('myTeam.php', 'error', 'Team is now full. Invitation expired.');
        }

        // Add user to team
        $joinStmt = mysqli_prepare($conn, "INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, 'member')");
        mysqli_stmt_bind_param($joinStmt, "ii", $invite['team_id'], $userId);

        if (mysqli_stmt_execute($joinStmt)) {
            mysqli_stmt_close($joinStmt);

            // Update invitation status
            $upStmt = mysqli_prepare($conn, "UPDATE team_invitations SET status = 'accepted', responded_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($upStmt, "i", $inviteId);
            mysqli_stmt_execute($upStmt);
            mysqli_stmt_close($upStmt);

            // Decline other pending invitations for this user
            $decOthers = mysqli_prepare($conn, "UPDATE team_invitations SET status = 'declined', responded_at = NOW() WHERE invited_user_id = ? AND status = 'pending'");
            mysqli_stmt_bind_param($decOthers, "i", $userId);
            mysqli_stmt_execute($decOthers);
            mysqli_stmt_close($decOthers);

            // Auto-close team if full
            if ($memberCount + 1 >= $invite['max_members']) {
                $closeStmt = mysqli_prepare($conn, "UPDATE teams SET status = 'closed' WHERE id = ?");
                mysqli_stmt_bind_param($closeStmt, "i", $invite['team_id']);
                mysqli_stmt_execute($closeStmt);
                mysqli_stmt_close($closeStmt);
            }

            redirectWith('myTeam.php', 'success', 'You have joined team: ' . $invite['team_name']);
        } else {
            mysqli_stmt_close($joinStmt);
            redirectWith('myTeam.php', 'error', 'Failed to join team');
        }
        break;

    // ==========================================
    // TEAMS: Decline invitation
    // ==========================================
    case 'decline_invite':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $userId = $_SESSION['user_id'];
        $inviteId = intval($_POST['invite_id'] ?? 0);

        $decStmt = mysqli_prepare($conn, "UPDATE team_invitations SET status = 'declined', responded_at = NOW() WHERE id = ? AND invited_user_id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($decStmt, "ii", $inviteId, $userId);

        if (mysqli_stmt_execute($decStmt) && mysqli_stmt_affected_rows($decStmt) > 0) {
            mysqli_stmt_close($decStmt);
            redirectWith('myTeam.php', 'success', 'Invitation declined');
        } else {
            mysqli_stmt_close($decStmt);
            redirectWith('myTeam.php', 'error', 'Invalid or already responded invitation');
        }
        break;

    // ==========================================
    // TEAMS: Cancel invitation (leader only)
    // ==========================================
    case 'cancel_invite':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $leaderId = $_SESSION['user_id'];
        $inviteId = intval($_POST['invite_id'] ?? 0);

        $cancelStmt = mysqli_prepare($conn, "DELETE FROM team_invitations WHERE id = ? AND invited_by = ? AND status = 'pending'");
        mysqli_stmt_bind_param($cancelStmt, "ii", $inviteId, $leaderId);

        if (mysqli_stmt_execute($cancelStmt) && mysqli_stmt_affected_rows($cancelStmt) > 0) {
            mysqli_stmt_close($cancelStmt);
            redirectWith('myTeam.php', 'success', 'Invitation cancelled');
        } else {
            mysqli_stmt_close($cancelStmt);
            redirectWith('myTeam.php', 'error', 'Could not cancel invitation');
        }
        break;

    default:
        // No valid action, redirect to home
        if (!empty($action)) {
            redirectWith('index.php', 'error', 'Invalid action');
        }
        break;
}
?>