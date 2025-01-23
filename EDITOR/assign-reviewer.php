<?php
session_start();
include '../config.php';

// Ensure the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submission ID and reviewers from the form
    $submissionId = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
    $reviewers = isset($_POST['reviewers']) ? $_POST['reviewers'] : [];

    if ($submissionId && !empty($reviewers)) {
        // Fetch the submission details to get the lead author and co-authors
        $query = "SELECT lead_author_email, co_authors FROM submissions WHERE id = :submission_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':submission_id', $submissionId, PDO::PARAM_INT);
        $stmt->execute();
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$submission) {
            die("Submission not found.");
        }

        // Get the lead author's email and co-authors
        $leadAuthorEmail = $submission['lead_author_email'];
        $coAuthors = array_map('trim', explode(',', $submission['co_authors']));

        // Fetch all users with 'User' role, excluding lead author and co-authors
        $placeholders = str_repeat('?,', count($coAuthors)) . '?'; // Create placeholders for the co-authors
        $query = "
            SELECT id, username, email, expertise_area 
            FROM users 
            WHERE role = 'User' 
            AND email NOT IN ($placeholders)
        ";

        $params = array_merge([$leadAuthorEmail], $coAuthors); // Combine lead author and co-authors into one array
        $reviewersStmt = $pdo->prepare($query);
        $reviewersStmt->execute($params);
        $validReviewers = $reviewersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Extract valid reviewer IDs
        $validReviewerIds = array_column($validReviewers, 'id');

        // Validate submitted reviewers
        foreach ($reviewers as $reviewerId) {
            if (!in_array($reviewerId, $validReviewerIds)) {
                die("Invalid reviewer assignment.");
            }
        }

        // Ensure editor is logged in
        if (!isset($_SESSION['user_id'])) {
            die("User not logged in.");
        }

        $editorId = $_SESSION['user_id']; // Get the logged-in user's ID

        // Loop through the selected reviewers and insert into the database
        $query = "INSERT INTO review_assignments (submission_id, reviewer_id, editor_id, status) VALUES (:submission_id, :reviewer_id, :editor_id, 'Pending')";
        $stmt = $pdo->prepare($query);

        foreach ($reviewers as $reviewerId) {
            $stmt->bindParam(':submission_id', $submissionId, PDO::PARAM_INT);
            $stmt->bindParam(':reviewer_id', $reviewerId, PDO::PARAM_INT);
            $stmt->bindParam(':editor_id', $editorId, PDO::PARAM_INT); // Bind the editor ID
            $stmt->execute();
        }

        // Redirect back to the submission page after assignment
        header('Location: view-submission.php?id=' . $submissionId);
        exit();
    } else {
        echo "Please select at least one reviewer.";
    }
}
?>
