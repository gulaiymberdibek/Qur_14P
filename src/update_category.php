<?php
session_start();
require 'config.php';

header('Content-Type: application/json'); // Set response type for AJAX

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category']) && isset($_SESSION['user_id'])) {
    if (!isset($pdo)) {
        echo json_encode(["error" => "Database connection error"]);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $category = trim($_POST['category']);

    if (empty($category)) {
        echo json_encode(["error" => "Category cannot be empty"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET category = :category WHERE id = :user_id");
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(["success" => "Category updated successfully"]);
        } else {
            echo json_encode(["error" => "Failed to update category"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request"]);
}
?>
