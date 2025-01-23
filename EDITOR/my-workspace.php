<?php

include '../config.php';

// Start session to get the logged-in user's ID
session_start();
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this page.");
}

$user_id = $_SESSION['user_id']; // Logged-in editor's ID

// Define number of rows per page
$rowsPerPage = 5;

// Get current page number from query string (default is 1 if not set)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startFrom = ($page - 1) * $rowsPerPage;

// Get selected status from query string (optional)
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Prepare the base query for fetching submissions
$query = "SELECT id, title, category, lead_author_name, submission_date, status 
          FROM submissions
          WHERE editor_id = :editor_id";

// If a status is selected, filter by it
if ($selectedStatus) {
    // Filter by 'Pending' status if selected
    if ($selectedStatus === 'Pending') {
        $query .= " AND status = 'Pending'";
    } else {
        // Filter by other status
        $query .= " AND status = :status";
    }
}

// Add pagination
$query .= " LIMIT :startFrom, :rowsPerPage";

try {
    // Prepare the query
    $stmt = $pdo->prepare($query);

    // Bind parameters for editor ID and exact status (if applicable)
    $stmt->bindParam(':editor_id', $user_id, PDO::PARAM_INT);
    if ($selectedStatus && $selectedStatus !== 'Pending') {
        $stmt->bindParam(':status', $selectedStatus, PDO::PARAM_STR);
    }
    $stmt->bindParam(':startFrom', $startFrom, PDO::PARAM_INT);
    $stmt->bindParam(':rowsPerPage', $rowsPerPage, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch all results as an associative array
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving submissions: " . $e->getMessage());
}

// Fetch total number of submissions for stats (without pagination)
$totalQuery = "SELECT COUNT(*) AS total FROM submissions WHERE editor_id = :editor_id";
if ($selectedStatus) {
    $totalQuery .= " AND status = :status";
}
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->bindParam(':editor_id', $user_id, PDO::PARAM_INT);
if ($selectedStatus) {
    $totalStmt->bindParam(':status', $selectedStatus, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Count total submissions by status for the dashboard
$statQuery = "SELECT status, COUNT(*) AS count 
              FROM submissions 
              WHERE editor_id = :editor_id 
              GROUP BY status";
$statStmt = $pdo->prepare($statQuery);
$statStmt->bindParam(':editor_id', $user_id, PDO::PARAM_INT);
$statStmt->execute();
$statusCounts = $statStmt->fetchAll(PDO::FETCH_ASSOC);

$pendingCount = $underReviewCount = $acceptedCount = $revisionRequiredCount = 0;

foreach ($statusCounts as $statusCount) {
    switch ($statusCount['status']) {
        case 'Submitted':
        case 'Pending':
            $pendingCount += $statusCount['count'];
            break;
        case 'Under Review':
            $underReviewCount = $statusCount['count'];
            break;
        case 'Accepted':
            $acceptedCount = $statusCount['count'];
            break;
        case 'Revision Required':
            $revisionRequiredCount = $statusCount['count'];
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - My Workspace</title>
    <link rel="stylesheet" href="Estyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">

    <div class="dashboard-container">
    <div class="header-container">
        <h2>My Workspace</h2>
        <!-- <button class="manage-edition-btn" onclick="window.location.href='manage-edition.php'">
            <i class='bx bx-folder-plus' style="margin-right: 5px; font-size: 20px;"></i>Manage Edition
        </button> -->
    </div>
    <!-- Stats boxes with links to filter by status -->
    <div class="stats-grid">
        <div class="stat-box red" onclick="filterData('Pending')">
                <div class="stat-header">
                    <h3><?= $pendingCount ?></h3>
                    <i class="bx bxs-error-circle icon"></i>
                </div>
                <p>Pending Approval</p>
            </div>
            <div class="stat-box orange" onclick="filterData('Under Review')">
                <div class="stat-header">
                    <h3><?= $underReviewCount ?></h3>
                    <i class='bx bxs-book-open icon'></i>
                </div>
                <p>Under Review</p>
            </div>
            <div class="stat-box yellow" onclick="filterData('Revision Required')">
                <div class="stat-header">
                    <h3><?= $revisionRequiredCount ?></h3>
                    <i class="bx bx-revision icon"></i>
                </div>
                <p>Revision Required</p>
            </div>
            <div class="stat-box green" onclick="filterData('Accepted')">
                <div class="stat-header">
                    <h3><?= $acceptedCount ?></h3>
                    <i class="bx bxs-check-square icon"></i>
                </div>
                <p>Accepted Approval</p>
            </div>
    </div>

    <p onclick="filterData('')" style="font-size: 15px; color: blue; text-decoration: underline; text-align: right; cursor: pointer;">Clear Filter</p>

    <!-- Table -->
    <table class="table-container">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Category</th>
            <th>Author</th>
            <th>Submission Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
            <tbody>
            <?php if (!empty($submissions)): ?>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?= htmlspecialchars($submission['id']) ?></td>
                        <td><?= htmlspecialchars($submission['title']) ?></td>
                        <td><?= htmlspecialchars($submission['category']) ?></td>
                        <td><?= htmlspecialchars($submission['lead_author_name']) ?></td>
                        <td><?= htmlspecialchars($submission['submission_date']) ?></td>
                        <td><?= htmlspecialchars($submission['status']) ?></td>
                        <td>
                            <div class="action-icons">
                                <i class="bx bx-show view" onclick="viewSubmission(<?= $submission['id'] ?>)" title="View Submission"></i>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No submissions found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?status=<?= $selectedStatus ?>&page=<?= $page - 1 ?>" class="prev">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?status=<?= $selectedStatus ?>&page=<?= $i ?>" class="page-number <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?status=<?= $selectedStatus ?>&page=<?= $page + 1 ?>" class="next">Next</a>
            <?php endif; ?>
        </div>
    </div>

</main>

<script>
    // Function to filter data based on status
    function filterData(status) {
    let url = window.location.href.split('?')[0]; // Remove any query parameters
    if (status) {
        url += `?status=${status}`; // Append status to the URL
    }
    window.location.href = url; // Redirect to the new URL
    }

    function viewSubmission(id) {
        // Replace with your actual URL for viewing the submission details
        window.location.href = `view-submission.php?id=${id}`;
    }
</script>

</body>
</html>