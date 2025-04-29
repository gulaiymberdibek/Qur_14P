<?php
session_start();

require '../config.php';
$db=new DBS();
$pdo=$db->getConnection();
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user_id']) || !isset($data['post_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$userId = $_SESSION['user_id'];
$postId = $data['post_id'];

try {
    // Delete related records first
    $pdo->prepare("DELETE FROM likes WHERE post_id = ?")->execute([$postId]);
    $pdo->prepare("DELETE FROM comments WHERE post_id = ?")->execute([$postId]);
    $pdo->prepare("DELETE FROM post_categories WHERE post_id = ?")->execute([$postId]);

    // Now delete the post
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Post not found or not owned by user."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
