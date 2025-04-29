
<?php
session_start();

// Validate email
if (!isset($_POST['email']) || empty($_POST['email'])) {
    echo json_encode(["status" => "error", "message" => "Email is required"]);
    exit;
}

$email = $_POST['email'];
$verificationCode = rand(100000, 999999);  // Generate a random 6-digit verification code

require '../config.php';
$db=new DBS();
$pdo=$db->getConnection();

$stmt = $pdo->prepare("INSERT INTO verification_codes (email, verification_code) VALUES (:email, :verification_code)");
$stmt->bindParam(':email', $email);
$stmt->bindParam(':verification_code', $verificationCode);

if ($stmt->execute()) {
    // Send the verification code to the user's email
    $to = $email;
    $subject = "Your Verification Code";
    $message = "Your verification code is: " . $verificationCode;
    $headers = "From: help@qur.kz\r\n";
    $headers .= "Reply-To: help@qur.kz\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (mail($to, $subject, $message, $headers)) {
        $_SESSION['email'] = $email;
        $_SESSION['verificationCode'] = $verificationCode;
        echo json_encode(["status" => "success", "message" => "Verification code sent", "verificationCode" => $verificationCode]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send verification code"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Failed to store verification code"]);
}
?>
