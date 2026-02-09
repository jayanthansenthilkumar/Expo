<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Email and Password are required!"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, first_name, last_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];

            // Determine redirect URL based on role
            $redirect = 'index.php';
            switch ($user['role']) {
                case 'student':
                    $redirect = 'studentDashboard.php';
                    break;
                case 'advisor':
                    $redirect = 'classAdvisor.php'; // or advisor dashboard
                    break;
                case 'admin': // Assuming manual insertion for admin later
                    $redirect = 'projectAdmin.php';
                    break;
                default:
                    $redirect = 'index.php';
            }

            echo json_encode(["status" => "success", "message" => "Login successful!", "redirect" => $redirect]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid password!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found!"]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>