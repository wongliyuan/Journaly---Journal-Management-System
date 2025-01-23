<?php
include '../config.php';

$limit = 5; // Limit of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total submissions count
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM submissions WHERE editor_id IS NULL AND status ='Pending'");
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($total / $limit);

// Fetch submissions for the current page
$stmt = $pdo->prepare("SELECT id, title, category, manuscript_file, DATE_FORMAT(submission_date, '%Y-%m-%d') AS submission_date 
FROM submissions 
WHERE editor_id IS NULL 
AND status = 'Pending'
ORDER BY submission_date ASC
LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construct the full URL for manuscript file if it's stored in a directory, e.g., "uploads/"
foreach ($submissions as &$submission) {
    // Assuming manuscript files are stored in the "uploads" directory
    $submission['manuscript_url'] = '../uploads/' . $submission['manuscript_file'];
}

echo json_encode([
    'submissions' => $submissions,
    'totalPages' => $totalPages,
]);
?>
