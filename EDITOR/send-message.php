<?php
session_start();
$user_id = $_SESSION['user_id'];
$message_text = $_POST['message'];
$receiver_user = $_POST['receiver_user']; 

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

// Get the receiver's ID based on their username
$query = "SELECT id FROM users WHERE username = :receiver_user";
$stmt = $pdo->prepare($query);
$stmt->execute(['receiver_user' => $receiver_user]);
$receiver = $stmt->fetch(PDO::FETCH_ASSOC);

if ($receiver) {
    // Insert the message into the chats table
    $insert_query = "INSERT INTO chats (sender_id, receiver_id, message, timestamp) 
                     VALUES (:sender_id, :receiver_id, :message, NOW())";
    $insert_stmt = $pdo->prepare($insert_query);
    $insert_stmt->execute([
        'sender_id' => $user_id,
        'receiver_id' => $receiver['id'],
        'message' => $message_text
    ]);

    echo "Message sent successfully!";
} else {
    echo "Receiver not found!";
}
?>
