<?php
session_start();
$user_id = $_SESSION['user_id']; 
$chat_user = $_GET['user']; 

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
    exit;
}

// Fetch messages exchanged with the selected user
$query = "
    SELECT sender_id, message, timestamp 
    FROM chats
    WHERE (sender_id = :user_id AND receiver_id = (SELECT id FROM users WHERE username = :chat_user))
       OR (receiver_id = :user_id AND sender_id = (SELECT id FROM users WHERE username = :chat_user))
    ORDER BY timestamp
";

$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $user_id, 'chat_user' => $chat_user]);

$messages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = [
        'type' => ($row['sender_id'] == $user_id) ? 'sent' : 'received',
        'text' => $row['message'],
        'time' => date('H:i', strtotime($row['timestamp']))
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($messages);
?>
