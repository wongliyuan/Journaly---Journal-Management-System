<?php
session_start();
include '../config.php';

// Get the submission ID from the query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch submission details
$query = "SELECT * FROM submissions WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    die("Submission not found.");
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

// Fetch all users with 'User' role, assuming they are reviewers
// $reviewersStmt = $pdo->prepare("
//     SELECT id, username, email, expertise_area FROM users WHERE role = 'User'
// ");
// $reviewersStmt->execute();
// $reviewers = $reviewersStmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch the lead author's email and co-authors
$leadAuthorEmail = $submission['lead_author_email'];
$coAuthors = array_map('trim', explode(',', $submission['co_authors']));

// Fetch all users with the 'User' role but exclude the lead author and co-authors
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
$reviewers = $reviewersStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="Estyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php';?>

<main class="main-content">
    <article class="blog-post">
        <header class="blog-header">
            <h1><?= htmlspecialchars($submission['title']) ?></h1>
            <p class="blog-meta">
                <strong>Category:</strong> <?= htmlspecialchars($submission['category']) ?> | 
                <strong>Submission Date:</strong> <?= htmlspecialchars($submission['submission_date']) ?>
            </p>
        </header>
        <section class="blog-content">
            <p><strong>Keywords: </strong> <?= htmlspecialchars($submission['keywords']) ?></p>
            <p><strong>Manuscript Type: </strong> <?= htmlspecialchars($submission['manuscript_type']) ?></p>
            
            <h2>Abstract</h2>
            <p><?= nl2br(htmlspecialchars($submission['abstract'])) ?></p>
            
            <h2>Files</h2>
            <p><strong>Manuscript File:</strong> 
                <?php if (!empty($submission['manuscript_file'])): ?>
                    <a href="../uploads/<?= htmlspecialchars($submission['manuscript_file']) ?>" target="_blank">Click to View Manuscript</a>
                <?php else: ?>
                    No file available
                <?php endif; ?>
            </p>
            <p><strong>Supplementary Materials:</strong> 
                <?php if (!empty($submission['supplementary_materials'])): ?>
                    <a href="../uploads/<?= htmlspecialchars($submission['supplementary_materials']) ?>" target="_blank">Click to View Supplementary Materials</a>
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

    <?php
// Check the editor's response to the invitation
$query = "SELECT assignment_status, reject_reason FROM editor_assignments 
          WHERE editor_id = :editor_id AND submission_id = :submission_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':editor_id', $_SESSION['user_id'], PDO::PARAM_INT); // Editor ID from session
$stmt->bindParam(':submission_id', $submission['id'], PDO::PARAM_INT); // Submission ID
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$assignmentStatus = $result['assignment_status'] ?? 'Pending';
$rejectReason = $result['reject_reason'] ?? '';
?>

<section class="invitation-response styled-section" id="invitation-response">
    <?php if ($assignmentStatus === 'Pending'): ?>
        <h3>Invitation to Manage Submission</h3>
        <p>You have been invited to manage this submission as an editor.</p>
        <form action="respond-invitation.php" method="POST" class="invitation-form">
            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
            <input type="hidden" name="editor_id" value="<?= $_SESSION['user_id'] ?>"> <!-- Editor ID -->
            <div class="button-group">
                <button type="submit" name="response" value="accept" class="accept-button" style="margin: 20px 0 0; width: 150px;">Accept</button>
                <button type="button" onclick="showRejectReasonForm()" class="reject-button" style="display:block; margin: 20px 0 0; width: 150px;">Reject</button>
            </div>

            <!-- Rejection Reason Section -->
            <div id="reject-reason-form" style="display: none; margin-top: 20px;">
                <h4 style="font-size: 20px;">Reason for Rejection</h4>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="radio" name="reject_reason" value="Too busy to review">
                        Heavy Workload
                    </label>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="radio" name="reject_reason" value="Conflict of interest">
                        Conflict of Interest
                    </label>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="radio" name="reject_reason" value="Topic not within expertise">
                        Topic Not Within Expertise Area
                    </label>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="radio" name="reject_reason" value="Other">
                        Other
                    </label>
                </div>
                <textarea name="custom_reject_reason" placeholder="If 'Other', please provide details here..." style="width: 100%; margin-top: 10px; padding: 5px;"></textarea><br>
                <div class="button-group">
                    <button type="submit" name="response" value="reject" class="reject-button" style="background-color: #F97C7C;">Submit</button>
                </div>
            </div>
        </form>
    <?php elseif ($assignmentStatus === 'Accepted'): ?>
        <p>You have accepted the invitation to manage this submission. Thank you!</p>
    <?php elseif ($assignmentStatus === 'Rejected'): ?>
        <p>You have rejected the invitation to manage this submission due to: <strong><?= htmlspecialchars($rejectReason) ?></strong>.</p>
    <?php else: ?>
        <p>You are not assigned as an editor for this submission.</p>
    <?php endif; ?>
</section>


    <?php if ($assignmentStatus !== 'Accepted'): ?>
        <style>
            #editor-actions, #assign-reviewer, #comments-section {
                display: none;
            }
        </style>
    <?php endif; ?>

    <section class="editor-actions" id="editor-actions">
        <form action="update-status.php" method="POST" class="status-update-form">
            <input type="hidden" name="id" value="<?= $submission['id'] ?>">
            <label for="status" style="font-size:22px;">Update Status</label>
            <select name="status" id="status">
                <option value="Pending" <?= $submission['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Under Review" <?= $submission['status'] === 'Under Review' ? 'selected' : '' ?>>Under Review</option>
                <option value="Revision Required" <?= $submission['status'] === 'Revision Required' ? 'selected' : '' ?>>Revision Required</option>
                <option value="Accepted" <?= $submission['status'] === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="Rejected" <?= $submission['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
            <button type="submit" id="update-status-btn">Update</button>
        </form>
    </section>

        <!-- Assign Reviewer Section -->
    <section class="assign-reviewer" id="assign-reviewer" style="display: <?= in_array($submission['status'], ['Under Review', 'Revision Required']) ? 'block' : 'none' ?>;">
        <h3>Reviewer Details</h3>
        <form action="assign-reviewer.php" method="POST">
            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
            <label for="reviewers">Select Reviewers:</label>
                <select id="reviewers" name="reviewers[]" multiple="multiple" style="width: 100%;">
                    <?php if (!empty($reviewers)): ?>
                        <?php foreach ($reviewers as $reviewer): ?>
                            <option value="<?= htmlspecialchars($reviewer['id']) ?>">
                                <?= htmlspecialchars($reviewer['username']) ?> 
                                (<?= htmlspecialchars($reviewer['email']) ?>, 
                                <?= htmlspecialchars($reviewer['expertise_area']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option disabled>No reviewers available for selection</option>
                    <?php endif; ?>
                </select>
            <button type="submit">Assign</button>
        </form>

        <h4 style="margin-top:15px;">Assigned Reviewers</h4>
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
                        <p class="comment-username"><strong style="color:#FDAAAA;font-size:18px;"><?= htmlspecialchars($comment['username']) ?></strong> commented:</p>
                        <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                        <p class="comment-meta"><em>Posted on <?= htmlspecialchars($comment['created_at']) ?></em></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No comments yet.</p>
        <?php endif; ?>

        <!-- Feedback Form (Now part of the comments section) -->
        <h3>Leave a Comment</h3>
        <form action="add-feedback.php" method="POST" class="feedback-form">
            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
            <textarea name="feedback" id="feedback" rows="5" placeholder="Write your comments here..."></textarea>
            <button type="submit">Submit</button>
        </form>
    </section>

</main>

<script>
    function showRejectReasonForm() {
        document.getElementById('reject-reason-form').style.display = 'block';
    }

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 on the reviewer's select element
    $('#reviewers').select2({
        placeholder: "Choose reviewers...",
        allowClear: true
    });

    // Other DOM manipulations related to status
    var currentStatus = "<?= htmlspecialchars($submission['status']) ?>"; // Get the current status from PHP
    var assignReviewerSection = document.getElementById("assign-reviewer");

    if (currentStatus === "Under Review" || currentStatus === "Revision Required") {
        assignReviewerSection.style.display = "block";
    } else {
        assignReviewerSection.style.display = "none";
    }

    var statusSelect = document.getElementById("status");
    if (currentStatus === "Under Review") {
        statusSelect.querySelector('option[value="Pending"]').disabled = true;
    }
    if (currentStatus === "Revision Required") {
        statusSelect.querySelector('option[value="Pending"]').disabled = true;
        statusSelect.querySelector('option[value="Under Review"]').disabled = true;
    }
    if (currentStatus === "Accepted") {
        statusSelect.querySelector('option[value="Pending"]').disabled = true;
        statusSelect.querySelector('option[value="Under Review"]').disabled = true;
        statusSelect.querySelector('option[value="Revision Required"]').disabled = true;
    }
    if (currentStatus === "Rejected") {
        statusSelect.querySelector('option[value="Pending"]').disabled = true;
        statusSelect.querySelector('option[value="Under Review"]').disabled = true;
        statusSelect.querySelector('option[value="Revision Required"]').disabled = true;
        statusSelect.querySelector('option[value="Accepted"]').disabled = true;
    }

    // Hide comment and status sections for "Accepted" or "Published" status
    var commentsSection = document.getElementById("comments-section");
    var editorActions = document.getElementById("editor-actions");
    if (currentStatus === "Accepted" || currentStatus === "Published") {
        commentsSection.style.display = "none";
        editorActions.style.display = "none";
    }
});

document.querySelector('.status-update-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('update-status.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    timer: 3000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message,
                    icon: 'error',
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error!',
                text: 'An unexpected error occurred.',
                icon: 'error',
            });
        });
});

// document.querySelector('.invitation-form').addEventListener('submit', function (e) {
//     e.preventDefault();
//     const formData = new FormData(this);

//     fetch('respond-invitation.php', {
//         method: 'POST',
//         body: formData,
//     })
//         .then(response => response.json())
//         .then(data => {
//             Swal.fire({
//                 title: data.success ? 'Success!' : 'Error!',
//                 text: data.message,
//                 icon: data.success ? 'success' : 'error',
//             }).then(() => {
//                 if (data.success) {
//                     location.reload();
//                 }
//             });
//         })
//         .catch(() => {
//             Swal.fire('Error!', 'An unexpected error occurred.', 'error');
//         });
// });

</script>

</body>
</html>
