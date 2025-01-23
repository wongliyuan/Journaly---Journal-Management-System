<?php
session_start();
include '../config.php';

// Check if the request method is POST for better security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $submissionId = (int) $_POST['id'];

        try {
            // Check if the submission exists
            $stmt = $pdo->prepare("SELECT id FROM submissions WHERE id = :id");
            $stmt->bindParam(':id', $submissionId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'Submission not found.']);
                exit;
            }

            // Delete related data (e.g., comments, review assignments) if needed
            $pdo->beginTransaction();

            // Delete comments
            $stmt = $pdo->prepare("DELETE FROM comments WHERE submission_id = :id");
            $stmt->bindParam(':id', $submissionId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete review assignments
            $stmt = $pdo->prepare("DELETE FROM review_assignments WHERE submission_id = :id");
            $stmt->bindParam(':id', $submissionId, PDO::PARAM_INT);
            $stmt->execute();

            // Finally, delete the submission
            $stmt = $pdo->prepare("DELETE FROM submissions WHERE id = :id");
            $stmt->bindParam(':id', $submissionId, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();

            // Return success message
            echo json_encode(['success' => true, 'message' => 'Submission deleted successfully.']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error deleting submission: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error deleting submission.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid submission ID.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
?>