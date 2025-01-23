<?php
include '../config.php';
header('Content-Type: application/json');

// Check if the necessary POST parameters are present
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
    exit;
}

$id = (int)$_POST['id'];
$status = $_POST['status'];

try {
    $pdo->beginTransaction(); // Start a transaction

    // Update the status in the submissions table
    $query = "UPDATE submissions SET status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($status === 'Accepted') {
        // Update completed_at for review_assignments
        $query = "UPDATE review_assignments SET completed_at = NOW() WHERE submission_id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Update completed_at for editor_assignments
        $query = "UPDATE editor_assignments SET completed_at = NOW() WHERE submission_id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Update end_date for submissions
        $query = "UPDATE submissions SET end_date = NOW() WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    $pdo->commit(); // Commit the transaction

    echo json_encode(['success' => true, 'message' => 'Status and related fields updated successfully.']);
} catch (PDOException $e) {
    $pdo->rollBack(); // Rollback the transaction in case of an error
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
