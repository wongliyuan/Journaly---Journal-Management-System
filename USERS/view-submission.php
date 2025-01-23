<?php
session_start();

include '../config.php';

// Get the submission ID from the query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if the submission ID is passed via the query string
if (isset($_GET['id'])) {
    $submission_id = (int)$_GET['id'];

    // Fetch the submission details from the database
    $query = "SELECT * FROM submissions WHERE id = :submission_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':submission_id', $submission_id, PDO::PARAM_INT);
    $stmt->execute();

    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        // If the submission is not found, show an error message
        echo "Submission not found.";
        exit;
    }

    // Proceed to display the submission details
} else {
    echo "No submission ID provided.";
    exit;
}

// Fetch the comments along with the username of the user who posted the comment
$query = "SELECT comments.comment_text, comments.created_at, users.username
          FROM comments
          JOIN users ON comments.user_id = users.id
          WHERE comments.submission_id = :id
          ORDER BY comments.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT); // Use the submission ID here
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch assigned reviewers
$query = "SELECT review_assignments.reviewer_id, users.username, review_assignments.assigned_at, review_assignments.status AS reviewer_status
          FROM review_assignments
          JOIN users ON review_assignments.reviewer_id = users.id
          WHERE review_assignments.submission_id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$assignedReviewers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - View Submission</title>
    <link rel="stylesheet" href="Ustyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php';?>

<main class="main-content">
    <!-- Buttons for Edit and Delete -->
    <?php if (!in_array($submission['status'], ['Accepted', 'Rejected', 'Published'])): ?>
        <div class="action-buttons">
            <a href="edit-submission.php?id=<?= $submission['id'] ?>" class="edit-button">
                <i class="bx bx-edit"></i> Edit
            </a>
            <button class="delete-button" onclick="confirmDelete(<?= $submission['id'] ?>)">
                <i class="bx bx-trash"></i> Delete
            </button>
        </div>
    <?php endif; ?>

    <article class="blog-post">
        <header class="blog-header">
            <h1><?= htmlspecialchars($submission['title']) ?></h1>
            <p class="blog-meta">
                <strong>Category:</strong> <?= htmlspecialchars($submission['category']) ?> | 
                <strong>Submission Date:</strong> <?= htmlspecialchars($submission['submission_date']) ?>
            </p>
        </header>
        <section class="blog-content">
            <h2>Abstract</h2>
            <p><?= nl2br(htmlspecialchars($submission['abstract'])) ?></p>
            
            <h2>Files</h2>
            <p><strong>Manuscript File:</strong> 
                <?php if (!empty($submission['manuscript_file'])): ?>
                    <a href="<?= htmlspecialchars($submission['manuscript_file']) ?>" target="_blank">Click to View Manuscript</a>
                <?php else: ?>
                    No file available
                <?php endif; ?>
            </p>
            <p><strong>Supplementary Materials:</strong> 
                <?php if (!empty($submission['supplementary_materials'])): ?>
                    <a href="<?= htmlspecialchars($submission['supplementary_materials']) ?>" target="_blank">Click to View Supplementary Materials</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </p>

            <h2>Author(s) Information</h2>
            <p><strong>Full Name: </strong> <?= htmlspecialchars($submission['lead_author_name']) ?></p>
            <p><strong>Email Address: </strong> <?= htmlspecialchars($submission['lead_author_email']) ?></p>
            <p><strong>Institution: </strong> <?= htmlspecialchars($submission['lead_author_affiliation']) ?></p>
            <p><strong>Co-Authors:</strong></p>
            <p><?= !empty($submission['co_authors']) ? nl2br(htmlspecialchars($submission['co_authors'])) : '-' ?></p>

            <h2>About this Submission</h2>
            <p><strong>Last Updated: </strong> <?= !empty($submission['updated_at']) ? htmlspecialchars($submission['updated_at']): '-' ?></p>
            <p><strong>Status: </strong> <?= htmlspecialchars($submission['status']) ?></p>
        </section>
    </article>

    <?php if (!in_array($submission['status'], ['Accepted', 'Rejected', 'Published'])): ?>
    <section class="assigned-reviewers">
        <h3>Assigned Reviewers</h3>
        <?php if ($assignedReviewers): ?>
            <ul class="reviewer-list">
                <?php foreach ($assignedReviewers as $reviewer): ?>
                    <li>
                        <strong><?= htmlspecialchars($reviewer['username']) ?></strong>
                        - Status: <?= htmlspecialchars($reviewer['reviewer_status']) ?>
                        - Assigned At: <?= htmlspecialchars(date('Y-m-d', strtotime($reviewer['assigned_at']))) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No reviewers assigned yet.</p>
        <?php endif; ?>
    </section>

    <!-- Comments Section -->
    <section class="comments-section" id="comments-section">
        <h3>Previous Comments</h3>

        <?php if ($comments): ?>
            <ul class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <li class="comment">
                        <p class="comment-username"><strong style="color:#7180AC;font-size:18px;"><?= htmlspecialchars($comment['username']) ?></strong> commented:</p>
                        <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                        <p class="comment-meta"><em>Posted on <?= htmlspecialchars($comment['created_at']) ?></em></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No comments yet.</p>
        <?php endif; ?>

        <!-- Feedback Form -->
        <h3>Leave a Comment</h3>
        <form action="add-feedback.php" method="POST" class="feedback-form">
            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
            <textarea name="feedback" id="feedback" rows="5" placeholder="Write your comments here..."></textarea>
            <button type="submit">Submit</button>
        </form>
    </section>
    <?php endif; ?>

</main>

<script>
    function confirmDelete(submissionId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('delete-submission.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${submissionId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                        }).then(() => {
                            window.location.href = "../USERS/my-submission.php";
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete the submission.',
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while deleting the submission.',
                    });
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Select2 on the reviewer's select element
        $('#reviewers').select2({
            placeholder: "Choose reviewers...",
            allowClear: true
        });
    });
</script>

</body>
</html>
