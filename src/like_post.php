<?php
session_start();
require 'config.php';
$db=new DBS();
$pdo=$db->getConnection();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(['error' => 'Post ID is missing']);
    exit;
}

// Check if user already liked the post
$stmt = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$like = $stmt->fetch();

if ($like) {
    // Unlike the post (remove from database)
    $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
} else {
    // Like the post (insert into database)
    $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
    $stmt->execute([$post_id, $user_id]);
}

// Get updated like count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$stmt->execute([$post_id]);
$like_count = $stmt->fetchColumn();

// Get list of users who liked the post
$stmt = $pdo->prepare("SELECT users.name FROM likes JOIN users ON likes.user_id = users.id WHERE likes.post_id = ?");
$stmt->execute([$post_id]);
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['like_count' => $like_count, 'liked_by' => $users]);
?>
