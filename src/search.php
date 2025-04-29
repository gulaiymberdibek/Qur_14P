<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../config.php';
$db=new DBS();
$pdo=$db->getConnection();
if (isset($_POST['query'])) {
    $search = trim($_POST['query']);

    if (empty($search)) {
        die("Query is empty");
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT content FROM posts WHERE content REGEXP ?");
    
    if (!$stmt) {
        die("SQL error: " . $conn->error); // Debugging: Show SQL errors
    }

    $stmt->bind_param("s", $search);

    if (!$stmt->execute()) {
        die("Execution error: " . $stmt->error); // Debugging: Show execution errors
    }

    $result = $stmt->get_result();

    if (!$result) {
        die("Result error: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='search-item'>" . htmlspecialchars($row['content']) . "</div>";
        }
    } else {
        echo "<div class='search-item'>No results found</div>";
    }
}
?>