

<?php
session_start();

include '../config.php';
$db=new DBS();
$pdo=$db->getConnection();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postData = file_get_contents("php://input");
    $data = json_decode($postData, true);

    // Check if JSON is valid
    if (!$data) {
        echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
        exit;
    }

    // Validate required fields
    if (empty($data['name']) || empty($data['password']) || empty($data['verificationCode'])) {
        echo json_encode(["status" => "error", "message" => "Missing fields"]);
        exit;
    }

    // Get session values
    $email = $_SESSION['email'];
    $verificationCodeFromSession = trim((string)$_SESSION['verificationCode']);
    $verificationCodeFromUser = trim((string)$data['verificationCode']);

    // Verify the code entered by the user
    if ($verificationCodeFromSession !== $verificationCodeFromUser) {
        echo json_encode(["status" => "error", "message" => "Incorrect verification code"]);
        exit;
    }

    // Sanitize and hash password
    $name = htmlspecialchars($data['name']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);  // Hash password

    // Save to the database
    $stmt = $pdo->prepare("INSERT INTO users (email, name, password) VALUES (:email, :name, :password)");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':password', $password);

   if ($stmt->execute()) {
    // Store session variables after successful registration
    $_SESSION['user_id'] = $pdo->lastInsertId(); // Get last inserted user ID
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    // Clear verification session
    unset($_SESSION['email']);
    unset($_SESSION['verificationCode']);

    // Send success response with redirect URL
    echo json_encode([
        "status" => "success",
        "redirect_url" => "https://qur.kz/k/user_profile.php"
    ]);
    exit;
}

} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
