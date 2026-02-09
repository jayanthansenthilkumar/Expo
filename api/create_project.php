<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($title) || empty($description) || empty($category)) {
        echo json_encode(["status" => "error", "message" => "Title, Description and Category are required"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO projects (title, description, category, user_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $description, $category, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Project created successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>