<?php
include '../config.php';

// Get the submission_id from the query parameter
$submissionId = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;

// Fetch submission details
$sql = "SELECT * FROM submissions WHERE id = :submission_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':submission_id' => $submissionId]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    die("Submission not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Submission Details - <?= htmlspecialchars($submission['title']) ?></title>
    <link rel="stylesheet" href="Gstyle.css" />
</head>
<body>

<?php include 'navbar.php'; ?>

    <main>
        <section class="submission-detail">
            <h2><?= htmlspecialchars($submission['title']) ?></h2>
            <p><strong>Submitted on:</strong> <?= date('F d, Y', strtotime($submission['created_at'])) ?></p>
            <div class="submission-content">
                <p><?= nl2br(htmlspecialchars($submission['content'])) ?></p>
            </div>
        </section>
    </main>
</body>
</html>
