<?php
session_start();

// Include the config file for database connection
include '../config.php';

// Get the logged-in user ID from session
$user_id = $_SESSION['user_id']; // assuming the user ID is stored in the session

// Initialize counts for different statuses
$pendingCount = 0;
$rejectedCount = 0;
$underReviewCount = 0;
$completedCount = 0;

// Define number of rows per page
$rowsPerPage = 5;

// Get current page number from query string (default is 1 if not set)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startFrom = ($page - 1) * $rowsPerPage;

// Get selected status from query string (optional)
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch review assignments for the logged-in user, filtered by status if selected
$query = "
    SELECT 
        submissions.id AS submission_id,
        submissions.title, 
        submissions.category, 
        submissions.lead_author_name, 
        review_assignments.status, 
        review_assignments.assigned_at
    FROM review_assignments
    JOIN submissions ON review_assignments.submission_id = submissions.id
    WHERE review_assignments.reviewer_id = :user_id
";

// If a status is selected, filter by it
if ($selectedStatus) {
    $query .= " AND review_assignments.status = :status";
}

// Add pagination to the query
$query .= " LIMIT :startFrom, :rowsPerPage";

// Prepare and execute the query
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
if ($selectedStatus) {
    $stmt->bindParam(':status', $selectedStatus, PDO::PARAM_STR);
}
$stmt->bindParam(':startFrom', $startFrom, PDO::PARAM_INT);
$stmt->bindParam(':rowsPerPage', $rowsPerPage, PDO::PARAM_INT);
$stmt->execute();

// Fetch all the assignments
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total number of submissions for pagination (unfiltered)
$totalQuery = "
    SELECT COUNT(*) AS total 
    FROM review_assignments
    JOIN submissions ON review_assignments.submission_id = submissions.id
    WHERE review_assignments.reviewer_id = :user_id
";
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$totalStmt->execute();
$totalRows = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Calculate counts for all statuses (unfiltered)
$countQuery = "
    SELECT status, COUNT(*) AS count
    FROM review_assignments
    WHERE reviewer_id = :user_id
    GROUP BY status
";
$countStmt = $pdo->prepare($countQuery);
$countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$countStmt->execute();
$statusCounts = $countStmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize status counts
foreach ($statusCounts as $statusCount) {
    if ($statusCount['status'] === 'Pending') {
        $pendingCount = $statusCount['count'];
    } elseif ($statusCount['status'] === 'Declined') {
        $rejectedCount = $statusCount['count'];
    } elseif ($statusCount['status'] === 'Accepted') {
        $underReviewCount = $statusCount['count'];
    } elseif ($statusCount['status'] === 'Completed'){
        $completedCount = $statusCount ['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - My Reviews</title>
    <link rel="stylesheet" href="Ustyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">
    <div class="dashboard-container">
        <h2>My Reviews</h2>

        <!-- Stats boxes -->
        <div class="stats-grid">
            <div class="stat-box orange" onclick="filterData('Pending')">
                <h3><?= $pendingCount ?></h3>
                <p>Pending Review</p>
            </div>
            <div class="stat-box yellow" onclick="filterData('Accepted')">
                <h3><?= $underReviewCount ?></h3>
                <p>Accepted Review</p>
            </div>
            <div class="stat-box green" onclick="filterData('Completed')">
                <h3><?= $completedCount ?></h3>
                <p>Completed Review</p>
            </div>
            <div class="stat-box red" onclick="filterData('Declined')">
                <h3><?= $rejectedCount ?></h3>
                <p>Rejected Review</p>
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
                    <th>Status</th>
                    <th>Assigned At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?= htmlspecialchars($submission['submission_id']) ?></td>
                        <td><?= htmlspecialchars($submission['title']) ?></td>
                        <td><?= htmlspecialchars($submission['category']) ?></td>
                        <td><?= htmlspecialchars($submission['lead_author_name']) ?></td>
                        <td><span class="<?= strtolower(str_replace(' ', '-', $submission['status'])) ?>"><?= htmlspecialchars($submission['status']) ?></span></td>
                        <td><?= htmlspecialchars($submission['assigned_at']) ?></td>
                        <td>
                            <div class="action-icons">
                                <!-- Ensure that the onclick passes the correct submission ID -->
                                <i class="bx bx-show view" onclick="viewSubmission(<?= htmlspecialchars($submission['submission_id']) ?>)" title="View Submission"></i>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
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
        window.location.href = `create-review.php?id=${id}`;
    }
</script>

</body>
</html>
