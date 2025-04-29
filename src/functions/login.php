<?php
session_start();
header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config.php';
$db=new DBS();
$pdo=$db->getConnection();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postData = file_get_contents("php://input");
    $data = json_decode($postData, true);

    if (!$data) {
        echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
        exit;
    }

    if (empty($data['email']) || empty($data['password'])) {
        echo json_encode(["status" => "error", "message" => "Email and password are required"]);
        exit;
    }

    $email = trim($data['email']);
    $password = trim($data['password']);

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $email;

        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "redirect_url" => "https://qur.kz/k/user_profile.php" // Simply redirect to user_profile.php
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
