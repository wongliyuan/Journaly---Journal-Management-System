<?php
session_start();
$userId = $_SESSION['user_id']; // Ensure the user_id is stored in the session

// Include the database connection
include '../config.php'; 

// Fetch notifications along with the submission ID(Wanted)
$stmt = $pdo->prepare("
  SELECT n.id, n.message, n.is_read, n.timestamp, n.submission_id, n.new_user
  FROM notifications n
  WHERE n.user_id = ?
  ORDER BY n.timestamp DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the data as JSON
echo json_encode($notifications);
?>
