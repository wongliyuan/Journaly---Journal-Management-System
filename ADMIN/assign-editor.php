<?php
include '../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$submission_id = $data['submission_id'];
$editor_id = $data['editor_id'];

try {
    // Begin a transaction to ensure both queries are executed together
    $pdo->beginTransaction();

    // Update the submissions table with the selected editor
    $query = "UPDATE submissions SET editor_id = :editor_id WHERE id = :submission_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['editor_id' => $editor_id, 'submission_id' => $submission_id]);

    // Insert a new record into the editor_assignments table
    $query = "INSERT INTO editor_assignments (editor_id, submission_id, assignment_status, assigned_at) 
              VALUES (:editor_id, :submission_id, 'Pending', NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['editor_id' => $editor_id, 'submission_id' => $submission_id]);

    // Commit the transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Editor assigned successfully and assignment recorded.']);
} catch (Exception $e) {
    // Roll back the transaction if something goes wrong
    $pdo->rollBack();

    echo json_encode(['success' => false, 'message' => 'An error occurred while assigning the editor.', 'error' => $e->getMessage()]);
}
?>

