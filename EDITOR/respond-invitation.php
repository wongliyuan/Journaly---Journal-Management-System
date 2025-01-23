<?php
session_start();
include '../config.php'; // Include the database configuration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = $_POST['submission_id'];
    $editor_id = $_POST['editor_id'];
    $response = strtolower($_POST['response']); // Convert to lowercase to avoid case sensitivity

    if (!isset($response) || !in_array($response, ['accept', 'reject'])) {
        // Redirect to an error page if the response is invalid
        header('Location: error-page.php?error=Invalid+response+provided');
        exit;
    }
    
    try {
        if ($response === 'accept') {
            // Update the assignment status to 'Accepted'
            $query = "UPDATE editor_assignments 
                      SET assignment_status = 'Accepted', updated_at = NOW() 
                      WHERE editor_id = :editor_id AND submission_id = :submission_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'editor_id' => $editor_id,
                'submission_id' => $submission_id
            ]);

            // Redirect to the submissions page
            $_SESSION['message'] = "You accepted to be the Editor of this submission.";
            header("Location: view-submission.php?id=" . urldecode($submission_id));
            exit;
        } elseif ($response === 'reject') {
            // Update the assignment status to 'Rejected'
            $reject_reason = $_POST['reject_reason'] ?? null;
            $custom_reason = $_POST['custom_reject_reason'] ?? '';

            // Combine rejection reasons if 'Other' is selected
            if ($reject_reason === 'Other' && !empty($custom_reason)) {
                $reject_reason .= ": " . $custom_reason;
            }

            $pdo->beginTransaction();

            // Update the assignment status and updated_at in editor_assignments
            $query = "UPDATE editor_assignments 
                      SET assignment_status = 'Rejected', 
                          updated_at = NOW(), 
                          reject_reason = :reject_reason 
                      WHERE editor_id = :editor_id AND submission_id = :submission_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'editor_id' => $editor_id,
                'submission_id' => $submission_id,
                'reject_reason' => $reject_reason
            ]);

            // Set editor_id to NULL in submissions table
            $query = "UPDATE submissions 
                      SET editor_id = NULL 
                      WHERE id = :submission_id AND editor_id = :editor_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'submission_id' => $submission_id,
                'editor_id' => $editor_id
            ]);

            $pdo->commit();

            // Redirect to the submissions page
            $_SESSION['message'] = "You rejected to be the Editor of this submission.";
            header("Location: view-submission.php?id=". urldecode($submission_id));
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack(); // Roll back the transaction in case of an error
        // Redirect to an error page with the error message
        header('Location: error-page.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Redirect to an error page if the request method is invalid
    header('Location: error-page.php?error=Invalid+request+method');
    exit;
}
?>