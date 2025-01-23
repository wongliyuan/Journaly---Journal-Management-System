<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Extract data from POST
        $title = $_POST['title'];
        $publicationDate = $_POST['publication_date'];
        $coverImage = $_FILES['cover_image'];
        $submissionIds = $_POST['submissions']; // Array of selected submission IDs

        // Assuming user_id is stored in session after login
        session_start(); // Start the session to access session variables
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User is not logged in.");
        }
        $userId = $_SESSION['user_id']; // Get the user_id from session

        // Handle file upload for cover image
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/'; // Directory to save the uploaded file
            $uploadFile = $uploadDir . basename($_FILES['cover_image']['name']);
            
            // Move the uploaded file to the desired location
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadFile)) {
                $coverImagePath = $uploadFile; // Use this path for database insertion
            } else {
                throw new Exception("Failed to upload file.");
            }
        } else {
            $coverImagePath = null; // Handle missing file gracefully
        }

        // Insert new edition
        $pdo->beginTransaction();

        $insertEditionStmt = $pdo->prepare("
            INSERT INTO editions (title, publication_date, cover_image, status, user_id) 
            VALUES (:title, :publication_date, :cover_image, 'Published', :user_id)
        ");
        $insertEditionStmt->execute([
            ':title' => $title,
            ':publication_date' => $publicationDate,
            ':cover_image' => $coverImagePath,
            ':user_id' => $userId, // Include user_id in the insert statement
        ]);

        $editionId = $pdo->lastInsertId(); // Get the ID of the newly inserted edition

        // Link submissions to the new edition and update their status
        $updateSubmissionsStmt = $pdo->prepare("
            UPDATE submissions 
            SET edition_id = :edition_id, status = 'Published' 
            WHERE id = :submission_id
        ");
        
        $insertEditionSubmissionsStmt = $pdo->prepare("
            INSERT INTO edition_submissions (edition_id, submission_id)
            VALUES (:edition_id, :submission_id)
        ");

        foreach ($submissionIds as $submissionId) {
            // Update submission status to Published
            $updateSubmissionsStmt->execute([
                ':edition_id' => $editionId,
                ':submission_id' => $submissionId,
            ]);
            
            // Insert the relation into the edition_submissions table
            $insertEditionSubmissionsStmt->execute([
                ':edition_id' => $editionId,
                ':submission_id' => $submissionId,
            ]);
        }

        $pdo->commit(); // Commit the transaction

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback in case of error
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
