<?php
include '../config.php';

// Get the edition_id from the query parameter
$editionId = isset($_GET['edition_id']) ? (int)$_GET['edition_id'] : 0;

// Fetch submissions related to the edition
$sql = "SELECT s.* FROM submissions s
        INNER JOIN edition_submissions es ON s.id = es.submission_id
        WHERE es.edition_id = :edition_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':edition_id' => $editionId]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch edition details
$sqlEdition = "SELECT * FROM editions WHERE id = :edition_id";
$stmtEdition = $pdo->prepare($sqlEdition);
$stmtEdition->execute([':edition_id' => $editionId]);
$edition = $stmtEdition->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Submissions - <?= htmlspecialchars($edition['title']) ?></title>
    <link rel="stylesheet" href="Gstyle.css" />
</head>
<body>

<?php include 'navbar.php'; ?>

    <main>
        <section style="margin: 50px;" class="submissions-list">
            <h2 style="color:#c8592d;">Journal Articles In <?= htmlspecialchars($edition['title']) ?></h2>
            <div class="submission-grid">
                <?php if (count($submissions) > 0): ?>
                    <?php foreach ($submissions as $submission): ?>
                        <a href="../uploads/<?= htmlspecialchars($submission['manuscript_file']) ?>" class="submission-card" target="_blank">
                            <h3 class="submission-title"><?= htmlspecialchars($submission['title']) ?></h3>
                            <p><strong>Author:</strong> <?= htmlspecialchars($submission['lead_author_name']) ?> </p>
                            <p><strong>Institution:</strong> <?= htmlspecialchars($submission['lead_author_affiliation']) ?></p>
                            <p><strong>Keywords:</strong> <?= htmlspecialchars($submission['keywords']) ?></p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No submissions found for this edition.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
