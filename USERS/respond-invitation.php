<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewerId = isset($_POST['reviewer_id']) ? (int)$_POST['reviewer_id'] : 0;
    $submissionId = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
    $response = isset($_POST['response']) ? $_POST['response'] : '';
    $rejectionReason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : null;
    $customRejectionReason = isset($_POST['custom_reject_reason']) ? trim($_POST['custom_reject_reason']) : '';

    if ($reviewerId && $submissionId && in_array($response, ['accept', 'reject'])) {
        $status = ($response === 'accept') ? 'Accepted' : 'Declined';

        // If the response is 'reject', determine the rejection reason
        if ($response === 'reject') {
            if ($rejectionReason === 'Other' && !empty($customRejectionReason)) {
                $rejectionReason = $customRejectionReason; // Use the custom reason
            }
            // Ensure the rejection reason is not empty
            if (empty($rejectionReason)) {
                $_SESSION['message'] = "Rejection reason is required.";
                header("Location: create-review.php?id=" . urlencode($submissionId));
                exit;
            }
        } else {
            $rejectionReason = null; // No reason required for 'accept'
        }

        // Update the review assignment in the database
        $query = "UPDATE review_assignments 
                  SET status = :status, 
                      rejection_reason = :rejection_reason, 
                      updated_at = NOW() 
                  WHERE reviewer_id = :reviewer_id AND submission_id = :submission_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':rejection_reason', $rejectionReason, PDO::PARAM_STR);
        $stmt->bindParam(':reviewer_id', $reviewerId, PDO::PARAM_INT);
        $stmt->bindParam(':submission_id', $submissionId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Response recorded successfully.";
            header("Location: create-review.php?id=" . urlencode($submissionId));
            exit;
        } else {
            error_log("Database error: " . implode(", ", $stmt->errorInfo()));
            $_SESSION['message'] = "Failed to record the response. Please try again.";
        }
    } else {
        $_SESSION['message'] = "Invalid input data.";
    }
    header("Location: create-review.php?id=" . urlencode($submissionId));
    exit;
}
?>
