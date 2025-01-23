<?php
// Assuming user_id is stored in the session after the user logs in
session_start();
$user_id = $_SESSION['user_id'];

// Database connection
$host = 'localhost';
$dbname = 'journaly';
$username = 'root';
$password = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

// Fetch users who have chatted with the logged-in user along with the latest message timestamp
$query = "
    SELECT DISTINCT
        CASE
            WHEN sender_id = :user_id THEN receiver_id
            ELSE sender_id
        END AS chat_user_id,
        MAX(c.timestamp) AS last_message_timestamp
    FROM chats c
    WHERE c.sender_id = :user_id OR c.receiver_id = :user_id
    GROUP BY chat_user_id
    ORDER BY last_message_timestamp DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $user_id]);

// Fetch chat user IDs and their last message timestamp
$chat_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Now fetch the usernames, profile_picture, last message timestamp, and unread status
$user_info = [];
foreach ($chat_users as $chat_user) {
    $chat_user_id = $chat_user['chat_user_id'];
    $last_message_timestamp = $chat_user['last_message_timestamp'];

    // Fetch the username and profile_picture of the chat user
    $user_query = "SELECT username, profile_picture FROM users WHERE id = :user_id";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute(['user_id' => $chat_user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check if there are unread messages from this user
        $unread_query = "
            SELECT COUNT(*) AS unread_count
            FROM chats
            WHERE sender_id = :chat_user_id AND receiver_id = :user_id AND is_read = 0
        ";
        $unread_stmt = $pdo->prepare($unread_query);
        $unread_stmt->execute([
            'chat_user_id' => $chat_user_id,
            'user_id' => $user_id
        ]);
        $unread_count = $unread_stmt->fetchColumn();

        // Resolve the profile picture path
        $profile_picture_path = $user['profile_picture'] 
            ? "../uploads/" . $user['profile_picture'] 
            : "../uploads/default.png"; // Default image if no profile_picture

        // Store the username, last message timestamp, profile_picture, and unread status
        $user_info[] = [
            'username' => $user['username'],
            'profile_picture' => $profile_picture_path,
            'last_message_timestamp' => $last_message_timestamp,
            'has_unread' => $unread_count > 0 // true if there are unread messages
        ];
    }
}

// Convert the user information array to JSON
echo json_encode($user_info);
?>
