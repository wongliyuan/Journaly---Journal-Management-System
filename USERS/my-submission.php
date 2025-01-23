<?php 
include '../config.php'; 
session_start();

// Get the logged-in user's ID
$userId = $_SESSION['user_id'];

// Define number of rows per page
$rowsPerPage = 5;

// Get current page number from query string (default is 1 if not set)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startFrom = ($page - 1) * $rowsPerPage;

// Get selected status from query string (optional)
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Initialize counts
$pendingCount = 0;
$underReviewCount = 0;
$revisionRequiredCount = 0;
$acceptedCount = 0;
$rejectedCount = 0;
$publishedCount = 0;

// Prepare the base query for fetching submissions
$query = "SELECT id, title, lead_author_name, submission_date, status 
          FROM submissions 
          WHERE user_id = :user_id";

// If a status is selected, filter by it
if ($selectedStatus) {
    $query .= " AND status = :status";
}

// Add pagination
$query .= " LIMIT :startFrom, :rowsPerPage";

try {
    // Prepare the query
    $stmt = $pdo->prepare($query);

    // Bind parameters for status (if applicable)
    if ($selectedStatus) {
        $stmt->bindParam(':status', $selectedStatus, PDO::PARAM_STR);
    }
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
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
$totalQuery = "SELECT COUNT(*) AS total FROM submissions WHERE user_id = :user_id";
if ($selectedStatus) {
    $totalQuery .= " AND status = :status";
}
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
if ($selectedStatus) {
    $totalStmt->bindParam(':status', $selectedStatus, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Count total submissions by status for the dashboard
$statQuery = "SELECT status, COUNT(*) AS count FROM submissions WHERE user_id = :user_id GROUP BY status";
$statStmt = $pdo->prepare($statQuery);
$statStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$statStmt->execute();
$statusCounts = $statStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($statusCounts as $statusCount) {
    if ($statusCount['status'] === 'Pending') {
        $pendingCount = $statusCount['count'];
    } elseif ($statusCount['status'] === 'Under Review') {
        $underReviewCount = $statusCount['count'];
    } elseif ($statusCount['status'] === 'Revision Required') {
        $revisionRequiredCount = $statusCount['count'];
    } elseif ($statusCount['status'] === 'Accepted') {
        $acceptedCount = $statusCount['count'];
    } elseif ($statusCount['status'] === 'Rejected') {
        $rejectedCount = $statusCount['count'];
    } elseif ($statusCount['status'] === 'Published') {
        $publishedCount = $statusCount['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - My Submissions</title>
    <link rel="stylesheet" href="Ustyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">
    <div class="dashboard-container">
        <div class="header-container">
            <h2>My Submissions</h2>
            <button class="add-btn" onclick="window.location.href='add-submission.php';">
                <i class='bx bx-plus' style="margin-right: 5px; font-size: 20px;"></i>Add Submission
            </button>
        </div>

        <!-- Stats boxes -->
        <div class="stats-grid">
            <div class="stat-box blue" onclick="filterData('Pending')">
                <h3><?= $pendingCount ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-box orange" onclick="filterData('Under Review')">
                <h3><?= $underReviewCount ?></h3>
                <p>Under Review</p>
            </div>
            <div class="stat-box yellow" onclick="filterData('Revision Required')">
                <h3><?= $revisionRequiredCount ?></h3>
                <p>Revision Required</p>
            </div>
            <div class="stat-box green" onclick="filterData('Accepted')">
                <h3><?= $acceptedCount ?></h3>
                <p>Accepted</p>
            </div>
            <div class="stat-box red" onclick="filterData('Rejected')">
                <h3><?= $rejectedCount ?></h3>
                <p>Rejected</p>
            </div>
            
        </div>

        <p onclick="filterData('')" style="font-size: 15px; color: blue; text-decoration: underline; text-align: right; cursor: pointer;">Clear Filter</p>

        <!-- Table -->
        <table class="table-container">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Date of Submission</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?= htmlspecialchars($submission['id']) ?></td>
                        <td><?= htmlspecialchars($submission['title']) ?></td>
                        <td><?= htmlspecialchars($submission['lead_author_name']) ?></td>
                        <td><?= htmlspecialchars($submission['submission_date']) ?></td>
                        <td><span class="<?= strtolower($submission['status']) ?>"><?= htmlspecialchars($submission['status']) ?></span></td>
                        <td>
                            <div class="action-icons">
                                <i class="bx bx-show view" onclick="viewSubmission(<?= $submission['id'] ?>)" title="View Submission"></i>
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
        window.location.href = `view-submission.php?id=${id}`;
    }
</script>

</body>
</html>
