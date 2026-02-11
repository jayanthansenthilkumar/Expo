<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php';

// ==========================================
// HELPER: Redirect with message
// ==========================================
function redirectWith($url, $type, $message)
{
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit();
}

// ==========================================
// HELPER: Get role-based dashboard URL
// ==========================================
function getDashboardUrl($role)
{
    switch ($role) {
        case 'student':
            return 'studentDashboard.php';
        case 'studentaffairs':
            return 'studentAffairs.php';
        case 'departmentcoordinator':
            return 'departmentCoordinator.php';
        case 'admin':
            return 'sparkAdmin.php';
        default:
            return 'studentDashboard.php';
    }
}

// ==========================================
// HELPER: Get role-based chat suggestions
// ==========================================
function getChatSuggestions($role = null)
{
    if (!$role) {
        return [
            ['icon' => 'ri-user-add-line', 'text' => 'Register'],
            ['icon' => 'ri-login-box-line', 'text' => 'Login'],
            ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
            ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
        ];
    }
    switch ($role) {
        case 'student':
            return [
                ['icon' => 'ri-folder-line', 'text' => 'My Projects'],
                ['icon' => 'ri-team-line', 'text' => 'My Team'],
                ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
            ];
        case 'departmentcoordinator':
            return [
                ['icon' => 'ri-checkbox-circle-line', 'text' => 'Pending Reviews'],
                ['icon' => 'ri-bar-chart-line', 'text' => 'Department Stats'],
                ['icon' => 'ri-group-line', 'text' => 'Students'],
                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
            ];
        case 'admin':
            return [
                ['icon' => 'ri-pie-chart-line', 'text' => 'Analytics'],
                ['icon' => 'ri-folder-line', 'text' => 'All Projects'],
                ['icon' => 'ri-shield-user-line', 'text' => 'Coordinators'],
                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
            ];
        case 'studentaffairs':
            return [
                ['icon' => 'ri-folder-line', 'text' => 'All Projects'],
                ['icon' => 'ri-checkbox-circle-line', 'text' => 'Approvals'],
                ['icon' => 'ri-pie-chart-line', 'text' => 'Analytics'],
                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
            ];
        default:
            return [
                ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                ['icon' => 'ri-compass-line', 'text' => 'Tracks'],
                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
            ];
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
    // CHAT: Syraa AI Bot (Expanded with State Machine)
    // ==========================================
    case 'chat_query':
        header('Content-Type: application/json');
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = ['message' => ''];
        }
        $message = trim($data['message'] ?? '');
        $lowerMsg = strtolower($message);

        // Initialize Chat State
        if (!isset($_SESSION['chat_state'])) {
            $_SESSION['chat_state'] = 'IDLE';
            $_SESSION['chat_data'] = [];
        }

        $response = ['reply' => '', 'action' => null];
        $currentState = $_SESSION['chat_state'];

        // GLOBAL: Cancel Command
        if (in_array($lowerMsg, ['cancel', 'stop', 'exit', 'quit'])) {
            $_SESSION['chat_state'] = 'IDLE';
            $_SESSION['chat_data'] = [];
            echo json_encode([
                'reply' => "Action cancelled. How else can I help you?",
                'action' => 'reset_ui',
                'suggestions' => getChatSuggestions($_SESSION['role'] ?? null)
            ]);
            exit;
        }

        // ============================================================
        // STATE MACHINE
        // ============================================================

        switch ($currentState) {
            case 'IDLE':
                // --- Check Notifications (Button Trigger) ---
                if ($message === '__check_notifications__') {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login to check notifications.";
                        $response['suggestions'] = [
                            ['icon' => 'ri-login-box-line', 'text' => 'Login'],
                            ['icon' => 'ri-user-add-line', 'text' => 'Register']
                        ];
                    } else {
                        // Check for pending invitations
                        $invStmt = mysqli_prepare($conn, "SELECT ti.id, t.team_name, ti.team_id FROM team_invitations ti JOIN teams t ON ti.team_id = t.id WHERE ti.invited_user_id = ? AND ti.status = 'pending' LIMIT 1");
                        mysqli_stmt_bind_param($invStmt, "i", $_SESSION['user_id']);
                        mysqli_stmt_execute($invStmt);
                        $invRes = mysqli_stmt_get_result($invStmt);

                        if ($row = mysqli_fetch_assoc($invRes)) {
                            $_SESSION['chat_state'] = 'INVITE_DECISION';
                            $_SESSION['chat_data']['invite_id'] = $row['id'];
                            $_SESSION['chat_data']['team_id'] = $row['team_id'];
                            $_SESSION['chat_data']['team_name'] = $row['team_name'];

                            $response['reply'] = "🔔 You have an invitation to join **" . $row['team_name'] . "**. Do you want to accept?";
                            $response['options'] = ['Accept', 'Decline'];
                        } else {
                            $response['reply'] = "You have no new notifications involved with teams.";
                            $response['suggestions'] = [
                                ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                                ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                                ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                            ];
                        }
                    }
                }

                // --- Start Registration ---
                elseif (strpos($lowerMsg, 'register') !== false || strpos($lowerMsg, 'signup') !== false) {
                    if (isset($_SESSION['user_id'])) {
                        $response['reply'] = "You are already logged in as " . $_SESSION['name'] . ". Please logout first to register a new account.";
                        $response['suggestions'] = [
                            ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                            ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                            ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                        ];
                    } else {
                        $_SESSION['chat_state'] = 'REG_ASK_NAME';
                        $_SESSION['chat_data'] = [];
                        $response['reply'] = "Let's create your account! First, what is your **Full Name**?";
                    }
                }
                // --- Start Login ---
                elseif (strpos($lowerMsg, 'login') !== false || strpos($lowerMsg, 'signin') !== false) {
                    if (isset($_SESSION['user_id'])) {
                        $response['reply'] = "You are already logged in as " . $_SESSION['name'] . ".";
                        $response['suggestions'] = [
                            ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                            ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                            ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                        ];
                    } else {
                        $_SESSION['chat_state'] = 'LOGIN_ASK_USER';
                        $_SESSION['chat_data'] = [];
                        $response['reply'] = "Secure Login: Please enter your **Username**.";
                        $response['input_type'] = 'text';
                    }
                }
                // --- Start Create Team ---
                elseif (strpos($lowerMsg, 'create team') !== false) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "You need to login first to create a team. Type 'login' to start.";
                        $response['suggestions'] = [
                            ['icon' => 'ri-login-box-line', 'text' => 'Login'],
                            ['icon' => 'ri-user-add-line', 'text' => 'Register']
                        ];
                    } else {
                        // Check if already in a team
                        $chk = mysqli_query($conn, "SELECT id FROM team_members WHERE user_id = " . $_SESSION['user_id']);
                        if (mysqli_num_rows($chk) > 0) {
                            $response['reply'] = "You are already part of a team. You cannot create another one.";
                            $response['suggestions'] = [
                                ['icon' => 'ri-mail-send-line', 'text' => 'Invite'],
                                ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                                ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                            ];
                        } else {
                            $_SESSION['chat_state'] = 'TEAM_CREATE_ASK_NAME';
                            $_SESSION['chat_data'] = [];
                            $response['reply'] = "Exciting! Let's build your squad. What will be your **Team Name**?";
                        }
                    }
                }
                // --- Start Join Team ---
                elseif (strpos($lowerMsg, 'join team') !== false) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first to join a team.";
                        $response['suggestions'] = [
                            ['icon' => 'ri-login-box-line', 'text' => 'Login'],
                            ['icon' => 'ri-user-add-line', 'text' => 'Register']
                        ];
                    } else {
                        // Check if already in a team
                        $chk = mysqli_query($conn, "SELECT id FROM team_members WHERE user_id = " . $_SESSION['user_id']);
                        if (mysqli_num_rows($chk) > 0) {
                            $response['reply'] = "You are already part of a team.";
                            $response['suggestions'] = [
                                ['icon' => 'ri-mail-send-line', 'text' => 'Invite'],
                                ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                                ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                            ];
                        } else {
                            $_SESSION['chat_state'] = 'TEAM_JOIN_ASK_CODE';
                            $response['reply'] = "Please enter the **Team Invite Code** shared by your leader.";
                        }
                    }
                }
                // --- Start Invite Member ---
                elseif (strpos($lowerMsg, 'invite') !== false) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = [
                            ['icon' => 'ri-login-box-line', 'text' => 'Login'],
                            ['icon' => 'ri-user-add-line', 'text' => 'Register']
                        ];
                    } else {
                        // Check leader status
                        $chk = mysqli_query($conn, "SELECT id FROM teams WHERE leader_id = " . $_SESSION['user_id']);
                        if (mysqli_num_rows($chk) == 0) {
                            $response['reply'] = "Only team leaders can invite members.";
                            $response['suggestions'] = [
                                ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                                ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                                ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                            ];
                        } else {
                            $_SESSION['chat_state'] = 'TEAM_INVITE_ASK_USER';
                            $response['reply'] = "Who would you like to invite? Enter their **Username** or **Email**.";
                        }
                    }
                }

                // ============================================================
                // ROLE-BASED FEATURE COMMANDS
                // ============================================================

                // --- Logout ---
                elseif (preg_match('/^(logout|log ?out|sign ?out|signout)$/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "You are not logged in.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $name = $_SESSION['name'];
                        session_unset();
                        session_destroy();
                        session_start();
                        $_SESSION['chat_state'] = 'IDLE';
                        $response['reply'] = "Goodbye, $name! You have been logged out successfully. 👋";
                        $response['action'] = 'reload';
                        $response['suggestions'] = getChatSuggestions();
                    }
                }

                // --- My Profile ---
                elseif (preg_match('/(my profile|my info|who am i|my account|my detail|view profile)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first to view your profile.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $uid = $_SESSION['user_id'];
                        $stmt = mysqli_prepare($conn, "SELECT name, email, role, department, year, reg_no, created_at FROM users WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "i", $uid);
                        mysqli_stmt_execute($stmt);
                        $pUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                        mysqli_stmt_close($stmt);

                        if ($pUser) {
                            $joined = date('M d, Y', strtotime($pUser['created_at']));
                            $response['reply'] = "📋 **Your Profile**\n"
                                . "• **Name:** " . $pUser['name'] . "\n"
                                . "• **Email:** " . $pUser['email'] . "\n"
                                . "• **Role:** " . ucfirst($pUser['role']) . "\n"
                                . ($pUser['department'] ? "• **Department:** " . $pUser['department'] . "\n" : "")
                                . ($pUser['year'] ? "• **Year:** " . $pUser['year'] . "\n" : "")
                                . ($pUser['reg_no'] ? "• **Reg No:** " . $pUser['reg_no'] . "\n" : "")
                                . "• **Joined:** " . $joined;
                        } else {
                            $response['reply'] = "Could not fetch profile details.";
                        }
                        $response['suggestions'] = [
                            ['icon' => 'ri-settings-3-line', 'text' => 'Settings'],
                            ['icon' => 'ri-bar-chart-line', 'text' => 'My Stats'],
                            ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                        ];
                    }
                }

                // --- My Stats / Dashboard ---
                elseif (preg_match('/(my stats|my dashboard|^dashboard$|my overview|my summary)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        $uid = $_SESSION['user_id'];

                        if ($role === 'student') {
                            $teamId = null;
                            $tc = mysqli_prepare($conn, "SELECT t.id FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ?");
                            mysqli_stmt_bind_param($tc, "i", $uid);
                            mysqli_stmt_execute($tc);
                            $tr = mysqli_fetch_assoc(mysqli_stmt_get_result($tc));
                            if ($tr) $teamId = $tr['id'];
                            mysqli_stmt_close($tc);

                            $total = 0; $approved = 0; $pending = 0; $rejected = 0;
                            if ($teamId) {
                                $ps = mysqli_prepare($conn, "SELECT status, COUNT(*) as cnt FROM projects WHERE team_id = ? GROUP BY status");
                                mysqli_stmt_bind_param($ps, "i", $teamId);
                            } else {
                                $ps = mysqli_prepare($conn, "SELECT status, COUNT(*) as cnt FROM projects WHERE student_id = ? GROUP BY status");
                                mysqli_stmt_bind_param($ps, "i", $uid);
                            }
                            mysqli_stmt_execute($ps);
                            $pr = mysqli_stmt_get_result($ps);
                            while ($row = mysqli_fetch_assoc($pr)) {
                                $total += $row['cnt'];
                                if ($row['status'] === 'approved') $approved = $row['cnt'];
                                if ($row['status'] === 'pending') $pending = $row['cnt'];
                                if ($row['status'] === 'rejected') $rejected = $row['cnt'];
                            }
                            mysqli_stmt_close($ps);

                            $eventDate = '2026-02-15';
                            $daysLeft = max(0, (int)((strtotime($eventDate) - time()) / 86400));

                            $response['reply'] = "📊 **Your Dashboard**"
                                . ($teamId ? "" : "\n\n⚠️ You haven't joined a team yet!");
                            $response['chart'] = [
                                'type' => 'student',
                                'title' => 'Your Dashboard',
                                'donut' => [
                                    'label' => 'Projects',
                                    'total' => (int)$total,
                                    'segments' => [
                                        ['label' => 'Approved', 'value' => (int)$approved, 'color' => '#22c55e'],
                                        ['label' => 'Pending', 'value' => (int)$pending, 'color' => '#f59e0b'],
                                        ['label' => 'Rejected', 'value' => (int)$rejected, 'color' => '#ef4444']
                                    ]
                                ],
                                'bars' => [
                                    ['label' => 'Total Projects', 'value' => (int)$total, 'icon' => '📁'],
                                    ['label' => 'Approved', 'value' => (int)$approved, 'icon' => '✅'],
                                    ['label' => 'Pending', 'value' => (int)$pending, 'icon' => '⏳'],
                                    ['label' => 'Days to Expo', 'value' => (int)$daysLeft, 'icon' => '📅']
                                ]
                            ];
                            $response['suggestions'] = [
                                ['icon' => 'ri-folder-line', 'text' => 'My Projects'],
                                ['icon' => 'ri-team-line', 'text' => 'My Team'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        } elseif ($role === 'departmentcoordinator') {
                            $dept = $_SESSION['department'] ?? '';
                            $deptFilter = buildDeptFilter($dept);
                            $dp = $deptFilter['placeholders'];
                            $dt = $deptFilter['types'];
                            $dv = $deptFilter['values'];

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp)");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $totalP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp) AND status = 'pending'");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $pendingP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp) AND status = 'approved'");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $approvedP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM teams WHERE department IN ($dp)");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $teamCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $response['reply'] = "📊 **Department Dashboard** ($dept)";
                            $response['chart'] = [
                                'type' => 'department',
                                'title' => "$dept Department",
                                'donut' => [
                                    'label' => 'Projects',
                                    'total' => (int)$totalP,
                                    'segments' => [
                                        ['label' => 'Approved', 'value' => (int)$approvedP, 'color' => '#22c55e'],
                                        ['label' => 'Pending', 'value' => (int)$pendingP, 'color' => '#f59e0b'],
                                        ['label' => 'Rejected', 'value' => isset($rejectedP) ? (int)$rejectedP : 0, 'color' => '#ef4444']
                                    ]
                                ],
                                'bars' => [
                                    ['label' => 'Total Projects', 'value' => (int)$totalP, 'icon' => '📁'],
                                    ['label' => 'Pending Review', 'value' => (int)$pendingP, 'icon' => '⏳'],
                                    ['label' => 'Approved', 'value' => (int)$approvedP, 'icon' => '✅'],
                                    ['label' => 'Teams', 'value' => (int)$teamCount, 'icon' => '👥']
                                ]
                            ];
                            $response['suggestions'] = [
                                ['icon' => 'ri-checkbox-circle-line', 'text' => 'Pending Reviews'],
                                ['icon' => 'ri-group-line', 'text' => 'Students'],
                                ['icon' => 'ri-team-line', 'text' => 'Teams'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        } else {
                            $totalP = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects"))['cnt'];
                            $totalU = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
                            $totalD = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT department) as cnt FROM projects WHERE department IS NOT NULL AND department != ''"))['cnt'];
                            $approvedP = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];
                            $pendingP = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending'"))['cnt'];

                            $response['reply'] = "📊 **System Dashboard**\n"
                                . "• **Total Projects:** $totalP\n"
                                . "• **Pending:** $pendingP ⏳\n"
                                . "• **Approved:** $approvedP ✅\n"
                                . "• **Registered Users:** $totalU 👤\n"
                                . "• **Departments:** $totalD 🏢";
                            $response['suggestions'] = getChatSuggestions($role);
                        }
                    }
                }

                // --- My Projects (Student) ---
                elseif (preg_match('/(my project|my submission|view project|project status)/', $lowerMsg) && !preg_match('/(all|department|dept|pending|top)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $uid = $_SESSION['user_id'];
                        $teamId = null;
                        $tc = mysqli_prepare($conn, "SELECT t.id FROM team_members tm JOIN teams t ON tm.team_id = t.id WHERE tm.user_id = ?");
                        mysqli_stmt_bind_param($tc, "i", $uid);
                        mysqli_stmt_execute($tc);
                        $tr = mysqli_fetch_assoc(mysqli_stmt_get_result($tc));
                        if ($tr) $teamId = $tr['id'];
                        mysqli_stmt_close($tc);

                        if ($teamId) {
                            $ps = mysqli_prepare($conn, "SELECT title, status, category, score, created_at FROM projects WHERE team_id = ? ORDER BY created_at DESC LIMIT 5");
                            mysqli_stmt_bind_param($ps, "i", $teamId);
                        } else {
                            $ps = mysqli_prepare($conn, "SELECT title, status, category, score, created_at FROM projects WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
                            mysqli_stmt_bind_param($ps, "i", $uid);
                        }
                        mysqli_stmt_execute($ps);
                        $result = mysqli_stmt_get_result($ps);
                        $chatProjects = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $chatProjects[] = $row;
                        }
                        mysqli_stmt_close($ps);

                        if (empty($chatProjects)) {
                            $response['reply'] = "You haven't submitted any projects yet. Submit your first project to get started! 🚀";
                            $response['suggestions'] = [
                                ['icon' => 'ri-add-line', 'text' => 'Submit Project'],
                                ['icon' => 'ri-team-line', 'text' => 'My Team'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        } else {
                            $reply = "📂 **Your Projects** (" . count($chatProjects) . " latest)\n\n";
                            foreach ($chatProjects as $i => $p) {
                                $statusIcon = $p['status'] === 'approved' ? '✅' : ($p['status'] === 'rejected' ? '❌' : '⏳');
                                $scoreText = $p['score'] ? " | Score: {$p['score']}" : "";
                                $reply .= ($i + 1) . ". **" . $p['title'] . "** $statusIcon\n"
                                    . "   " . ucfirst($p['category']) . " | " . ucfirst($p['status']) . $scoreText . "\n\n";
                            }
                            $response['reply'] = $reply;
                            $response['suggestions'] = [
                                ['icon' => 'ri-bar-chart-line', 'text' => 'My Stats'],
                                ['icon' => 'ri-team-line', 'text' => 'My Team'],
                                ['icon' => 'ri-trophy-line', 'text' => 'Top Projects']
                            ];
                        }
                    }
                }

                // --- My Team ---
                elseif (preg_match('/(my team|team info|team member|team detail)/', $lowerMsg) && !preg_match('/(create|join|all|manage|list)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $uid = $_SESSION['user_id'];
                        $tc = mysqli_prepare($conn, "SELECT t.*, u.name as leader_name FROM team_members tm JOIN teams t ON tm.team_id = t.id LEFT JOIN users u ON t.leader_id = u.id WHERE tm.user_id = ?");
                        mysqli_stmt_bind_param($tc, "i", $uid);
                        mysqli_stmt_execute($tc);
                        $myTeamData = mysqli_fetch_assoc(mysqli_stmt_get_result($tc));
                        mysqli_stmt_close($tc);

                        if (!$myTeamData) {
                            $response['reply'] = "You haven't joined a team yet. Create one or join using an invite code!";
                            $response['suggestions'] = [
                                ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                                ['icon' => 'ri-group-line', 'text' => 'Join Team'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        } else {
                            $ms = mysqli_prepare($conn, "SELECT u.name, tm.role FROM team_members tm JOIN users u ON tm.user_id = u.id WHERE tm.team_id = ? ORDER BY tm.role = 'leader' DESC");
                            mysqli_stmt_bind_param($ms, "i", $myTeamData['id']);
                            mysqli_stmt_execute($ms);
                            $mr = mysqli_stmt_get_result($ms);
                            $members = [];
                            while ($m = mysqli_fetch_assoc($mr)) {
                                $members[] = $m;
                            }
                            mysqli_stmt_close($ms);

                            $reply = "👥 **Team: " . $myTeamData['team_name'] . "**\n"
                                . "• **Code:** " . $myTeamData['team_code'] . "\n"
                                . "• **Leader:** " . $myTeamData['leader_name'] . "\n"
                                . "• **Department:** " . $myTeamData['department'] . "\n"
                                . "• **Status:** " . ucfirst($myTeamData['status']) . "\n"
                                . "• **Members:** " . count($members) . "/" . $myTeamData['max_members'] . "\n\n";

                            foreach ($members as $m) {
                                $badge = $m['role'] === 'leader' ? '👑' : '👤';
                                $reply .= "  $badge " . $m['name'] . " (" . ucfirst($m['role']) . ")\n";
                            }

                            $response['reply'] = $reply;
                            $isLeader = ((int)$myTeamData['leader_id'] === (int)$uid);
                            $response['suggestions'] = $isLeader ? [
                                ['icon' => 'ri-mail-send-line', 'text' => 'Invite'],
                                ['icon' => 'ri-folder-line', 'text' => 'My Projects'],
                                ['icon' => 'ri-add-line', 'text' => 'Submit Project']
                            ] : [
                                ['icon' => 'ri-folder-line', 'text' => 'My Projects'],
                                ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- Announcements (View) ---
                elseif (preg_match('/(announcement|notice|bulletin)/', $lowerMsg) && !preg_match('/(create|post|new|add) (announcement|notice)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login to view announcements.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (in_array($role, ['admin', 'studentaffairs'])) {
                            $annRes = mysqli_query($conn, "SELECT a.title, a.message, u.name as author, a.is_featured, a.created_at FROM announcements a JOIN users u ON a.author_id = u.id ORDER BY a.created_at DESC LIMIT 5");
                        } else {
                            $safeRole = mysqli_real_escape_string($conn, $role);
                            $annRes = mysqli_query($conn, "SELECT a.title, a.message, u.name as author, a.is_featured, a.created_at FROM announcements a JOIN users u ON a.author_id = u.id WHERE a.target_role IN ('all', '$safeRole') ORDER BY a.created_at DESC LIMIT 5");
                        }
                        $anns = [];
                        while ($row = mysqli_fetch_assoc($annRes)) { $anns[] = $row; }

                        if (empty($anns)) {
                            $response['reply'] = "No announcements to show at this time. 📭";
                        } else {
                            $reply = "📢 **Recent Announcements**\n\n";
                            foreach ($anns as $i => $a) {
                                $featured = $a['is_featured'] ? "⭐ " : "";
                                $date = date('M d', strtotime($a['created_at']));
                                $reply .= ($i + 1) . ". $featured**" . $a['title'] . "**\n"
                                    . "   " . substr($a['message'], 0, 100) . (strlen($a['message']) > 100 ? "..." : "") . "\n"
                                    . "   By " . $a['author'] . " - $date\n\n";
                            }
                            $response['reply'] = $reply;
                        }

                        $sug = [];
                        if (in_array($role, ['admin', 'studentaffairs'])) {
                            $sug[] = ['icon' => 'ri-add-line', 'text' => 'Create Announcement'];
                        }
                        $sug[] = ['icon' => 'ri-bar-chart-line', 'text' => 'My Stats'];
                        $sug[] = ['icon' => 'ri-questionnaire-line', 'text' => 'Help'];
                        $response['suggestions'] = $sug;
                    }
                }

                // --- Create Announcement (Admin/SA) ---
                elseif (preg_match('/(create|post|new|add) announcement/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['admin', 'studentaffairs'])) {
                            $response['reply'] = "Only admins and student affairs can create announcements.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $_SESSION['chat_state'] = 'ANN_ASK_TITLE';
                            $_SESSION['chat_data'] = [];
                            $response['reply'] = "📢 Let's create an announcement! What is the **Title**?";
                        }
                    }
                }

                // --- Send Message ---
                elseif (preg_match('/(send message|compose message|write message|new message)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $_SESSION['chat_state'] = 'MSG_ASK_RECIPIENT';
                        $_SESSION['chat_data'] = [];
                        $response['reply'] = "📧 Who would you like to message? Enter their **email address**.";
                    }
                }

                // --- Messages / Inbox ---
                elseif (preg_match('/(^message|^inbox|^mail|unread message|my message|check message|view message)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login to check messages.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $uid = $_SESSION['user_id'];
                        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = ? AND is_read = 0");
                        mysqli_stmt_bind_param($stmt, "i", $uid);
                        mysqli_stmt_execute($stmt);
                        $unread = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                        mysqli_stmt_close($stmt);

                        $stmt = mysqli_prepare($conn, "SELECT m.subject, m.is_read, m.created_at, u.name as sender FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.recipient_id = ? ORDER BY m.created_at DESC LIMIT 5");
                        mysqli_stmt_bind_param($stmt, "i", $uid);
                        mysqli_stmt_execute($stmt);
                        $msgRes = mysqli_stmt_get_result($stmt);
                        $msgs = [];
                        while ($row = mysqli_fetch_assoc($msgRes)) { $msgs[] = $row; }
                        mysqli_stmt_close($stmt);

                        if (empty($msgs)) {
                            $response['reply'] = "📬 Your inbox is empty. No messages yet.";
                        } else {
                            $reply = "📬 **Inbox** ($unread unread)\n\n";
                            foreach ($msgs as $i => $m) {
                                $readIcon = $m['is_read'] ? '📖' : '📩';
                                $date = date('M d, H:i', strtotime($m['created_at']));
                                $reply .= "$readIcon **" . $m['subject'] . "**\n"
                                    . "   From: " . $m['sender'] . " - $date\n\n";
                            }
                            $response['reply'] = $reply;
                        }
                        $response['suggestions'] = [
                            ['icon' => 'ri-mail-send-line', 'text' => 'Send Message'],
                            ['icon' => 'ri-bar-chart-line', 'text' => 'My Stats'],
                            ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                        ];
                    }
                }

                // --- Guidelines ---
                elseif (preg_match('/(guideline|submission rule|judging criteria|submission requirement)/', $lowerMsg)) {
                    $response['reply'] = "📋 **SPARK'26 Guidelines**\n\n"
                        . "**General:**\n"
                        . "• Team size: Max 4 members\n"
                        . "• Deadline: Feb 15, 2026, 11:59 PM\n"
                        . "• Original work only\n"
                        . "• Must be currently enrolled\n\n"
                        . "**Project Requirements:**\n"
                        . "• Clear problem statement\n"
                        . "• Complete documentation\n"
                        . "• GitHub repository required\n"
                        . "• Working demo/prototype\n"
                        . "• PDF upload (max 10MB)\n\n"
                        . "**Judging Criteria:**\n"
                        . "• Innovation: 25%\n"
                        . "• Technical Complexity: 25%\n"
                        . "• Practicality: 20%\n"
                        . "• Presentation: 15%\n"
                        . "• Documentation: 15%";
                    $role = $_SESSION['role'] ?? null;
                    $response['suggestions'] = getChatSuggestions($role);
                }

                // --- Top Projects / Leaderboard ---
                elseif (preg_match('/(top project|leaderboard|ranking|best project|winner|scores)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login to view the leaderboard.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        $dept = $_SESSION['department'] ?? '';

                        if ($role === 'departmentcoordinator') {
                            $deptFilter = buildDeptFilter($dept);
                            $dp = $deptFilter['placeholders'];
                            $dt = $deptFilter['types'];
                            $dv = $deptFilter['values'];
                            $stmt = mysqli_prepare($conn, "SELECT p.title, p.score, p.category, u.name as student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'approved' AND p.score > 0 AND p.department IN ($dp) ORDER BY p.score DESC LIMIT 10");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                        } else {
                            $stmt = mysqli_prepare($conn, "SELECT p.title, p.score, p.category, u.name as student_name FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'approved' AND p.score > 0 ORDER BY p.score DESC LIMIT 10");
                        }
                        mysqli_stmt_execute($stmt);
                        $topRes = mysqli_stmt_get_result($stmt);
                        $tops = [];
                        while ($row = mysqli_fetch_assoc($topRes)) { $tops[] = $row; }
                        mysqli_stmt_close($stmt);

                        if (empty($tops)) {
                            $response['reply'] = "🏆 No scored projects yet. Projects will appear here after judging.";
                        } else {
                            $medals = ['🥇', '🥈', '🥉'];
                            $reply = "🏆 **Top Projects Leaderboard**\n\n";
                            foreach ($tops as $i => $p) {
                                $rank = $i < 3 ? $medals[$i] : "#" . ($i + 1);
                                $reply .= "$rank **" . $p['title'] . "** — " . $p['score'] . "/100\n"
                                    . "   By " . $p['student_name'] . " | " . ucfirst($p['category']) . "\n\n";
                            }
                            $response['reply'] = $reply;
                        }
                        $response['suggestions'] = getChatSuggestions($role);
                    }
                }

                // --- Pending Reviews (Coordinator/Admin/SA) ---
                elseif (preg_match('/(pending review|pending project|review project|pending approval|awaiting review|^approval|approval status)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['departmentcoordinator', 'admin', 'studentaffairs'])) {
                            $response['reply'] = "This feature is only available for coordinators and administrators.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            // Get counts
                            if ($role === 'departmentcoordinator') {
                                $dept = $_SESSION['department'] ?? '';
                                $deptFilter = buildDeptFilter($dept);
                                $dp = $deptFilter['placeholders'];
                                $dt = $deptFilter['types'];
                                $dv = $deptFilter['values'];

                                $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending' AND department IN ($dp)");
                                mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                                mysqli_stmt_execute($stmt);
                                $pendCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                                mysqli_stmt_close($stmt);

                                $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved' AND department IN ($dp)");
                                mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                                mysqli_stmt_execute($stmt);
                                $apprCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                                mysqli_stmt_close($stmt);

                                $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'rejected' AND department IN ($dp)");
                                mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                                mysqli_stmt_execute($stmt);
                                $rejCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                                mysqli_stmt_close($stmt);

                                $stmt = mysqli_prepare($conn, "SELECT p.id, p.title, p.category, u.name as student_name, p.created_at FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'pending' AND p.department IN ($dp) ORDER BY p.created_at DESC LIMIT 10");
                                mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            } else {
                                $pendCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending'"))['cnt'];
                                $apprCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];
                                $rejCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'rejected'"))['cnt'];

                                $stmt = mysqli_prepare($conn, "SELECT p.id, p.title, p.category, u.name as student_name, p.created_at FROM projects p LEFT JOIN users u ON p.student_id = u.id WHERE p.status = 'pending' ORDER BY p.created_at DESC LIMIT 10");
                            }
                            mysqli_stmt_execute($stmt);
                            $pendRes = mysqli_stmt_get_result($stmt);
                            $pendings = [];
                            while ($row = mysqli_fetch_assoc($pendRes)) { $pendings[] = $row; }
                            mysqli_stmt_close($stmt);

                            $reply = "📋 **Review Summary**\n"
                                . "• Pending: $pendCount ⏳\n"
                                . "• Approved: $apprCount ✅\n"
                                . "• Rejected: $rejCount ❌\n\n";

                            if (empty($pendings)) {
                                $reply .= "✅ No pending projects to review. All caught up!";
                            } else {
                                $reply .= "**Pending Projects:**\n\n";
                                foreach ($pendings as $i => $p) {
                                    $date = date('M d', strtotime($p['created_at']));
                                    $reply .= ($i + 1) . ". **" . $p['title'] . "** (ID: " . $p['id'] . ")\n"
                                        . "   By " . $p['student_name'] . " | " . ucfirst($p['category']) . " | $date\n\n";
                                }
                                $reply .= "💡 Type **approve [ID]** or **reject [ID]** to review.";
                            }
                            $response['reply'] = $reply;
                            $response['suggestions'] = [
                                ['icon' => 'ri-bar-chart-line', 'text' => 'Department Stats'],
                                ['icon' => 'ri-group-line', 'text' => 'Students'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- Approve/Reject Project by ID ---
                elseif (preg_match('/^(approve|reject)\s+(\d+)/', $lowerMsg, $reviewMatch)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['departmentcoordinator', 'admin', 'studentaffairs'])) {
                            $response['reply'] = "Only coordinators and admins can review projects.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $decision = $reviewMatch[1];
                            $projectId = (int)$reviewMatch[2];

                            $stmt = mysqli_prepare($conn, "SELECT title, department FROM projects WHERE id = ? AND status = 'pending'");
                            mysqli_stmt_bind_param($stmt, "i", $projectId);
                            mysqli_stmt_execute($stmt);
                            $project = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                            mysqli_stmt_close($stmt);

                            if (!$project) {
                                $response['reply'] = "Project #$projectId not found or is not pending review.";
                                $response['suggestions'] = [
                                    ['icon' => 'ri-checkbox-circle-line', 'text' => 'Pending Reviews'],
                                    ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                                ];
                            } else {
                                $_SESSION['chat_state'] = 'REVIEW_CONFIRM';
                                $_SESSION['chat_data'] = [
                                    'project_id' => $projectId,
                                    'project_title' => $project['title'],
                                    'decision' => $decision === 'approve' ? 'approved' : 'rejected'
                                ];
                                $response['reply'] = "You are about to **$decision** project:\n\n**" . $project['title'] . "** (ID: $projectId)\n\nAdd a review comment (or type **skip** to proceed without one):";
                            }
                        }
                    }
                }

                // --- Department Stats (Coordinator) ---
                elseif (preg_match('/(department stat|dept stat|department overview|dept overview|department summary)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if ($role !== 'departmentcoordinator') {
                            $response['reply'] = "This feature is for department coordinators. Try **analytics** for system-wide stats.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $dept = $_SESSION['department'] ?? '';
                            $deptFilter = buildDeptFilter($dept);
                            $dp = $deptFilter['placeholders'];
                            $dt = $deptFilter['types'];
                            $dv = $deptFilter['values'];

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp)");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $totalP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp) AND status = 'pending'");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $pendingP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp) AND status = 'approved'");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $approvedP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM projects WHERE department IN ($dp) AND status = 'rejected'");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $rejectedP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM teams WHERE department IN ($dp)");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $teamCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                            mysqli_stmt_close($stmt);

                            $stmt = mysqli_prepare($conn, "SELECT category, COUNT(*) as cnt FROM projects WHERE department IN ($dp) GROUP BY category ORDER BY cnt DESC");
                            mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                            mysqli_stmt_execute($stmt);
                            $catRes = mysqli_stmt_get_result($stmt);
                            $cats = [];
                            while ($row = mysqli_fetch_assoc($catRes)) { $cats[] = $row; }
                            mysqli_stmt_close($stmt);

                            $response['reply'] = "📊 **Department Statistics** ($dept)";

                            $catBars = [];
                            if (!empty($cats)) {
                                foreach ($cats as $c) {
                                    $catBars[] = ['label' => ucfirst($c['category']), 'value' => (int)$c['cnt'], 'icon' => '📂'];
                                }
                            }

                            $response['chart'] = [
                                'type' => 'department',
                                'title' => "$dept Department",
                                'donut' => [
                                    'label' => 'Projects',
                                    'total' => (int)$totalP,
                                    'segments' => [
                                        ['label' => 'Approved', 'value' => (int)$approvedP, 'color' => '#22c55e'],
                                        ['label' => 'Pending', 'value' => (int)$pendingP, 'color' => '#f59e0b'],
                                        ['label' => 'Rejected', 'value' => (int)$rejectedP, 'color' => '#ef4444']
                                    ]
                                ],
                                'bars' => array_merge(
                                    [
                                        ['label' => 'Total Projects', 'value' => (int)$totalP, 'icon' => '📁'],
                                        ['label' => 'Teams', 'value' => (int)$teamCount, 'icon' => '👥']
                                    ],
                                    $catBars
                                )
                            ];
                            $response['suggestions'] = [
                                ['icon' => 'ri-checkbox-circle-line', 'text' => 'Pending Reviews'],
                                ['icon' => 'ri-group-line', 'text' => 'Students'],
                                ['icon' => 'ri-team-line', 'text' => 'Teams'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- Student List (Coordinator/Admin) ---
                elseif (preg_match('/(student list|list student|view student|my student|student count)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['departmentcoordinator', 'admin', 'studentaffairs'])) {
                            $response['reply'] = "This feature is for coordinators and admins only.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $reply = "";
                            if ($role === 'departmentcoordinator') {
                                $dept = $_SESSION['department'] ?? '';
                                $deptFilter = buildDeptFilter($dept);
                                $dp = $deptFilter['placeholders'];
                                $dt = $deptFilter['types'];
                                $dv = $deptFilter['values'];

                                $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM users WHERE department IN ($dp) AND role = 'student'");
                                mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                                mysqli_stmt_execute($stmt);
                                $totalStudents = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];
                                mysqli_stmt_close($stmt);

                                $stmt = mysqli_prepare($conn, "SELECT name, email, department, year FROM users WHERE department IN ($dp) AND role = 'student' ORDER BY name LIMIT 10");
                                mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                                $reply = "👨‍🎓 **Students** ($dept) — $totalStudents total\n\n";
                            } else {
                                $totalStudents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'student'"))['cnt'];
                                $stmt = mysqli_prepare($conn, "SELECT name, email, department, year FROM users WHERE role = 'student' ORDER BY name LIMIT 10");
                                $reply = "👨‍🎓 **All Students** — $totalStudents total\n\n";
                            }
                            mysqli_stmt_execute($stmt);
                            $stuRes = mysqli_stmt_get_result($stmt);
                            $students = [];
                            while ($row = mysqli_fetch_assoc($stuRes)) { $students[] = $row; }
                            mysqli_stmt_close($stmt);

                            if (empty($students)) {
                                $reply .= "No students found.";
                            } else {
                                foreach ($students as $i => $s) {
                                    $reply .= ($i + 1) . ". **" . $s['name'] . "**\n"
                                        . "   " . ($s['department'] ?? '-') . " | " . ($s['year'] ?? '-') . "\n\n";
                                }
                                if ($totalStudents > 10) {
                                    $reply .= "... and " . ($totalStudents - 10) . " more.";
                                }
                            }
                            $response['reply'] = $reply;
                            $response['suggestions'] = [
                                ['icon' => 'ri-checkbox-circle-line', 'text' => 'Pending Reviews'],
                                ['icon' => 'ri-bar-chart-line', 'text' => 'Department Stats'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- All Projects Summary (Admin/SA) ---
                elseif (preg_match('/(all project|total project|project count|project summary|project overview)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['admin', 'studentaffairs'])) {
                            $response['reply'] = "This feature is for admins and student affairs only. Try **my projects** instead.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects"))['cnt'];
                            $pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending'"))['cnt'];
                            $approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];
                            $rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'rejected'"))['cnt'];

                            $catRes = mysqli_query($conn, "SELECT category, COUNT(*) as cnt FROM projects GROUP BY category ORDER BY cnt DESC");
                            $cats = [];
                            while ($row = mysqli_fetch_assoc($catRes)) { $cats[] = $row; }

                            $deptRes = mysqli_query($conn, "SELECT department, COUNT(*) as cnt FROM projects WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY cnt DESC LIMIT 5");
                            $depts = [];
                            while ($row = mysqli_fetch_assoc($deptRes)) { $depts[] = $row; }

                            $reply = "📂 **All Projects Overview**\n\n"
                                . "**Status:**\n"
                                . "• Total: $total\n"
                                . "• Pending: $pending ⏳\n"
                                . "• Approved: $approved ✅\n"
                                . "• Rejected: $rejected ❌\n\n";

                            if (!empty($cats)) {
                                $reply .= "**By Category:**\n";
                                foreach ($cats as $c) {
                                    $reply .= "• " . ucfirst($c['category']) . ": " . $c['cnt'] . "\n";
                                }
                                $reply .= "\n";
                            }
                            if (!empty($depts)) {
                                $reply .= "**Top Departments:**\n";
                                foreach ($depts as $d) {
                                    $reply .= "• " . $d['department'] . ": " . $d['cnt'] . "\n";
                                }
                            }
                            $response['reply'] = $reply;
                            $response['suggestions'] = [
                                ['icon' => 'ri-checkbox-circle-line', 'text' => 'Approvals'],
                                ['icon' => 'ri-pie-chart-line', 'text' => 'Analytics'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- Analytics (Admin/SA) ---
                elseif (preg_match('/(^analytics|overall stat|system stat|^report|system overview)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['admin', 'studentaffairs'])) {
                            $response['reply'] = "Analytics is available for admins and student affairs only.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $totalP = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects"))['cnt'];
                            $totalU = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
                            $totalS = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'student'"))['cnt'];
                            $totalT = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM teams"))['cnt'];
                            $totalD = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT department) as cnt FROM projects WHERE department IS NOT NULL AND department != ''"))['cnt'];
                            $approvedP = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];
                            $pendingP = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'pending'"))['cnt'];
                            $rejectedP = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'rejected'"))['cnt'];
                            $approvalRate = $totalP > 0 ? round(($approvedP / $totalP) * 100) : 0;

                            $response['reply'] = "📊 **SPARK'26 Analytics**";
                            $response['chart'] = [
                                'type' => 'analytics',
                                'title' => "SPARK'26 Analytics",
                                'donut' => [
                                    'label' => 'Projects',
                                    'total' => (int)$totalP,
                                    'segments' => [
                                        ['label' => 'Approved', 'value' => (int)$approvedP, 'color' => '#22c55e'],
                                        ['label' => 'Pending', 'value' => (int)$pendingP, 'color' => '#f59e0b'],
                                        ['label' => 'Rejected', 'value' => (int)$rejectedP, 'color' => '#ef4444']
                                    ]
                                ],
                                'bars' => [
                                    ['label' => 'Projects', 'value' => (int)$totalP, 'icon' => '📁'],
                                    ['label' => 'Users', 'value' => (int)$totalU, 'icon' => '👤'],
                                    ['label' => 'Students', 'value' => (int)$totalS, 'icon' => '🎓'],
                                    ['label' => 'Teams', 'value' => (int)$totalT, 'icon' => '👥'],
                                    ['label' => 'Departments', 'value' => (int)$totalD, 'icon' => '🏛️']
                                ]
                            ];
                            $response['suggestions'] = [
                                ['icon' => 'ri-folder-line', 'text' => 'All Projects'],
                                ['icon' => 'ri-checkbox-circle-line', 'text' => 'Approvals'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- Teams Overview (Coordinator/Admin) ---
                elseif (preg_match('/(all team|team list|view team|manage team|teams overview)/', $lowerMsg) && !preg_match('/(create|join|my) team/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['departmentcoordinator', 'admin', 'studentaffairs'])) {
                            $response['reply'] = "This feature is for coordinators and admins only.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            if ($role === 'departmentcoordinator') {
                                $dept = $_SESSION['department'] ?? '';
                                $deptFilter = buildDeptFilter($dept);
                                $dp = $deptFilter['placeholders'];
                                $dt = $deptFilter['types'];
                                $dv = $deptFilter['values'];
                                $stmt = mysqli_prepare($conn, "SELECT t.team_name, t.status, u.name as leader_name, (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as member_count FROM teams t LEFT JOIN users u ON t.leader_id = u.id WHERE t.department IN ($dp) ORDER BY t.created_at DESC LIMIT 10");
                                mysqli_stmt_bind_param($stmt, $dt, ...$dv);
                                $title = "Teams ($dept)";
                            } else {
                                $stmt = mysqli_prepare($conn, "SELECT t.team_name, t.status, t.department, u.name as leader_name, (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) as member_count FROM teams t LEFT JOIN users u ON t.leader_id = u.id ORDER BY t.created_at DESC LIMIT 10");
                                $title = "All Teams";
                            }
                            mysqli_stmt_execute($stmt);
                            $teamRes = mysqli_stmt_get_result($stmt);
                            $chatTeams = [];
                            while ($row = mysqli_fetch_assoc($teamRes)) { $chatTeams[] = $row; }
                            mysqli_stmt_close($stmt);

                            if (empty($chatTeams)) {
                                $response['reply'] = "👥 No teams found yet.";
                            } else {
                                $reply = "👥 **$title**\n\n";
                                foreach ($chatTeams as $i => $t) {
                                    $statusIcon = $t['status'] === 'open' ? '🟢' : '🔴';
                                    $deptInfo = isset($t['department']) ? " | " . $t['department'] : "";
                                    $reply .= ($i + 1) . ". **" . $t['team_name'] . "** $statusIcon\n"
                                        . "   Leader: " . $t['leader_name'] . " | " . $t['member_count'] . " members" . $deptInfo . "\n\n";
                                }
                                $response['reply'] = $reply;
                            }
                            $response['suggestions'] = getChatSuggestions($role);
                        }
                    }
                }

                // --- Coordinators (Admin/SA) ---
                elseif (preg_match('/(coordinator list|view coordinator|^coordinators$|dept coordinator|department coordinator)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['admin', 'studentaffairs'])) {
                            $response['reply'] = "This feature is for admins and student affairs only.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $coordRes = mysqli_query($conn, "SELECT u.name, u.email, u.department, u.status, (SELECT COUNT(*) FROM projects WHERE department = u.department) as project_count FROM users u WHERE u.role = 'departmentcoordinator' ORDER BY u.department");
                            $coords = [];
                            while ($row = mysqli_fetch_assoc($coordRes)) { $coords[] = $row; }

                            if (empty($coords)) {
                                $response['reply'] = "No coordinators assigned yet.";
                            } else {
                                $reply = "🎓 **Department Coordinators** (" . count($coords) . ")\n\n";
                                foreach ($coords as $i => $c) {
                                    $statusIcon = $c['status'] === 'active' ? '🟢' : '🔴';
                                    $reply .= ($i + 1) . ". **" . $c['name'] . "** $statusIcon\n"
                                        . "   " . $c['department'] . " | " . $c['project_count'] . " projects\n"
                                        . "   " . $c['email'] . "\n\n";
                                }
                                $response['reply'] = $reply;
                            }
                            $response['suggestions'] = [
                                ['icon' => 'ri-pie-chart-line', 'text' => 'Analytics'],
                                ['icon' => 'ri-folder-line', 'text' => 'All Projects'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- Judging (Admin/SA/Coordinator) ---
                elseif (preg_match('/(^judging|^judge|scoring progress|score progress|judging progress)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['admin', 'studentaffairs', 'departmentcoordinator'])) {
                            $response['reply'] = "Judging info is available for coordinators and admins.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $totalApproved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved'"))['cnt'];
                            $scored = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM projects WHERE status = 'approved' AND score > 0"))['cnt'];
                            $unscored = $totalApproved - $scored;
                            $progress = $totalApproved > 0 ? round(($scored / $totalApproved) * 100) : 0;

                            $response['reply'] = "⚖️ **Judging Progress**\n\n"
                                . "• Approved Projects: $totalApproved\n"
                                . "• Scored: $scored ✅\n"
                                . "• Unscored: $unscored ⏳\n"
                                . "• Progress: **$progress%**\n\n"
                                . ($progress < 100 ? "📝 $unscored projects awaiting scores." : "🎉 All projects scored! Ready for results.");
                            $response['suggestions'] = [
                                ['icon' => 'ri-trophy-line', 'text' => 'Top Projects'],
                                ['icon' => 'ri-folder-line', 'text' => 'All Projects'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- Departments Overview (Admin) ---
                elseif (preg_match('/(^departments$|department list|all department|dept list)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $role = $_SESSION['role'];
                        if (!in_array($role, ['admin', 'studentaffairs'])) {
                            $response['reply'] = "This feature is for admins only.";
                            $response['suggestions'] = getChatSuggestions($role);
                        } else {
                            $deptResult = mysqli_query($conn, "SELECT department, COUNT(*) as project_count FROM projects WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY project_count DESC");
                            $depts = [];
                            while ($row = mysqli_fetch_assoc($deptResult)) { $depts[] = $row; }

                            if (empty($depts)) {
                                $response['reply'] = "🏢 No department data yet.";
                            } else {
                                $reply = "🏢 **Departments Overview**\n\n";
                                foreach ($depts as $i => $d) {
                                    $coordRes = mysqli_query($conn, "SELECT name FROM users WHERE role = 'departmentcoordinator' AND department = '" . mysqli_real_escape_string($conn, $d['department']) . "' LIMIT 1");
                                    $coord = mysqli_fetch_assoc($coordRes);
                                    $coordName = $coord ? $coord['name'] : 'Not assigned';
                                    $reply .= ($i + 1) . ". **" . $d['department'] . "** — " . $d['project_count'] . " projects\n"
                                        . "   Coordinator: " . $coordName . "\n\n";
                                }
                                $response['reply'] = $reply;
                            }
                            $response['suggestions'] = [
                                ['icon' => 'ri-shield-user-line', 'text' => 'Coordinators'],
                                ['icon' => 'ri-pie-chart-line', 'text' => 'Analytics'],
                                ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                            ];
                        }
                    }
                }

                // --- View Schedule (with real data) ---
                elseif (preg_match('/(^schedule|view schedule|upcoming event|event schedule|timeline)/', $lowerMsg)) {
                    $scheRes = mysqli_query($conn, "SELECT title, description, event_date, event_type FROM schedule ORDER BY event_date ASC LIMIT 8");
                    $events = [];
                    while ($row = mysqli_fetch_assoc($scheRes)) { $events[] = $row; }

                    if (empty($events)) {
                        $response['reply'] = "📅 No schedule events found. SPARK'26 is on **Feb 15, 2026**.";
                    } else {
                        $reply = "📅 **SPARK'26 Schedule**\n\n";
                        foreach ($events as $e) {
                            $date = date('M d, Y', strtotime($e['event_date']));
                            $time = date('h:i A', strtotime($e['event_date']));
                            $typeIcon = match($e['event_type']) {
                                'milestone' => '🔵',
                                'deadline' => '🔴',
                                'event' => '🟢',
                                default => '⚪'
                            };
                            $reply .= "$typeIcon **" . $e['title'] . "**\n"
                                . "   $date at $time\n"
                                . ($e['description'] ? "   " . $e['description'] . "\n" : "") . "\n";
                        }
                        $response['reply'] = $reply;
                    }
                    $response['action'] = 'scroll_schedule';
                    $role = $_SESSION['role'] ?? null;
                    $response['suggestions'] = getChatSuggestions($role);
                }

                // --- Submit Project shortcut ---
                elseif (preg_match('/(submit project|new project|post project)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $response['reply'] = "📤 Opening the project submission form...";
                        $response['action'] = 'redirect';
                        $response['redirect_url'] = 'submitProject.php';
                        $response['suggestions'] = getChatSuggestions($_SESSION['role']);
                    }
                }

                // --- Settings ---
                elseif (preg_match('/(^setting|^preference|account setting|change password|update profile)/', $lowerMsg)) {
                    if (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $response['reply'] = "⚙️ Opening Settings where you can update your profile and change your password...";
                        $response['action'] = 'redirect';
                        $response['redirect_url'] = 'settings.php';
                        $response['suggestions'] = getChatSuggestions($_SESSION['role']);
                    }
                }

                // --- Navigate to Pages ---
                elseif (preg_match('/(go to|open|navigate|take me to|visit)\s+(.+)/i', $lowerMsg, $navMatch)) {
                    $page = strtolower(trim($navMatch[2]));
                    $role = $_SESSION['role'] ?? 'student';
                    $pageMap = [
                        'dashboard' => ['student' => 'studentDashboard.php', 'admin' => 'sparkAdmin.php', 'studentaffairs' => 'studentAffairs.php', 'departmentcoordinator' => 'departmentCoordinator.php'],
                        'my projects' => 'myProjects.php',
                        'projects' => ['student' => 'myProjects.php', 'default' => 'allProjects.php'],
                        'all projects' => 'allProjects.php',
                        'my team' => 'myTeam.php',
                        'teams' => 'teams.php',
                        'submit' => 'submitProject.php',
                        'profile' => 'profile.php',
                        'settings' => 'settings.php',
                        'messages' => 'messages.php',
                        'announcements' => 'announcements.php',
                        'schedule' => 'schedule.php',
                        'guidelines' => 'guidelines.php',
                        'analytics' => 'analytics.php',
                        'approvals' => 'approvals.php',
                        'users' => 'users.php',
                        'coordinators' => 'coordinators.php',
                        'departments' => 'departments.php',
                        'judging' => 'judging.php',
                        'review' => 'reviewApprove.php',
                        'leaderboard' => 'topProjects.php',
                        'students' => 'studentList.php',
                    ];

                    $url = null;
                    foreach ($pageMap as $key => $val) {
                        if (strpos($page, $key) !== false) {
                            if (is_array($val)) {
                                $url = $val[$role] ?? $val['default'] ?? array_values($val)[0];
                            } else {
                                $url = $val;
                            }
                            break;
                        }
                    }

                    if ($url && isset($_SESSION['user_id'])) {
                        $response['reply'] = "🔗 Opening **" . ucwords($page) . "**...";
                        $response['action'] = 'redirect';
                        $response['redirect_url'] = $url;
                    } elseif (!isset($_SESSION['user_id'])) {
                        $response['reply'] = "Please login first to access pages.";
                        $response['suggestions'] = getChatSuggestions();
                    } else {
                        $response['reply'] = "I couldn't find that page. Try **help** to see available commands.";
                        $response['suggestions'] = getChatSuggestions($role);
                    }
                }

                // --- General Queries (Fallback) ---
                else {
                    if (preg_match('/(hi|hello|hey|greetings|howdy|sup|yo)/', $lowerMsg)) {
                        $user = $_SESSION['name'] ?? 'there';
                        $greetings = [
                            "Hey $user! 👋 How can I help you today?",
                            "Hello $user! What can I do for you?",
                            "Hi $user! Need help with SPARK'26?"
                        ];
                        $response['reply'] = $greetings[array_rand($greetings)];
                        $response['suggestions'] = getChatSuggestions($_SESSION['role'] ?? null);
                    } elseif (preg_match('/(thank|thanks|thx)/', $lowerMsg)) {
                        $response['reply'] = "You're welcome! Let me know if you need anything else. 😊";
                        $response['suggestions'] = [
                            ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                            ['icon' => 'ri-compass-line', 'text' => 'Tracks'],
                            ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                        ];
                    } elseif (preg_match('/(help|what can you do|options|menu|command)/', $lowerMsg)) {
                        $helpRole = $_SESSION['role'] ?? null;
                        $helpBase = "Here's what I can do:\n";
                        if (!isset($_SESSION['user_id'])) {
                            $helpBase .= "• **Register** — Create a new account\n"
                                . "• **Login** — Sign in to your account\n"
                                . "• **Schedule** — View event timeline\n"
                                . "• **Tracks** — Browse project tracks\n"
                                . "• **Guidelines** — View submission rules";
                        } else {
                            $helpBase .= "• **My Profile** — View your profile\n"
                                . "• **My Stats** — View your dashboard\n"
                                . "• **Schedule** — View event timeline\n"
                                . "• **Announcements** — Recent announcements\n"
                                . "• **Messages** — Check your inbox\n"
                                . "• **Guidelines** — Submission rules\n"
                                . "• **Top Projects** — View leaderboard\n";
                            if ($helpRole === 'student') {
                                $helpBase .= "\n**Student Commands:**\n"
                                    . "• **My Projects** — View your projects\n"
                                    . "• **My Team** — View team info\n"
                                    . "• **Submit Project** — Open submission form\n"
                                    . "• **Create Team** / **Join Team** / **Invite**";
                            } elseif ($helpRole === 'departmentcoordinator') {
                                $helpBase .= "\n**Coordinator Commands:**\n"
                                    . "• **Pending Reviews** — Review projects\n"
                                    . "• **Approve [ID]** / **Reject [ID]** — Review by ID\n"
                                    . "• **Department Stats** — Department overview\n"
                                    . "• **Students** — Student list\n"
                                    . "• **Teams** — Department teams\n"
                                    . "• **Judging** — Scoring progress";
                            } elseif (in_array($helpRole, ['admin', 'studentaffairs'])) {
                                $helpBase .= "\n**Admin Commands:**\n"
                                    . "• **All Projects** — Project overview\n"
                                    . "• **Analytics** — System statistics\n"
                                    . "• **Approvals** — Review summary\n"
                                    . "• **Coordinators** — View coordinators\n"
                                    . "• **Departments** — Department overview\n"
                                    . "• **Judging** — Scoring progress\n"
                                    . "• **Create Announcement** — Post announcement\n"
                                    . "• **Send Message** — Message a user";
                            }
                            $helpBase .= "\n\n• **Go to [page]** — Navigate to any page\n"
                                . "• **Logout** — Sign out";
                        }
                        $response['reply'] = $helpBase;
                        $response['suggestions'] = getChatSuggestions($helpRole);
                    } elseif (strpos($lowerMsg, 'date') !== false || strpos($lowerMsg, 'when') !== false) {
                        $response['reply'] = "📅 SPARK'26 is scheduled for **Feb 15, 2026**. Type **schedule** for the full timeline!";
                        $response['action'] = 'scroll_schedule';
                        $response['suggestions'] = getChatSuggestions($_SESSION['role'] ?? null);
                    } elseif (strpos($lowerMsg, 'track') !== false || strpos($lowerMsg, 'topic') !== false || strpos($lowerMsg, 'domain') !== false) {
                        $response['reply'] = "We have 5 tracks: **AI/ML**, **Software Dev**, **HealthTech**, **Green Energy**, and **Open Innovation**. Let me show you!";
                        $response['action'] = 'scroll_tracks';
                        $response['suggestions'] = [
                            ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                            ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                            ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                        ];
                    } elseif (preg_match('/(team|squad|group)/', $lowerMsg)) {
                        $response['reply'] = "I can help with teams! Try:\n• **Create Team** — Start a new one\n• **Join Team** — Use an invite code\n• **Invite** — Add members to your team";
                        $response['suggestions'] = [
                            ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                            ['icon' => 'ri-group-line', 'text' => 'Join Team'],
                            ['icon' => 'ri-mail-send-line', 'text' => 'Invite']
                        ];
                    } elseif (preg_match('/(bye|goodbye|see you|later)/', $lowerMsg)) {
                        $response['reply'] = "Goodbye! Good luck with SPARK'26! 🚀";
                    } else {
                        $fallbacks = [
                            "I'm not sure I understand that. Try saying **help** to see what I can do!",
                            "Hmm, I didn't catch that. Type **help** to see all available commands.",
                            "I'm still learning! Say **help** to see everything I can do for you."
                        ];
                        $response['reply'] = $fallbacks[array_rand($fallbacks)];
                        $response['suggestions'] = getChatSuggestions($_SESSION['role'] ?? null);
                        // Keep this line to prevent overwrite below
                        $response['suggestions'] = getChatSuggestions($_SESSION['role'] ?? null);
                    }
                }
                break;

            // ==========================================
            // NOTIFICATION DECISION FLOW
            // ==========================================
            case 'INVITE_DECISION':
                if ($lowerMsg === 'accept') {
                    $inviteId = $_SESSION['chat_data']['invite_id'];
                    $teamId = $_SESSION['chat_data']['team_id'];
                    $userId = $_SESSION['user_id'];

                    // Add to team
                    mysqli_query($conn, "INSERT INTO team_members (team_id, user_id, role) VALUES ($teamId, $userId, 'member')");
                    // Update invite
                    mysqli_query($conn, "UPDATE team_invitations SET status = 'accepted' WHERE id = $inviteId");

                    $response['reply'] = "🎉 Accepted! You are now a member of **" . $_SESSION['chat_data']['team_name'] . "**. Reloading dashboard...";
                    $response['action'] = 'reload';
                    $_SESSION['chat_state'] = 'IDLE';
                    $response['suggestions'] = [
                        ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                        ['icon' => 'ri-compass-line', 'text' => 'Tracks'],
                        ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                    ];
                } elseif ($lowerMsg === 'decline') {
                    $inviteId = $_SESSION['chat_data']['invite_id'];
                    mysqli_query($conn, "UPDATE team_invitations SET status = 'declined' WHERE id = $inviteId");
                    $response['reply'] = "Invitation declined.";
                    $_SESSION['chat_state'] = 'IDLE';
                    $response['suggestions'] = [
                        ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                        ['icon' => 'ri-group-line', 'text' => 'Join Team'],
                        ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                    ];
                } else {
                    $response['reply'] = "Please type **Accept** or **Decline**.";
                    $response['options'] = ['Accept', 'Decline'];
                }
                break;

            // ==========================================
            // REGISTRATION FLOW
            // ==========================================
            case 'REG_ASK_NAME':
                $_SESSION['chat_data']['name'] = $message;
                $_SESSION['chat_state'] = 'REG_ASK_USER';
                $response['reply'] = "Nice to meet you, " . $message . "! Now, choose a unique **Username**.";
                break;

            case 'REG_ASK_USER':
                // Check if username exists
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
                mysqli_stmt_bind_param($stmt, "s", $message);
                mysqli_stmt_execute($stmt);
                if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                    $response['reply'] = "That username is taken. Please try another one.";
                } else {
                    $_SESSION['chat_data']['username'] = $message;
                    $_SESSION['chat_state'] = 'REG_ASK_DEPT';
                    $response['reply'] = "Got it! What is your **Department**?";
                    $response['options'] = ['AIDS','AIML','CSE','CSBS','CYBER','ECE','EEE','MECH','CIVIL','IT','VLSI','MBA','MCA'];
                }
                break;

            case 'REG_ASK_DEPT':
                $deptInput = strtoupper(trim($message));
                $validDepts = ['AIDS','AIML','CSE','CSBS','CYBER','ECE','EEE','MECH','CIVIL','IT','VLSI','MBA','MCA'];
                if (!in_array($deptInput, $validDepts)) {
                    $response['reply'] = "That's not a valid department. Please pick one:";
                    $response['options'] = ['AIDS','AIML','CSE','CSBS','CYBER','ECE','EEE','MECH','CIVIL','IT','VLSI','MBA','MCA'];
                } else {
                    $_SESSION['chat_data']['department'] = $deptInput;
                    $_SESSION['chat_state'] = 'REG_ASK_YEAR';
                    if ($deptInput === 'CYBER') {
                        $response['reply'] = "Cyber Security is available for **I Year** only. Selecting I Year automatically.";
                        // Auto-set year and skip to reg no
                        $_SESSION['chat_data']['year'] = 'I year';
                        $_SESSION['chat_state'] = 'REG_ASK_REGNO';
                        $prefix = '927625' . 'BSC';
                        $_SESSION['chat_data']['reg_prefix'] = $prefix;
                        $remaining = 12 - strlen($prefix);
                        $response['reply'] .= "\nYour register number starts with **$prefix**. Enter the remaining **$remaining digits** to complete it.";
                    } else {
                        $response['reply'] = "Which **Year** are you in?";
                        $response['options'] = ['I Year','II Year','III Year','IV Year'];
                    }
                }
                break;

            case 'REG_ASK_YEAR':
                // Normalize year input
                $yearInput = strtolower(trim($message));
                $yearMap = [
                    'i' => 'I year', '1' => 'I year', 'i year' => 'I year', '1st' => 'I year', 'first' => 'I year',
                    'ii' => 'II year', '2' => 'II year', 'ii year' => 'II year', '2nd' => 'II year', 'second' => 'II year',
                    'iii' => 'III year', '3' => 'III year', 'iii year' => 'III year', '3rd' => 'III year', 'third' => 'III year',
                    'iv' => 'IV year', '4' => 'IV year', 'iv year' => 'IV year', '4th' => 'IV year', 'fourth' => 'IV year'
                ];
                $normalizedYear = $yearMap[$yearInput] ?? null;
                if (!$normalizedYear) {
                    $response['reply'] = "Please select a valid year:";
                    $response['options'] = ['I Year','II Year','III Year','IV Year'];
                } else {
                    $_SESSION['chat_data']['year'] = $normalizedYear;
                    $_SESSION['chat_state'] = 'REG_ASK_REGNO';

                    // Compute register number prefix (same logic as register page)
                    $deptCodes = [
                        'AIDS'=>'BAD','AIML'=>'BAM','CSE'=>'BCS','CSBS'=>'BCB','CYBER'=>'BSC',
                        'ECE'=>'BEC','EEE'=>'BEE','MECH'=>'BME','CIVIL'=>'BCE','IT'=>'BIT',
                        'VLSI'=>'BEV','MBA'=>'MBA','MCA'=>'MCA'
                    ];
                    $yearCodes = ['I year'=>'927625','II year'=>'927624','III year'=>'927623','IV year'=>'927622'];

                    $dept = $_SESSION['chat_data']['department'];
                    $dCode = $deptCodes[$dept] ?? '';

                    // Special case: AIML IV Year uses BAL instead of BAM
                    if ($dept === 'AIML' && $normalizedYear === 'IV year') {
                        $dCode = 'BAL';
                    }

                    $yCode = $yearCodes[$normalizedYear] ?? '';
                    $prefix = $yCode . $dCode;
                    $_SESSION['chat_data']['reg_prefix'] = $prefix;

                    $remaining = 12 - strlen($prefix);
                    $response['reply'] = "Your register number starts with **$prefix**.\nEnter the remaining **$remaining digits** to complete it.";
                }
                break;

            case 'REG_ASK_REGNO':
                $prefix = $_SESSION['chat_data']['reg_prefix'] ?? '';
                $input = strtoupper(trim($message));

                // If user entered only the remaining digits, prepend prefix
                if ($prefix && !str_starts_with($input, $prefix)) {
                    $expectedRemaining = 12 - strlen($prefix);
                    if (strlen($input) === $expectedRemaining && ctype_alnum($input)) {
                        $input = $prefix . $input;
                    }
                }

                if (strlen($input) !== 12) {
                    $remaining = $prefix ? (12 - strlen($prefix)) : 12;
                    $response['reply'] = "Register number must be exactly **12 characters** (prefix **$prefix** + **$remaining digits**). Please try again.";
                } elseif ($prefix && !str_starts_with($input, $prefix)) {
                    $response['reply'] = "Register number must start with **$prefix** based on your department and year. Please re-enter.";
                } else {
                    // Check uniqueness
                    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE reg_no = ?");
                    mysqli_stmt_bind_param($stmt, "s", $input);
                    mysqli_stmt_execute($stmt);
                    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                        $response['reply'] = "That Register Number is already registered. Please try another.";
                    } else {
                        $_SESSION['chat_data']['reg_no'] = $input;
                        $_SESSION['chat_state'] = 'REG_ASK_EMAIL';
                        $response['reply'] = "Registered as **$input** ✓\nAlmost there! What is your **Email Address**?";
                    }
                }
                break;

            case 'REG_ASK_EMAIL':
                if (!filter_var($message, FILTER_VALIDATE_EMAIL)) {
                    $response['reply'] = "That doesn't look like a valid email. Please try again.";
                } else {
                    // Check email uniqueness
                    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
                    mysqli_stmt_bind_param($stmt, "s", $message);
                    mysqli_stmt_execute($stmt);
                    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                        $response['reply'] = "That email is already in use.";
                    } else {
                        $_SESSION['chat_data']['email'] = $message;
                        $_SESSION['chat_state'] = 'REG_ASK_PASS';
                        $response['reply'] = "Last step! Create a strong **Password**.";
                        $response['input_type'] = 'password';
                    }
                }
                break;

            case 'REG_ASK_PASS':
                $password = $message;
                $d = $_SESSION['chat_data'];

                // Insert User
                $stmt = mysqli_prepare($conn, "INSERT INTO users (name, username, department, year, reg_no, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'student')");
                mysqli_stmt_bind_param($stmt, "sssssss", $d['name'], $d['username'], $d['department'], $d['year'], $d['reg_no'], $d['email'], $password);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['chat_state'] = 'IDLE';
                    $_SESSION['chat_data'] = [];
                    $response['reply'] = "Registration Successful! 🎉 Type 'Login' to sign in to your new account.";
                    $response['suggestions'] = [
                        ['icon' => 'ri-login-box-line', 'text' => 'Login'],
                        ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                        ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                    ];
                } else {
                    $response['reply'] = "An error occurred during registration. Please try again later or use the main register page.";
                    $_SESSION['chat_state'] = 'IDLE';
                }
                break;

            // ==========================================
            // LOGIN FLOW
            // ==========================================
            case 'LOGIN_ASK_USER':
                $_SESSION['chat_data']['username'] = $message;
                $_SESSION['chat_state'] = 'LOGIN_ASK_PASS';
                $response['reply'] = "Enter your **Password**.";
                $response['input_type'] = 'password';
                break;

            case 'LOGIN_ASK_PASS':
                $username = $_SESSION['chat_data']['username'];
                $password = $message;

                $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? AND password = ?");
                mysqli_stmt_bind_param($stmt, "ss", $username, $password);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);

                if ($user) {
                    if ($user['status'] === 'inactive') {
                        $response['reply'] = "Account is inactive. Contact admin.";
                        $_SESSION['chat_state'] = 'IDLE';
                    } else {
                        // Set Session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['userid'] = $user['id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['department'] = $user['department'];
                        $_SESSION['year'] = $user['year'];

                        $_SESSION['chat_state'] = 'IDLE';
                        $_SESSION['chat_data'] = [];
                        $response['reply'] = "Welcome back, " . $user['name'] . "! You are now logged in. ✨";
                        $response['action'] = 'reload'; // Reload page to update UI
                        $response['suggestions'] = [
                            ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                            ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                            ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                        ];
                    }
                } else {
                    $response['reply'] = "Invalid credentials. Please try logging in again.";
                    $_SESSION['chat_state'] = 'IDLE';
                    $response['suggestions'] = [
                        ['icon' => 'ri-login-box-line', 'text' => 'Login'],
                        ['icon' => 'ri-user-add-line', 'text' => 'Register'],
                        ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                    ];
                }
                break;

            // ==========================================
            // CREATE TEAM FLOW
            // ==========================================
            case 'TEAM_CREATE_ASK_NAME':
                $_SESSION['chat_data']['team_name'] = $message;
                $_SESSION['chat_state'] = 'TEAM_CREATE_ASK_DESC';
                $response['reply'] = "Great name. Briefly describe your team's goal or project idea.";
                break;

            case 'TEAM_CREATE_ASK_DESC':
                $desc = $message;
                $tName = $_SESSION['chat_data']['team_name'];
                $leaderId = $_SESSION['user_id'];

                // Use routing logic for department (FE vs Department)
                $studentDept = $_SESSION['department'] ?? '';
                $studentYear = $_SESSION['year'] ?? '';
                $dept = getRoutingDepartment($studentYear, $studentDept);

                // Generate code
                $teamCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

                $stmt = mysqli_prepare($conn, "INSERT INTO teams (team_name, description, team_code, leader_id, department) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssis", $tName, $desc, $teamCode, $leaderId, $dept);

                if (mysqli_stmt_execute($stmt)) {
                    $teamId = mysqli_insert_id($conn);
                    // Add leader
                    mysqli_query($conn, "INSERT INTO team_members (team_id, user_id, role) VALUES ($teamId, $leaderId, 'leader')");

                    $_SESSION['chat_state'] = 'IDLE';
                    $response['reply'] = "Team '$tName' created successfully! 🚀\nYour Team Code is **$teamCode**. Share this with members to join.";
                    $response['suggestions'] = [
                        ['icon' => 'ri-mail-send-line', 'text' => 'Invite'],
                        ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                        ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                    ];
                } else {
                    $_SESSION['chat_state'] = 'IDLE';
                    $response['reply'] = "Failed to create team. Please try again.";
                    $response['suggestions'] = [
                        ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                        ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                    ];
                }
                break;

            // ==========================================
            // JOIN TEAM FLOW
            // ==========================================
            case 'TEAM_JOIN_ASK_CODE':
                $code = strtoupper(trim($message));
                $userId = $_SESSION['user_id'];

                $stmt = mysqli_prepare($conn, "SELECT * FROM teams WHERE team_code = ? AND status = 'open'");
                mysqli_stmt_bind_param($stmt, "s", $code);
                mysqli_stmt_execute($stmt);
                $team = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                if (!$team) {
                    $response['reply'] = "Invalid or closed team code. Type 'join team' to try again.";
                    $_SESSION['chat_state'] = 'IDLE';
                    $response['suggestions'] = [
                        ['icon' => 'ri-group-line', 'text' => 'Join Team'],
                        ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                        ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                    ];
                } else {
                    // Check max members
                    $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM team_members WHERE team_id = " . $team['id']))['c'];
                    if ($cnt >= $team['max_members']) {
                        $response['reply'] = "That team is full.";
                        $_SESSION['chat_state'] = 'IDLE';
                        $response['suggestions'] = [
                            ['icon' => 'ri-group-line', 'text' => 'Join Team'],
                            ['icon' => 'ri-team-line', 'text' => 'Create Team'],
                            ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                        ];
                    } else {
                        mysqli_query($conn, "INSERT INTO team_members (team_id, user_id, role) VALUES (" . $team['id'] . ", $userId, 'member')");

                        // Auto close if full
                        if ($cnt + 1 >= $team['max_members']) {
                            mysqli_query($conn, "UPDATE teams SET status = 'closed' WHERE id = " . $team['id']);
                        }

                        $response['reply'] = "Success! You have joined team **" . $team['team_name'] . "**.";
                        $_SESSION['chat_state'] = 'IDLE';
                        $response['suggestions'] = [
                            ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                            ['icon' => 'ri-compass-line', 'text' => 'Tracks'],
                            ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                        ];
                    }
                }
                break;

            // ==========================================
            // INVITE FLOW
            // ==========================================
            case 'TEAM_INVITE_ASK_USER':
                $target = trim($message);
                $leaderId = $_SESSION['user_id'];

                // Find user
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
                mysqli_stmt_bind_param($stmt, "ss", $target, $target);
                mysqli_stmt_execute($stmt);
                $uRes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                if (!$uRes) {
                    $response['reply'] = "User not found. Check the username or email.";
                } else {
                    $targetId = $uRes['id'];

                    if ($targetId == $leaderId) {
                        $response['reply'] = "You cannot invite yourself.";
                    } else {
                        // Get leader team
                        $teamStmt = mysqli_prepare($conn, "SELECT id, max_members FROM teams WHERE leader_id = ?");
                        mysqli_stmt_bind_param($teamStmt, "i", $leaderId);
                        mysqli_stmt_execute($teamStmt);
                        $teamRow = mysqli_fetch_assoc(mysqli_stmt_get_result($teamStmt));
                        $teamId = $teamRow['id'];

                        // Check if user already in a team
                        $checkTeam = mysqli_prepare($conn, "SELECT team_id FROM team_members WHERE user_id = ?");
                        mysqli_stmt_bind_param($checkTeam, "i", $targetId);
                        mysqli_stmt_execute($checkTeam);
                        if (mysqli_num_rows(mysqli_stmt_get_result($checkTeam)) > 0) {
                            $response['reply'] = "This user is already part of a team.";
                        } else {
                            // Check for pending invite
                            $checkInvite = mysqli_prepare($conn, "SELECT id FROM team_invitations WHERE team_id = ? AND invited_user_id = ? AND status = 'pending'");
                            mysqli_stmt_bind_param($checkInvite, "ii", $teamId, $targetId);
                            mysqli_stmt_execute($checkInvite);
                            if (mysqli_num_rows(mysqli_stmt_get_result($checkInvite)) > 0) {
                                $response['reply'] = "An invitation is already pending for this user.";
                            } else {
                                // Send Invite
                                $ins = mysqli_prepare($conn, "INSERT INTO team_invitations (team_id, invited_by, invited_user_id) VALUES (?, ?, ?)");
                                mysqli_stmt_bind_param($ins, "iii", $teamId, $leaderId, $targetId);
                                if (mysqli_stmt_execute($ins)) {
                                    $response['reply'] = "Invitation sent to $target! They will see it in their dashboard.";
                                    $_SESSION['chat_state'] = 'IDLE';
                                    $response['suggestions'] = [
                                        ['icon' => 'ri-mail-send-line', 'text' => 'Invite'],
                                        ['icon' => 'ri-calendar-line', 'text' => 'Schedule'],
                                        ['icon' => 'ri-compass-line', 'text' => 'Tracks']
                                    ];
                                } else {
                                    $response['reply'] = "Failed to send invitation. Please try again.";
                                }
                            }
                        }
                    }
                }
                break;

            // ==========================================
            // REVIEW PROJECT CONFIRM FLOW
            // ==========================================
            case 'REVIEW_CONFIRM':
                $projectId = $_SESSION['chat_data']['project_id'];
                $title = $_SESSION['chat_data']['project_title'];
                $decision = $_SESSION['chat_data']['decision'];
                $reviewerId = $_SESSION['user_id'];

                $comment = (in_array($lowerMsg, ['skip', 'no', 'none'])) ? '' : $message;

                $stmt = mysqli_prepare($conn, "UPDATE projects SET status = ?, reviewed_by = ?, review_comments = ?, reviewed_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sisi", $decision, $reviewerId, $comment, $projectId);

                if (mysqli_stmt_execute($stmt)) {
                    $icon = $decision === 'approved' ? '✅' : '❌';
                    $response['reply'] = "$icon Project **$title** has been **$decision** successfully!";
                    if ($comment) {
                        $response['reply'] .= "\nComment: $comment";
                    }
                } else {
                    $response['reply'] = "Failed to update project. Please try again.";
                }
                mysqli_stmt_close($stmt);

                $_SESSION['chat_state'] = 'IDLE';
                $_SESSION['chat_data'] = [];
                $response['suggestions'] = [
                    ['icon' => 'ri-checkbox-circle-line', 'text' => 'Pending Reviews'],
                    ['icon' => 'ri-bar-chart-line', 'text' => 'Department Stats'],
                    ['icon' => 'ri-questionnaire-line', 'text' => 'Help']
                ];
                break;

            // ==========================================
            // SEND MESSAGE FLOW
            // ==========================================
            case 'MSG_ASK_RECIPIENT':
                $email = trim($message);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response['reply'] = "That doesn't look like a valid email. Please enter a valid **email address**.";
                } else {
                    $stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email = ?");
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    mysqli_stmt_execute($stmt);
                    $recipient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                    mysqli_stmt_close($stmt);

                    if (!$recipient) {
                        $response['reply'] = "No user found with that email. Please try again.";
                    } else {
                        $_SESSION['chat_data']['recipient_id'] = $recipient['id'];
                        $_SESSION['chat_data']['recipient_name'] = $recipient['name'];
                        $_SESSION['chat_state'] = 'MSG_ASK_SUBJECT';
                        $response['reply'] = "Messaging **" . $recipient['name'] . "**. What is the **Subject**?";
                    }
                }
                break;

            case 'MSG_ASK_SUBJECT':
                $_SESSION['chat_data']['subject'] = $message;
                $_SESSION['chat_state'] = 'MSG_ASK_BODY';
                $response['reply'] = "Subject: **$message**\nNow type your **message**.";
                break;

            case 'MSG_ASK_BODY':
                $senderId = $_SESSION['user_id'];
                $recipientId = $_SESSION['chat_data']['recipient_id'];
                $subject = $_SESSION['chat_data']['subject'];
                $body = $message;

                $stmt = mysqli_prepare($conn, "INSERT INTO messages (sender_id, recipient_id, subject, message) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iiss", $senderId, $recipientId, $subject, $body);

                if (mysqli_stmt_execute($stmt)) {
                    $response['reply'] = "✅ Message sent to **" . $_SESSION['chat_data']['recipient_name'] . "** successfully!";
                } else {
                    $response['reply'] = "Failed to send message. Please try again.";
                }
                mysqli_stmt_close($stmt);

                $_SESSION['chat_state'] = 'IDLE';
                $_SESSION['chat_data'] = [];
                $response['suggestions'] = getChatSuggestions($_SESSION['role'] ?? null);
                break;

            // ==========================================
            // CREATE ANNOUNCEMENT FLOW
            // ==========================================
            case 'ANN_ASK_TITLE':
                $_SESSION['chat_data']['ann_title'] = $message;
                $_SESSION['chat_state'] = 'ANN_ASK_MSG';
                $response['reply'] = "Title: **$message**\nNow type the **announcement message**.";
                break;

            case 'ANN_ASK_MSG':
                $_SESSION['chat_data']['ann_message'] = $message;
                $_SESSION['chat_state'] = 'ANN_ASK_TARGET';
                $response['reply'] = "Who should see this announcement?";
                $response['options'] = ['All', 'Students', 'Coordinators', 'Student Affairs'];
                break;

            case 'ANN_ASK_TARGET':
                $targetMap = [
                    'all' => 'all',
                    'students' => 'student',
                    'student' => 'student',
                    'coordinators' => 'departmentcoordinator',
                    'coordinator' => 'departmentcoordinator',
                    'student affairs' => 'studentaffairs',
                    'studentaffairs' => 'studentaffairs'
                ];
                $target = $targetMap[$lowerMsg] ?? 'all';

                $annTitle = $_SESSION['chat_data']['ann_title'];
                $annMsg = $_SESSION['chat_data']['ann_message'];
                $authorId = $_SESSION['user_id'];

                $stmt = mysqli_prepare($conn, "INSERT INTO announcements (title, message, author_id, target_role, is_featured) VALUES (?, ?, ?, ?, 0)");
                mysqli_stmt_bind_param($stmt, "ssis", $annTitle, $annMsg, $authorId, $target);

                if (mysqli_stmt_execute($stmt)) {
                    $response['reply'] = "📢 Announcement **$annTitle** posted successfully!";
                } else {
                    $response['reply'] = "Failed to post announcement. Please try again.";
                }
                mysqli_stmt_close($stmt);

                $_SESSION['chat_state'] = 'IDLE';
                $_SESSION['chat_data'] = [];
                $response['suggestions'] = getChatSuggestions($_SESSION['role'] ?? null);
                break;
        }

        echo json_encode($response);
        exit();
        break;

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
        $studentDept = $_SESSION['department'] ?? '';
        $studentYear = $_SESSION['year'] ?? '';
        $department = getRoutingDepartment($studentYear, $studentDept);

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

        if (!$leaderRow || (int) $leaderRow['leader_id'] !== (int) $studentId) {
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

        $stmt = mysqli_prepare(
            $conn,
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

            if (!$lRow || (int) $lRow['leader_id'] !== (int) $userId) {
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
        $customRedirect = trim($_POST['redirect'] ?? '');

        if (!in_array($decision, ['approved', 'rejected', 'pending'])) {
            $redirect = $customRedirect ?: (($role === 'departmentcoordinator') ? 'reviewApprove.php' : 'approvals.php');
            redirectWith($redirect, 'error', 'Invalid review decision');
        }

        // If reverting to pending, clear review fields
        if ($decision === 'pending') {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE projects SET status = 'pending', reviewed_by = NULL, review_comments = NULL, reviewed_at = NULL WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "i", $projectId);
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE projects SET status = ?, reviewed_by = ?, review_comments = ?, reviewed_at = NOW() WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "sisi", $decision, $reviewerId, $comments, $projectId);
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $redirect = $customRedirect ?: (($role === 'departmentcoordinator') ? 'reviewApprove.php' : 'approvals.php');
            redirectWith($redirect, 'success', 'Project ' . $decision . ' successfully');
        } else {
            mysqli_stmt_close($stmt);
            $redirect = $customRedirect ?: 'reviewApprove.php';
            redirectWith($redirect, 'error', 'Failed to update project status');
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

        $stmt = mysqli_prepare(
            $conn,
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

        $stmt = mysqli_prepare(
            $conn,
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

        $stmt = mysqli_prepare(
            $conn,
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
    // JUDGING: Score project
    // ==========================================
    case 'score_project':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs', 'departmentcoordinator'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $projectId = intval($_POST['project_id'] ?? 0);
        $score = intval($_POST['score'] ?? 0);

        if ($score < 0 || $score > 100) {
            redirectWith('judging.php', 'error', 'Invalid score');
        }

        $stmt = mysqli_prepare($conn, "UPDATE projects SET score = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $score, $projectId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirectWith('judging.php', 'success', 'Score updated successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('judging.php', 'error', 'Failed to update score');
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

        $stmt = mysqli_prepare(
            $conn,
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
        if ($deleteUserId === (int) $_SESSION['user_id']) {
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
        $studentDept = $_SESSION['department'] ?? '';
        $studentYear = $_SESSION['year'] ?? '';
        $department = getRoutingDepartment($studentYear, $studentDept);

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

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO teams (team_name, description, team_code, leader_id, department) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sssis", $teamName, $description, $teamCode, $leaderId, $department);

        if (mysqli_stmt_execute($stmt)) {
            $teamId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Add leader as team member
            $memberStmt = mysqli_prepare(
                $conn,
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

        if ($teamInfo && (int) $teamInfo['leader_id'] === (int) $userId) {
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

        if (!$teamInfo || (int) $teamInfo['leader_id'] !== (int) $userId) {
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

        if (!$teamInfo || (int) $teamInfo['leader_id'] !== (int) $leaderId) {
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
        $stmt = mysqli_prepare(
            $conn,
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

        $stmt = mysqli_prepare(
            $conn,
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
    // COORDINATOR: Remove coordinator role
    // ==========================================
    case 'remove_coordinator':
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'studentaffairs'])) {
            redirectWith('login.php', 'error', 'Unauthorized');
        }

        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            redirectWith('coordinators.php', 'error', 'Invalid user');
        }

        $stmt = mysqli_prepare($conn, "UPDATE users SET role = 'student' WHERE id = ? AND role = 'departmentcoordinator'");
        mysqli_stmt_bind_param($stmt, "i", $userId);

        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            redirectWith('coordinators.php', 'success', 'Coordinator removed successfully');
        } else {
            mysqli_stmt_close($stmt);
            redirectWith('coordinators.php', 'error', 'Failed to remove coordinator');
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

    // ==========================================
    // EXPORT: Students List CSV
    // ==========================================
    case 'export_students':
        if (!isset($_SESSION['user_id'])) {
            redirectWith('login.php', 'error', 'Please login first');
        }

        $department = $_SESSION['department'] ?? '';
        $isFE = (strtoupper($department) === 'FE');
        $isMbaOrMca = in_array(strtoupper($department), ['MBA', 'MCA']);

        // Multi-department support Logic (Inlined)
        $deptFilter = [];
        if (strpos($department, 'AIML') !== false || strpos($department, 'AIDS') !== false) {
            $deptFilter = [
                'placeholders' => "'AIML','AIDS'",
                'types' => "ss",
                'values' => ["AIML", "AIDS"]
            ];
        } else {
            $deptFilter = [
                'placeholders' => "?",
                'types' => "s",
                'values' => [$department]
            ];
        }

        $dp = $deptFilter['placeholders'];
        $dt = $deptFilter['types'];
        $dv = $deptFilter['values'];

        // Build Query
        if ($isFE) {
            $where = "(department NOT IN ('MBA', 'MCA') AND year = 'I')";
            $types = "";
            $params = [];

            $sql = "SELECT u.name, u.email, u.year, u.status, u.created_at,
                    (SELECT COUNT(*) FROM projects WHERE student_id = u.id) AS project_count
                    FROM users u
                    WHERE $where AND u.role = 'student'
                    ORDER BY u.name ASC";

        } elseif ($isMbaOrMca) {
            $sql = "SELECT u.name, u.email, u.year, u.status, u.created_at,
                    (SELECT COUNT(*) FROM projects WHERE student_id = u.id) AS project_count
                    FROM users u
                    WHERE u.department IN ($dp) AND u.role = 'student'
                    ORDER BY u.name ASC";
            $params = $dv;
            $types = $dt;
        } else {
            // Exclude first years for other depts
            $where = "u.department IN ($dp) AND u.role = 'student' AND u.year != 'I'";
            $params = $dv;
            $types = $dt;

            $sql = "SELECT u.name, u.email, u.year, u.status, u.created_at,
                    (SELECT COUNT(*) FROM projects WHERE student_id = u.id) AS project_count
                    FROM users u
                    WHERE $where
                    ORDER BY u.name ASC";
        }

        // Execute
        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Generate CSV
        $filename = "students_" . strtolower(str_replace(' ', '_', $department)) . "_" . date('Y-m-d') . ".csv";

        // Clear output buffer to avoid any previous output corrupting the CSV
        if (ob_get_level())
            ob_end_clean();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Headers
        fputcsv($output, ['Student Name', 'Email', 'Year', 'Status', 'Registered Date', 'Projects Submitted']);

        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['name'],
                $row['email'],
                $row['year'] ?? '-',
                ucfirst($row['status']),
                date('Y-m-d', strtotime($row['created_at'])),
                $row['project_count']
            ]);
        }

        fclose($output);
        $stmt->close();
        exit();
        break;

    default:
        // No valid action, redirect to home
        if (!empty($action)) {
            redirectWith('index.php', 'error', 'Invalid action');
        }
        break;
}
?>