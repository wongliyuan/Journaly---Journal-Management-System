<?php
session_start();
include '../config.php'; // Include your database connection

// Get the data from the request
$data = json_decode(file_get_contents('php://input'), true);

// If it's a request to mark all as read
if (isset($data['mark_all']) && $data['mark_all'] === true) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['status' => 'success']);
    exit;
}

// If it's a request to mark a specific notification as read
if (isset($data['notification_id'])) {
    $notificationId = $data['notification_id'];

    // Update the notification to marked as read
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$notificationId]);

    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>
