
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'config.php';
$db=new DBS();
$pdo=$db->getConnection();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];
$user_comment = trim($_POST['user_comment']);
$comment_image = null;

// Handle Image Upload
if (!empty($_FILES['comment_image']['name'])) {
  $target_dir = __DIR__ . "/uploads/";


   

    // Sanitize and create unique image name
    $image_name = time() . '_' . basename($_FILES["comment_image"]["name"]);
    $target_file = $target_dir . $image_name;

    // Validate file upload
    if ($_FILES["comment_image"]["error"] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES["comment_image"]["tmp_name"], $target_file)) {
            $comment_image = $image_name;
        } else {
            echo json_encode(["error" => "Failed to move uploaded file."]);
            exit;
        }
    } else {
        echo json_encode(["error" => "File upload error: " . $_FILES["comment_image"]["error"]]);
        exit;
    }
}

// Insert comment into the database
$stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, user_comment, comment_image) VALUES (?, ?, ?, ?)");
$stmt->execute([$user_id, $post_id, $user_comment, $comment_image]);

// Fetch user name
$user_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_name = $user_stmt->fetchColumn();

echo json_encode([
    "success" => true,
    "user_name" => $user_name,
    "comment" => $user_comment,
    "comment_image" => $comment_image ? "uploads/" . $comment_image : null // Return image path if uploaded
]);
?>
