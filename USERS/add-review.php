<?php
// Include database configuration
include '../config.php';

// Start the session to access user data
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to leave feedback.");
}

// Get the submission ID and the feedback from the form
$submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
$comment_text = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';

// Validate the comment text (basic validation)
if (empty($comment_text)) {
    die("Feedback cannot be empty.");
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Prepare and execute the query to insert the feedback into the database
$query = "INSERT INTO comments (submission_id, user_id, comment_text, created_at, updated_at) 
          VALUES (:submission_id, :user_id, :comment_text, NOW(), NOW())";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':submission_id', $submission_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':comment_text', $comment_text, PDO::PARAM_STR);

// Execute the query
if ($stmt->execute()) {
    // Redirect to the submission page after successful feedback submission
    header("Location: create-review.php?id=" . $submission_id);
    exit;
} else {
    die("Error adding feedback. Please try again later.");
}
?>
