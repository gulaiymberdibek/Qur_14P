

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require '../config.php';
$db=new DBS();
$pdo=$db->getConnection();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
   $content = trim($_POST['content']);
$content = mb_convert_encoding($content, 'UTF-8', 'auto'); // Ensure UTF-8 encoding


    // Validate content
    if (empty($content)) {
        die("Error: Question content cannot be empty.");
    }

    // Handle image upload (if any)
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];
        
        if (!in_array($fileType, $allowedFileTypes)) {
            die("Error: Only JPG, PNG, and GIF images are allowed.");
        }

        // Ensure the file size is within the limit (e.g., 5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            die("Error: File size exceeds the 5MB limit.");
        }

        // Generate a unique name for the image to avoid overwriting
        $targetDir = "uploads/";
        $imageName = uniqid("post_", true) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imagePath = $targetDir . $imageName;

        // Move the uploaded file to the target directory
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            die("Error: Image upload failed.");
        }
    }
	
	// Handle music upload (if any)
$musicPath = null;
if (isset($_FILES['music']) && $_FILES['music']['error'] === UPLOAD_ERR_OK) {
    $allowedMusicTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg'];
    $fileType = $_FILES['music']['type'];

    if (!in_array($fileType, $allowedMusicTypes)) {
        die("Error: Only MP3, WAV, and OGG files are allowed.");
    }

    // Ensure the file size is within the limit (e.g., 10MB)
    if ($_FILES['music']['size'] > 10 * 1024 * 1024) {
        die("Error: Music file size exceeds the 10MB limit.");
    }

    // Generate a unique name for the music file
    $targetDir = __DIR__ . "/uploads/music/"; // Use a local file path
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // Create directory if it doesn't exist
    }

    $musicName = uniqid("music_", true) . '.' . pathinfo($_FILES['music']['name'], PATHINFO_EXTENSION);
    $musicPath = $targetDir . $musicName;

    // Move the uploaded file to the target directory
    if (!move_uploaded_file($_FILES['music']['tmp_name'], $musicPath)) {
        die("Error: Music upload failed.");
    }

    // Save only the relative path in the database
    $musicPath = "uploads/music/" . $musicName;
}


	

    try {
        // Prepare the SQL query to insert the post with image
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at, image,music) VALUES (:user_id, :content, NOW(), :image,:music)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':image', $imagePath);
		  $stmt->bindParam(':music', $musicPath);
        $stmt->execute();

       // Redirect to categorize the post
$post_id = $pdo->lastInsertId();
header("Location:https://qur.kz");
exit;
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>
