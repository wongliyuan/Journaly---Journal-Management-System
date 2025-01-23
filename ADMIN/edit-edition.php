<?php
include '../config.php';

// Fetch edition details
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid edition ID.");
}

$editionId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM editions WHERE id = ?");
    $stmt->execute([$editionId]);
    $edition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$edition) {
        die("Edition not found.");
    }

    // Fetch accepted submissions
    $acceptedStmt = $pdo->prepare("SELECT id, title FROM submissions WHERE status = 'Accepted' OR id IN (SELECT submission_id FROM edition_submissions WHERE edition_id = ?)");
    $acceptedStmt->execute([$editionId]);
    $acceptedSubmissions = $acceptedStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch selected submissions for this edition
    $selectedStmt = $pdo->prepare("SELECT submission_id FROM edition_submissions WHERE edition_id = ?");
    $selectedStmt->execute([$editionId]);
    $selectedSubmissions = $selectedStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Error fetching edition details: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $publicationDate = $_POST['publication_date'];
    $coverImage = $_FILES['cover_image'];
    $submissions = $_POST['submissions'] ?? [];

    try {
        // Update edition details
        $updateStmt = $pdo->prepare("UPDATE editions SET title = ?, publication_date = ? WHERE id = ?");
        $updateStmt->execute([$title, $publicationDate, $editionId]);

        // Handle cover image upload
        if (!empty($coverImage['name'])) {
            $targetDir = ''; // You may want to specify a directory
            $fileName = basename($coverImage['name']);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($coverImage['tmp_name'], $targetFilePath)) {
                $pdo->prepare("UPDATE editions SET cover_image = ? WHERE id = ?")
                    ->execute([$fileName, $editionId]);
            }
        }

        // Update submissions
        $pdo->prepare("DELETE FROM edition_submissions WHERE edition_id = ?")->execute([$editionId]);
        $insertStmt = $pdo->prepare("INSERT INTO edition_submissions (edition_id, submission_id) VALUES (?, ?)");

        foreach ($submissions as $submissionId) {
            $insertStmt->execute([$editionId, $submissionId]);
        }

        // Redirect back with success message
        header("Location: manage-edition.php?success=Edition updated successfully.");
        exit;

    } catch (PDOException $e) {
        die("Error updating edition: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - Edit Edition</title>
    <link rel="stylesheet" href="Astyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
    <?php include 'navbar.php';?>

<main class="main-content">
    <div class="dashboard-container">

    <h2>Edit Edition</h2>

    <form method="POST" enctype="multipart/form-data" id="createEditionForm">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($edition['title']) ?>" required />

        <label for="publication_date">Publication Date:</label>
        <input type="date" id="publication_date" name="publication_date" value="<?= htmlspecialchars($edition['publication_date']) ?>" required />

        <label for="cover_image">Cover Image</label>
        <div class="file-input">
            <input type="file" id="cover_image" name="cover_image" accept="image/*" />
            <label for="cover_image" class="custom-file-label">
                <i class='bx bx-upload'></i>
                <?php echo isset($edition['cover_image']) && !empty($edition['cover_image']) 
                    ? basename($edition['cover_image']) 
                    : 'Click here to upload image'; ?>
            </label>
        </div>

        <label for="submissions">Select Submissions:</label>
        <select id="submissions" name="submissions[]" multiple="multiple" style="width: 100%;">
            <?php foreach ($acceptedSubmissions as $submission): ?>
                <option value="<?= htmlspecialchars($submission['id']) ?>" <?= in_array($submission['id'], $selectedSubmissions) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($submission['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="button-container">
            <button type="submit">Update</button>
            <button type="button" onclick="window.location.href='manage-edition.php';">Cancel</button>
        </div>
    </form>
    </div>
</main>

<script>
    // Function to update the file input label with the name of the selected file
    document.addEventListener('DOMContentLoaded', function () {
        const fileInputs = document.querySelectorAll('.file-input input[type="file"]');

        fileInputs.forEach(input => {
            input.addEventListener('change', function () {
                const fileName = this.files[0] ? this.files[0].name : "No image chosen";
                const label = this.nextElementSibling;
                label.innerHTML = `<i class='bx bx-upload'></i> ${fileName}`; // Update the label text with the file name
            });
        });
    });
    
    $(document).ready(function () {
        $('#submissions').select2({
            placeholder: "Choose submissions...",
            allowClear: true
        });
    });
</script>

</body>
</html>
