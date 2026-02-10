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
            echo json_encode(['reply' => "Action cancelled. How else can I help you?", 'action' => 'reset_ui']);
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
                        }
                    }
                }

                // --- Start Registration ---
                elseif (strpos($lowerMsg, 'register') !== false || strpos($lowerMsg, 'signup') !== false) {
                    if (isset($_SESSION['user_id'])) {
                        $response['reply'] = "You are already logged in as " . $_SESSION['name'] . ". Please logout first to register a new account.";
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
                    } else {
                        // Check if already in a team
                        $chk = mysqli_query($conn, "SELECT id FROM team_members WHERE user_id = " . $_SESSION['user_id']);
                        if (mysqli_num_rows($chk) > 0) {
                            $response['reply'] = "You are already part of a team. You cannot create another one.";
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
                    } else {
                        // Check if already in a team
                        $chk = mysqli_query($conn, "SELECT id FROM team_members WHERE user_id = " . $_SESSION['user_id']);
                        if (mysqli_num_rows($chk) > 0) {
                            $response['reply'] = "You are already part of a team.";
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
                    } else {
                        // Check leader status
                        $chk = mysqli_query($conn, "SELECT id FROM teams WHERE leader_id = " . $_SESSION['user_id']);
                        if (mysqli_num_rows($chk) == 0) {
                            $response['reply'] = "Only team leaders can invite members.";
                        } else {
                            $_SESSION['chat_state'] = 'TEAM_INVITE_ASK_USER';
                            $response['reply'] = "Who would you like to invite? Enter their **Username** or **Email**.";
                        }
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
                    } elseif (preg_match('/(thank|thanks|thx)/', $lowerMsg)) {
                        $response['reply'] = "You're welcome! Let me know if you need anything else. 😊";
                    } elseif (preg_match('/(help|what can you do|options|menu)/', $lowerMsg)) {
                        $response['reply'] = "Here's what I can do:\n• **Register** — Create a new account\n• **Login** — Sign in to your account\n• **Create Team** — Start a new team\n• **Join Team** — Join with invite code\n• **Invite** — Invite a member\n• **Schedule** — View event timeline\n• **Tracks** — Browse project tracks";
                    } elseif (strpos($lowerMsg, 'schedule') !== false || strpos($lowerMsg, 'date') !== false || strpos($lowerMsg, 'when') !== false) {
                        $response['reply'] = "📅 SPARK'26 is scheduled for **Feb 15, 2026**. Let me scroll you to the timeline!";
                        $response['action'] = 'scroll_schedule';
                    } elseif (strpos($lowerMsg, 'track') !== false || strpos($lowerMsg, 'topic') !== false || strpos($lowerMsg, 'domain') !== false) {
                        $response['reply'] = "We have 5 tracks: **AI/ML**, **Software Dev**, **HealthTech**, **Green Energy**, and **Open Innovation**. Let me show you!";
                        $response['action'] = 'scroll_tracks';
                    } elseif (preg_match('/(team|squad|group)/', $lowerMsg)) {
                        $response['reply'] = "I can help with teams! Try:\n• **Create Team** — Start a new one\n• **Join Team** — Use an invite code\n• **Invite** — Add members to your team";
                    } elseif (preg_match('/(bye|goodbye|see you|later)/', $lowerMsg)) {
                        $response['reply'] = "Goodbye! Good luck with SPARK'26! 🚀";
                    } else {
                        $fallbacks = [
                            "I'm not sure I understand that. Try saying **help** to see what I can do!",
                            "Hmm, I didn't catch that. You can ask me about **registration**, **login**, **teams**, or the **schedule**.",
                            "I'm still learning! Try commands like **Register**, **Login**, **Create Team**, or **Schedule**."
                        ];
                        $response['reply'] = $fallbacks[array_rand($fallbacks)];
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
                } elseif ($lowerMsg === 'decline') {
                    $inviteId = $_SESSION['chat_data']['invite_id'];
                    mysqli_query($conn, "UPDATE team_invitations SET status = 'declined' WHERE id = $inviteId");
                    $response['reply'] = "Invitation declined.";
                    $_SESSION['chat_state'] = 'IDLE';
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
                    }
                } else {
                    $response['reply'] = "Invalid credentials. Please try logging in again.";
                    $_SESSION['chat_state'] = 'IDLE';
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
                } else {
                    $_SESSION['chat_state'] = 'IDLE';
                    $response['reply'] = "Failed to create team. Please try again.";
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
                } else {
                    // Check max members
                    $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM team_members WHERE team_id = " . $team['id']))['c'];
                    if ($cnt >= $team['max_members']) {
                        $response['reply'] = "That team is full.";
                        $_SESSION['chat_state'] = 'IDLE';
                    } else {
                        mysqli_query($conn, "INSERT INTO team_members (team_id, user_id, role) VALUES (" . $team['id'] . ", $userId, 'member')");

                        // Auto close if full
                        if ($cnt + 1 >= $team['max_members']) {
                            mysqli_query($conn, "UPDATE teams SET status = 'closed' WHERE id = " . $team['id']);
                        }

                        $response['reply'] = "Success! You have joined team **" . $team['team_name'] . "**.";
                        $_SESSION['chat_state'] = 'IDLE';
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
                                } else {
                                    $response['reply'] = "Failed to send invitation. Please try again.";
                                }
                            }
                        }
                    }
                }
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