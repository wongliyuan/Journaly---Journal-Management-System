<?php
session_start();
include '../config.php';

// Define number of rows per page
$rowsPerPage = 5;

// Get current page number from query string (default is 1 if not set)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startFrom = ($page - 1) * $rowsPerPage;

// Get selected status from query string (optional)
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Get logged-in user's ID from session
$editorId = $_SESSION['user_id']; // Assuming the editor's ID is stored in the session

// Prepare the base query for fetching review assignments
$query = "SELECT 
            review_assignments.id, 
            review_assignments.submission_id, 
            users.username AS reviewer_name, 
            review_assignments.status, 
            review_assignments.assigned_at, 
            submissions.title
          FROM review_assignments
          INNER JOIN submissions ON review_assignments.submission_id = submissions.id
          INNER JOIN users ON review_assignments.reviewer_id = users.id
          WHERE review_assignments.editor_id = :editor_id";

// If a status is selected, filter by it
if ($selectedStatus) {
    $query .= " AND review_assignments.status = :status";
}

// Add pagination
$query .= " LIMIT :startFrom, :rowsPerPage";

try {
    // Prepare the query
    $stmt = $pdo->prepare($query);

    // Bind the editor ID parameter
    $stmt->bindParam(':editor_id', $editorId, PDO::PARAM_INT);

    // Bind parameters for exact status (if applicable)
    if ($selectedStatus) {
        $stmt->bindParam(':status', $selectedStatus, PDO::PARAM_STR);
    }
    $stmt->bindParam(':startFrom', $startFrom, PDO::PARAM_INT);
    $stmt->bindParam(':rowsPerPage', $rowsPerPage, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch all results as an associative array
    $reviewAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving review assignments: " . $e->getMessage());
}

// Fetch total number of review assignments for stats (without pagination)
$totalQuery = "SELECT COUNT(*) AS total FROM review_assignments WHERE editor_id = :editor_id";
if ($selectedStatus) {
    $totalQuery .= " AND status = :status";
}
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->bindParam(':editor_id', $editorId, PDO::PARAM_INT);
if ($selectedStatus) {
    $totalStmt->bindParam(':status', $selectedStatus, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Count total review assignments by status for the dashboard
$statQuery = "SELECT status, COUNT(*) AS count 
              FROM review_assignments 
              WHERE editor_id = :editor_id 
              GROUP BY status";
$statStmt = $pdo->prepare($statQuery);
$statStmt->bindParam(':editor_id', $editorId, PDO::PARAM_INT);
$statStmt->execute();
$statusCounts = $statStmt->fetchAll(PDO::FETCH_ASSOC);

$pendingCount = $completedCount = $acceptedCount = $declinedCount = 0;

foreach ($statusCounts as $statusCount) {
    switch ($statusCount['status']) {
        case 'Pending':
            $pendingCount = $statusCount['count'];
            break;
        case 'Completed':
            $completedCount = $statusCount['count'];
            break;
        case 'Accepted':
            $acceptedCount = $statusCount['count'];
            break;
        case 'Declined':
            $declinedCount = $statusCount['count'];
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
    <title>Journaly - My Invitation</title>
    <link rel="stylesheet" href="Estyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">

    <div class="dashboard-container">
        <div class="header-container">
            <h2>My Invitations</h2>
        </div>

        <!-- Stats boxes with links to filter by status -->
        <div class="stats-grid">
            <div class="stat-box orange" onclick="filterData('Pending')">
                <div class="stat-header">
                    <h3><?= $pendingCount ?></h3>
                    <i class="bx bxs-error-circle icon"></i>
                </div>
                <p>Pending</p>
            </div>
            <div class="stat-box yellow" onclick="filterData('Accepted')">
                <div class="stat-header">
                    <h3><?= $acceptedCount ?></h3>
                    <i class="bx bxs-check-square icon"></i>
                </div>
                <p>Accepted</p>
            </div>
            <div class="stat-box green" onclick="filterData('Completed')">
                <div class="stat-header">
                    <h3><?= $completedCount ?></h3>
                    <i class="bx bx-x-circle icon"></i>
                </div>
                <p>Completed</p>
            </div>
            <div class="stat-box red" onclick="filterData('Declined')">
                <div class="stat-header">
                    <h3><?= $declinedCount ?></h3>
                    <i class="bx bx-x-circle icon"></i>
                </div>
                <p>Declined</p>
            </div>
        </div>

        <p onclick="filterData('')" style="font-size: 15px; color: blue; text-decoration: underline; text-align: right; cursor: pointer;">Clear Filter</p>

        <!-- Table -->
        <table class="table-container">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Reviewer</th>
                    <th>Invitation Status</th>
                    <th>Assigned At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reviewAssignments)): ?>
                    <?php foreach ($reviewAssignments as $assignment): ?>
                        <tr>
                            <td><?= htmlspecialchars($assignment['submission_id']) ?></td>
                            <td><?= htmlspecialchars($assignment['title']) ?></td>
                            <td><?= htmlspecialchars($assignment['reviewer_name']) ?></td>
                            <td><?= htmlspecialchars($assignment['status']) ?></td>
                            <td><?= htmlspecialchars($assignment['assigned_at']) ?></td>
                            <td>
                                <div class="action-icons">
                                    <i class="bx bx-show view" onclick="viewAssignment(<?= $assignment['submission_id'] ?>)" title="View Assignment"></i>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No review assignments found.</td>
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

    function viewAssignment(id) {
        // Replace with your actual URL for viewing the assignment details
        window.location.href = `view-submission.php?id=${id}`;
    }
</script>

</body>
</html>
