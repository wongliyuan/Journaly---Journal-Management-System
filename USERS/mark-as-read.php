<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo "Error: User not logged in.";
    exit;
}

if (!isset($_GET['user'])) {
    echo "Error: 'user' parameter is missing in the GET request.";
    exit;
}

$current_user_id = $_SESSION['user_id'];
$other_user_username = $_GET['user'];

// Fetch the user_id for the other user (username)
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = :other_user_username");
$stmt->execute(['other_user_username' => $other_user_username]);
$other_user = $stmt->fetchColumn();

if (!$other_user) {
    echo "Error: User not found.";
    exit;
}

try {
    // Update the chat messages to mark as read
    $stmt = $pdo->prepare("UPDATE chats SET is_read = 1 WHERE sender_id = :other_user AND receiver_id = :current_user_id");
    $result = $stmt->execute([
        'other_user' => $other_user, // Use the user_id of the other user
        'current_user_id' => $current_user_id
    ]);

    if ($result) {
        echo "Messages from user $other_user to user $current_user_id marked as read.";
    } else {
        echo "Failed to mark messages as read.";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
