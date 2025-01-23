<?php
session_start();
include '../config.php';

header('Content-Type: application/json'); // Set response type to JSON for AJAX requests

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the 'id' parameter is set in the request
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $userId = (int) $_POST['id']; // Sanitize and validate input

        try {
            // Check if the user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                // User not found
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                exit;
            }

            // Prepare the delete query
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Return success response
            echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);
        } catch (PDOException $e) {
            // Log the error and return a failure response
            error_log("Error deleting user: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
        }
    } else {
        // Invalid ID
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    }
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
?>