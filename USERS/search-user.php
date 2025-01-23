<?php
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
    exit;
}

// Fetch the search query from the request
$query = $_GET['query'] ?? '';

// Query to search for users by username or email
$search_query = "
    SELECT id, username, email, profile_picture
    FROM users
    WHERE (username LIKE :query OR email LIKE :query)
      AND id != :user_id
    LIMIT 10
";

$stmt = $pdo->prepare($search_query);
$stmt->execute([
    'query' => '%' . $query . '%',
    'user_id' => $user_id,
]);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process profile_picture paths
foreach ($users as &$user) {
    $user['profile_picture'] = $user['profile_picture'] 
        ? "../uploads/" . $user['profile_picture'] 
        : "../uploads/default.png"; // Default image if no profile_picture
}

// Return the search results as JSON
echo json_encode($users);
?>
