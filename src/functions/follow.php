<?php
session_start();

require '../config.php';
$db=new DBS();
$pdo=$db->getConnection();

$logged_in_user_id = $_SESSION['user_id'] ?? null;
$followed_id = $_POST['followed_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$logged_in_user_id || !$followed_id || $logged_in_user_id == $followed_id) {
    header("Location: profile.php?user_id=$followed_id");
    exit;
}

if ($action === "follow") {
    $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (:follower_id, :followed_id)");
    $stmt->execute(['follower_id' => $logged_in_user_id, 'followed_id' => $followed_id]);
} elseif ($action === "unfollow") {
    $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
    $stmt->execute(['follower_id' => $logged_in_user_id, 'followed_id' => $followed_id]);
}

header("Location: profile.php?user_id=$followed_id");
exit;
