<?php
session_start();
include '../config.php';

$editor_id = $_SESSION['user_id'];
// Set default values for current month and year
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : null; // Default to current month if no filter is applied
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : null; // Default to current year if no filter is applied


try {
    // Query to get the total number of submissions assigned to the editor
    $queryTotalSubmissions = "
    SELECT COUNT(*) AS total_submissions
    FROM editor_assignments
    WHERE editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(assigned_at) = :month " : "") . 
    ($currentYear ? "AND YEAR(assigned_at) = :year" : "");

    // Prepare and execute the query for total submissions
    $stmtTotalSubmissions = $pdo->prepare($queryTotalSubmissions);
    $params = ['editor_id' => $editor_id];
    if ($currentMonth) $params['month'] = $currentMonth;
    if ($currentYear) $params['year'] = $currentYear;
    $stmtTotalSubmissions->execute($params);
    $totalSubmissions = $stmtTotalSubmissions->fetch(PDO::FETCH_ASSOC)['total_submissions'];

    // Query to get the total number of invitations sent by the editor in the current or all-time period
    $queryInvitesSent = "
    SELECT COUNT(*) AS total_invites_sent
    FROM review_assignments
    WHERE editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(assigned_at) = :month " : "") . 
    ($currentYear ? "AND YEAR(assigned_at) = :year" : "");

    $stmtInvitesSent = $pdo->prepare($queryInvitesSent);
    $stmtInvitesSent->execute($params);
    $totalInvitesSent = $stmtInvitesSent->fetch(PDO::FETCH_ASSOC)['total_invites_sent'];

    // Query to get the total number of accepted invitations
    $queryAcceptedInvites = "
    SELECT COUNT(*) AS total_accepted_invites
    FROM review_assignments
    WHERE editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(assigned_at) = :month " : "") . 
    ($currentYear ? "AND YEAR(assigned_at) = :year" : "") . 
    " AND status = 'Accepted'";

    $stmtAcceptedInvites = $pdo->prepare($queryAcceptedInvites);
    $stmtAcceptedInvites->execute($params);
    $totalAcceptedInvites = $stmtAcceptedInvites->fetch(PDO::FETCH_ASSOC)['total_accepted_invites'];

    // Calculate the invitation acceptance rate (avoid division by zero)
    $acceptanceRate = ($totalInvitesSent > 0) ? ($totalAcceptedInvites / $totalInvitesSent) * 100 : 0;
    $formattedAcceptanceRate = number_format($acceptanceRate, 2) . '%';

    // Query for average time to decision
    $queryAvgTimeToDecision = "
    SELECT AVG(TIMESTAMPDIFF(DAY, assigned_at, updated_at)) AS avg_time_to_decision
    FROM editor_assignments
    WHERE editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(assigned_at) = :month " : "") . 
    ($currentYear ? "AND YEAR(assigned_at) = :year" : "");

    $stmtAvgTimeToDecision = $pdo->prepare($queryAvgTimeToDecision);
    $stmtAvgTimeToDecision->execute($params);
    $avgTimeToDecision = $stmtAvgTimeToDecision->fetch(PDO::FETCH_ASSOC)['avg_time_to_decision'];
    if ($avgTimeToDecision === null) $avgTimeToDecision = 0;

    // Query for accepted submissions count
    $queryAcceptedSubmissions = "
    SELECT COUNT(*) AS accepted_submissions
    FROM editor_assignments
    WHERE editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(assigned_at) = :month " : "") . 
    ($currentYear ? "AND YEAR(assigned_at) = :year" : "") . 
    " AND assignment_status = 'Accepted'";

    $stmtAcceptedSubmissions = $pdo->prepare($queryAcceptedSubmissions);
    $stmtAcceptedSubmissions->execute($params);
    $acceptedSubmissions = $stmtAcceptedSubmissions->fetch(PDO::FETCH_ASSOC)['accepted_submissions'];

    // Query for submission status counts
    $querySubmissionStatus = "
    SELECT status, COUNT(*) AS status_count
    FROM submissions
    WHERE editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(submission_date) = :month " : "") . 
    ($currentYear ? "AND YEAR(submission_date) = :year" : "") . 
    " GROUP BY status";

    $stmtSubmissionStatus = $pdo->prepare($querySubmissionStatus);
    $stmtSubmissionStatus->execute($params);

    // Initialize arrays to store the statuses and their counts
    $statusLabels = [];
    $statusCounts = [];
    while ($row = $stmtSubmissionStatus->fetch(PDO::FETCH_ASSOC)) {
        $statusLabels[] = $row['status'];
        $statusCounts[] = (int)$row['status_count'];
    }

    // Other queries remain the same, modifying them in the same manner for all-time data retrieval
    // Fetch reviewer activity: on-time vs overdue
    $queryReviewerActivity = "
    SELECT 
        SUM(CASE WHEN completed_at <= DATE_ADD(assigned_at, INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS on_time,
        SUM(CASE WHEN completed_at > DATE_ADD(assigned_at, INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS overdue
    FROM review_assignments 
    WHERE completed_at IS NOT NULL AND editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(assigned_at) = :month " : "") . 
    ($currentYear ? "AND YEAR(assigned_at) = :year" : "");

    $stmtReviewerActivity = $pdo->prepare($queryReviewerActivity);
    $params = ['editor_id' => $editor_id];
    if ($currentMonth) $params['month'] = $currentMonth;
    if ($currentYear) $params['year'] = $currentYear;
    $stmtReviewerActivity->execute($params);
    $reviewerActivity = $stmtReviewerActivity->fetch(PDO::FETCH_ASSOC);

    $queryTimeInPeerReview = "
    SELECT 
        SUM(CASE WHEN DATEDIFF(completed_at, assigned_at) < 7 THEN 1 ELSE 0 END) AS less_than_1_week,
        SUM(CASE WHEN DATEDIFF(completed_at, assigned_at) BETWEEN 7 AND 14 THEN 1 ELSE 0 END) AS between_1_2_weeks,
        SUM(CASE WHEN DATEDIFF(completed_at, assigned_at) BETWEEN 15 AND 30 THEN 1 ELSE 0 END) AS between_2_4_weeks,
        SUM(CASE WHEN DATEDIFF(completed_at, assigned_at) > 30 THEN 1 ELSE 0 END) AS more_than_1_month
    FROM review_assignments
    WHERE completed_at IS NOT NULL AND editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(assigned_at) = :month " : "") . 
    ($currentYear ? "AND YEAR(assigned_at) = :year" : "");

    $stmtTimeInPeerReview = $pdo->prepare($queryTimeInPeerReview);
    $params = ['editor_id' => $editor_id];
    if ($currentMonth) $params['month'] = $currentMonth;
    if ($currentYear) $params['year'] = $currentYear;
    $stmtTimeInPeerReview->execute($params);
    $timeInPeerReview = $stmtTimeInPeerReview->fetch(PDO::FETCH_ASSOC);

    $queryEditorialWorkload = "
    SELECT 
        status, COUNT(*) AS total_count 
    FROM submissions 
    WHERE status IN ('Pending', 'Under Review', 'Revision Required') AND editor_id = :editor_id
    " . ($currentMonth ? "AND MONTH(submission_date) = :month " : "") . 
    ($currentYear ? "AND YEAR(submission_date) = :year" : "") . 
    " GROUP BY status";

    $stmtEditorialWorkload = $pdo->prepare($queryEditorialWorkload);
    $params = ['editor_id' => $editor_id];
    if ($currentMonth) $params['month'] = $currentMonth;
    if ($currentYear) $params['year'] = $currentYear;
    $stmtEditorialWorkload->execute($params);
    $editorialWorkload = $stmtEditorialWorkload->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - Editor Dashboard</title>
    <link rel="stylesheet" href="Estyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">
    <header>
        <h2>My Dashboard</h2>
        <form method="GET" action="my-dashboard.php" class="date-filter">
            <label for="month">Select Month:</label>
            <select name="month" id="month">
                <option value="" <?= !isset($_GET['month']) ? 'selected' : '' ?>>Select</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= (isset($_GET['month']) && $m == $_GET['month']) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <label for="year">Select Year:</label>
            <select name="year" id="year">
                <option value="" <?= !isset($_GET['year']) ? 'selected' : '' ?>>Select</option>
                <?php for ($y = 2025; $y >= (2025 - 5); $y--): ?>
                    <option value="<?= $y ?>" <?= (isset($_GET['year']) && $y == $_GET['year']) ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit">Filter</button>
            <button type="button" id="clear-filter">Clear</button>
        </form>
    </header>

    <div class="dashboard-grid">
        <!-- Row 1: 4 summary cards -->
        <div class="card summary-card">
            <h2>Total Submission</h2>
            <p class="amount"><?= $totalSubmissions ?></p>
        </div>
        <div class="card summary-card">
            <h2>Avg. Time to Decision</h2>
            <p class="amount"><?= number_format($avgTimeToDecision, 1) ?> days</p>
        </div>
        <div class="card summary-card">
            <h2>Total Invitation</h2>
            <p class="amount"><?= $totalInvitesSent ?></p>
        </div>
        <div class="card summary-card">
            <h2>Invitation Acceptance</h2>
            <p class="amount"><?= $formattedAcceptanceRate ?></p>
        </div>


        <!-- Row 2: 2 wide elements -->
        <div class="card chart-card">
            <h3>Total Submission vs Accepted Submission</h3>
            <canvas id="submissionsVsAcceptedChart"></canvas>
        </div>
        <div class="card chart-card">
            <h3>Submissions by Status</h3>
            <canvas id="submissionCategoryChart"></canvas>
        </div>

        <!-- Row 3: 4 summary cards -->
        <div class="card row-3-chart-reviewer">
            <h3>Reviewer Activity</h3>
            <canvas id="reviewerActivityChart"></canvas>
        </div>
        <div class="card row-3-chart-time">
            <h3>Time in Peer Review</h3>
            <canvas id="timeInReviewChart"></canvas>
        </div>
        <div class="card row-3-chart-workload">
            <h3>Workload Distribution</h3>
            <canvas id="editorialWorkloadChart"></canvas>
        </div>

    </div>
</main>


<script>
    document.getElementById('clear-filter').addEventListener('click', function () {
    // Redirect to the current page without query parameters
        window.location.href = 'my-dashboard.php';
    });

    // Get the data from PHP (you can pass the data to JavaScript variables)
    var totalSubmissions = <?= $totalSubmissions ?>;
    var acceptedSubmissions = <?= $acceptedSubmissions ?>;

    // Create the chart
    var ctx = document.getElementById('submissionsVsAcceptedChart').getContext('2d');
    var submissionsVsAcceptedChart = new Chart(ctx, {
        type: 'bar',  // Bar chart
        data: {
            labels: ['Total', 'Accepted'],  // Labels for the chart
            datasets: [{
                label: 'Submissions',
                data: [totalSubmissions, acceptedSubmissions],  // Data points for the chart
                backgroundColor: ['#FDAAAA', '#7180AC'],  // Colors for the bars
                borderColor: ['#F97C7C', '#2B4570'],  // Border color for the bars
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            scales: {
                y: {
                    beginAtZero: true,  // Start the y-axis at 0
                }
            }
        }
    });

    // Get the data from PHP (you can pass the data to JavaScript variables)
    var statusLabels = <?= json_encode($statusLabels) ?>;  // The labels (statuses)
    var statusCounts = <?= json_encode($statusCounts) ?>;  // The counts of each status

    // Create the chart
    var ctx = document.getElementById('submissionCategoryChart').getContext('2d');
    var submissionCategoryChart = new Chart(ctx, {
        type: 'bar',  // Bar chart (can change to 'pie' if preferred)
        data: {
            labels: statusLabels,  // Use the statuses as the labels
            datasets: [{
                label: 'Submissions by Status',
                data: statusCounts,  // Use the counts for each status
                backgroundColor: ['#7180AC', '#FDAAAA', '#FFF7AE'],  // Colors for the bars
                borderColor: ['#2B4570', '#F97C7C', 'rgba(255, 206, 86, 1)'],  // Border color for the bars
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                // x: {
                //     beginAtZero: true,
                //     title: {
                //         display: true,
                //         text: 'Status'
                //     }
                // },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Submission'
                    }
                }
            }
        }
    });

        // Reviewer Activity Data (On-Time vs Overdue)
        const reviewerActivity = <?= json_encode([
            'on_time' => $reviewerActivity['on_time'],
            'overdue' => $reviewerActivity['overdue']
        ]) ?>;
        new Chart(document.getElementById('reviewerActivityChart'), {
            type: 'pie',
            data: {
                labels: ['On Time', 'Overdue'],
                datasets: [{
                    data: [reviewerActivity.on_time, reviewerActivity.overdue],
                    backgroundColor: ['#7180AC', '#FDAAAA'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'top' }
                }
            }
        });

        // Time in Peer Review Duration Categories
        const timeInPeerReview = <?= json_encode([
            'less_than_1_week' => $timeInPeerReview['less_than_1_week'],
            'between_1_2_weeks' => $timeInPeerReview['between_1_2_weeks'],
            'between_2_4_weeks' => $timeInPeerReview['between_2_4_weeks'],
            'more_than_1_month' => $timeInPeerReview['more_than_1_month']
        ]) ?>;
        new Chart(document.getElementById('timeInReviewChart'), {
            type: 'bar',
            data: {
                labels: ['< 1 Week', '1-2 Weeks', '2-4 Weeks', '> 1 Month'],
                datasets: [{
                    label: 'Submissions',
                    data: [
                        timeInPeerReview.less_than_1_week,
                        timeInPeerReview.between_1_2_weeks,
                        timeInPeerReview.between_2_4_weeks,
                        timeInPeerReview.more_than_1_month
                    ],
                    backgroundColor: '#FFF7AE'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    // x: { title: { display: true, text: 'Review Duration' } },
                    y: { title: { display: true, text: 'Number of Submissions' }, beginAtZero: true }
                }
            }
        });

        // Editorial Workload Distribution
        const editorialWorkload = <?= json_encode($editorialWorkload) ?>;
        const statuses = editorialWorkload.map(item => item.status);
        const workloadCounts = editorialWorkload.map(item => item.total_count);

        new Chart(document.getElementById('editorialWorkloadChart'), {
            type: 'doughnut',
            data: {
                labels: statuses,
                datasets: [{
                    label: 'Submissions',
                    data: workloadCounts,
                    backgroundColor: ['#FDAAAA', '#FFF7AE', '#7180AC'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'top' }
                }
            }
        });

    
</script>

</body>
</html>