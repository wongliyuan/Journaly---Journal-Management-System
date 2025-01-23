<?php
session_start();
$userId = $_SESSION['user_id']; // Ensure the user_id is stored in the session

// Include the database connection
include '../config.php'; 

// Fetch notifications for the user from the database
// $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY timestamp DESC");
// $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
// $stmt->execute();

// $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch notifications along with the submission ID
$stmt = $pdo->prepare("
  SELECT n.id, n.message, n.is_read, n.timestamp, n.submission_id
  FROM notifications n
  WHERE n.user_id = ?
  ORDER BY n.timestamp DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the data as JSON
echo json_encode($notifications);

?>
