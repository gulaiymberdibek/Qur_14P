<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();


require '../config.php';
$db=new DBS();
$pdo=$db->getConnection();

if (!isset($_POST['email']) || !isset($_POST['verificationCode'])) {
    echo json_encode(["status" => "error", "message" => "Email and verification code are required"]);
    exit;
}

$email = $_POST['email'];
$enteredCode = trim($_POST['verificationCode']); // Trim to remove spaces

// Fetch the correct verification code from the database
$stmt = $pdo->prepare("SELECT verification_code FROM verification_codes WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $storedCode = trim($row['verification_code']); // Trim stored code as well

    if ($storedCode === $enteredCode) {
        echo json_encode(["status" => "success", "message" => "Verification successful"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Incorrect verification code"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No verification code found for this email"]);
}
?>
