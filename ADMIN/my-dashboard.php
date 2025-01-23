<?php
session_start();
include '../config.php';

$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

try {
    if (isset($_GET['month']) && isset($_GET['year'])) {
        // Filtered data for the selected month and year
        $filter = true;
        $filterParams = ['month' => $currentMonth, 'year' => $currentYear];
        
        // Total Users
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM users WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year");
        $stmt->execute($filterParams);
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total Submissions
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM submissions WHERE MONTH(submission_date) = :month AND YEAR(submission_date) = :year");
        $stmt->execute($filterParams);
        $totalSubmissions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total Editions
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM editions WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year");
        $stmt->execute($filterParams);
        $totalEditions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total Updates
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM announcements WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year");
        $stmt->execute($filterParams);
        $totalUpdates = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total Submissions by Category
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM submissions WHERE MONTH(submission_date) = :month AND YEAR(submission_date) = :year GROUP BY category");
        $stmt->execute($filterParams);
        $totalCategory = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Monthly Submissions with Filter (if month and year are specified)
        $queryMonthlySubmissions = "
        SELECT MONTH(submission_date) AS month, COUNT(*) AS total 
        FROM submissions 
        WHERE MONTH(submission_date) = :month AND YEAR(submission_date) = :year 
        GROUP BY MONTH(submission_date)";
        $stmtMonthlySubmissions = $pdo->prepare($queryMonthlySubmissions);
        $stmtMonthlySubmissions->execute($filterParams);
        $monthlySubmissions = $stmtMonthlySubmissions->fetchAll(PDO::FETCH_ASSOC);
        $submissionMonths = array_fill(0, 12, 0); // Initialize all months with zero
        if (!empty($monthlySubmissions)) {
            // Populate only the filtered month
            $submissionMonths[$currentMonth - 1] = $monthlySubmissions[0]['total'];
        }

        // Filtered User Distribution for the selected month and year
        $queryUserDistribution = "
        SELECT country, COUNT(*) AS total 
        FROM users 
        WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year 
        AND country IS NOT NULL 
        GROUP BY country";
        $stmtUserDistribution = $pdo->prepare($queryUserDistribution);
        $stmtUserDistribution->execute($filterParams);
        $userDistribution = $stmtUserDistribution->fetchAll(PDO::FETCH_ASSOC);

        // Filtered Submissions by Category for the selected month and year
        $queryCategoryDistribution = "
        SELECT category, COUNT(*) AS total 
        FROM submissions 
        WHERE MONTH(submission_date) = :month AND YEAR(submission_date) = :year 
        GROUP BY category";
        $stmtCategoryDistribution = $pdo->prepare($queryCategoryDistribution);
        $stmtCategoryDistribution->execute($filterParams);
        $categoryDistribution = $stmtCategoryDistribution->fetchAll(PDO::FETCH_ASSOC);

        // Filtered Role Distribution for the selected month and year
        $queryRoleDistribution = "
        SELECT role, COUNT(*) AS total 
        FROM users 
        WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year 
        GROUP BY role";
        $stmtRoleDistribution = $pdo->prepare($queryRoleDistribution);
        $stmtRoleDistribution->execute($filterParams);
        $roleDistribution = $stmtRoleDistribution->fetchAll(PDO::FETCH_ASSOC);

        // Filtered Assignment Status for the selected month and year
        $queryAssignmentStatus = "
        SELECT assignment_status, COUNT(*)
        FROM editor_assignments AS total
        WHERE MONTH(assigned_at) = :month AND YEAR(assigned_at) = :year
        GROUP BY assignment_status";
        $stmtAssignmentStatus = $pdo->prepare($queryAssignmentStatus);
        $stmtAssignmentStatus->execute($filterParams);
        $assignmentStatusData = $stmtAssignmentStatus->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // Total data (no filter applied)
        $filter = false;

        // Total Users
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total Submissions
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM submissions");
        $totalSubmissions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total Editions
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM editions");
        $totalEditions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total Updates
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM announcements");
        $totalUpdates = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total Category
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM submissions GROUP BY category");
        $totalCategory = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Monthly Submissions without Filter (all months for the current year)
        $queryMonthlySubmissions = "
        SELECT MONTH(submission_date) AS month, COUNT(*) AS total 
        FROM submissions 
        WHERE YEAR(submission_date) = :year 
        GROUP BY MONTH(submission_date)";
        $stmtMonthlySubmissions = $pdo->prepare($queryMonthlySubmissions);
        $stmtMonthlySubmissions->execute(['year' => $currentYear]);
        $monthlySubmissions = $stmtMonthlySubmissions->fetchAll(PDO::FETCH_ASSOC);
        $submissionMonths = array_fill(0, 12, 0); // Initialize all months with zero
        foreach ($monthlySubmissions as $submission) {
            $submissionMonths[$submission['month'] - 1] = $submission['total'];
        }

        // User Distribution without filtering
        $queryUserDistribution = "
        SELECT country, COUNT(*) AS total 
        FROM users 
        WHERE country IS NOT NULL 
        GROUP BY country";
        $stmtUserDistribution = $pdo->prepare($queryUserDistribution);
        $stmtUserDistribution->execute();
        $userDistribution = $stmtUserDistribution->fetchAll(PDO::FETCH_ASSOC);

        // Submissions by Category without filtering
        $queryCategoryDistribution = "
        SELECT category, COUNT(*) AS total 
        FROM submissions 
        GROUP BY category";
        $stmtCategoryDistribution = $pdo->prepare($queryCategoryDistribution);
        $stmtCategoryDistribution->execute();
        $categoryDistribution = $stmtCategoryDistribution->fetchAll(PDO::FETCH_ASSOC);

        // Role Distribution without filtering
        $queryRoleDistribution = "
        SELECT role, COUNT(*) AS total 
        FROM users 
        GROUP BY role";
        $stmtRoleDistribution = $pdo->prepare($queryRoleDistribution);
        $stmtRoleDistribution->execute();
        $roleDistribution = $stmtRoleDistribution->fetchAll(PDO::FETCH_ASSOC);

        // Filtered Assignment Status for the selected month and year
        $queryAssignmentStatus = "
        SELECT assignment_status, COUNT(*) AS total
        FROM editor_assignments
        GROUP BY assignment_status";
        $stmtAssignmentStatus = $pdo->prepare($queryAssignmentStatus);
        $stmtAssignmentStatus->execute($filterParams);
        $assignmentStatusData = $stmtAssignmentStatus->fetchAll(PDO::FETCH_ASSOC);
      
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - Admin Dashboard</title>
    <link rel="stylesheet" href="Astyle.css" />
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
                <?php for ($y = $currentYear; $y >= ($currentYear - 5); $y--): ?>
                    <option value="<?= $y ?>" <?= (isset($_GET['year']) && $y == $_GET['year']) ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit">Filter</button>
            <button type="button" id="clear-filter" onclick="window.location.href='my-dashboard.php';">Clear</button>
        </form>
    </header>

    <div class="dashboard-grid">
        <!-- Row 1: 4 summary cards -->
        <div class="card summary-card">
            <h2>Total Users</h2>
            <p class="amount"><?= $totalUsers ?></p>
        </div>
        <div class="card summary-card">
            <h2>Total Submissions</h2>
            <p class="amount"><?= $totalSubmissions ?></p>
        </div>
        <div class="card summary-card">
            <h2>Total Editions</h2>
            <p class="amount"><?= $totalEditions ?></p>
        </div>
        <div class="card summary-card">
            <h2>Total Updates</h2>
            <p class="amount"><?= $totalUpdates ?></p>
        </div>


        <!-- Row 2: 2 wide elements -->
        <div class="card chart-card">
            <h3>Total Submissions</h3>
            <canvas id="submissionTrendChart"></canvas>
        </div>
        <div class="card chart-card">
            <h3>User Demographic</h3>
            <canvas id="userCountryChart"></canvas>
        </div>

        <!-- Row 3: 4 summary cards -->
        <div class="card row-3-chart-role">
            <h3>Role Distribution</h3>
            <canvas id="roleDistributionChart"></canvas>
        </div>
        <div class="card row-3-chart-time">
            <h3>Submission by Category</h3>
            <canvas id="submissionCategoryChart"></canvas>
        </div>
        <div class="card row-3-chart-workload">
            <h3>Assignment Status</h3>
            <canvas id="assignmentStatusChart"></canvas>
        </div>

    </div>
</main>

<script>
document.getElementById('clear-filter').addEventListener('click', function () {
// Redirect to the current page without query parameters
    window.location.href = 'my-dashboard.php';
});

// Extract the data for the chart from PHP
const categoryData = <?php echo json_encode($userDistribution); ?>;

// Prepare the labels (country names) and datasets (user counts)
const country = categoryData.map(data => data.country);
const userCounts = categoryData.map(data => data.total);

// Set up the chart
const ctx = document.getElementById('userCountryChart').getContext('2d');
const userCountryChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: country, // Country names
        datasets: [{
            label: 'Number of Users',
            data: userCounts, // Number of users in each country
            backgroundColor: '#FDAAAA', // Bar color
            borderColor: '#F97C7C', // Bar border color
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1, // Step size for the Y-axis
                }
            },
            x: {
                ticks: {
                    autoSkip: true, // Avoid label overlap
                    maxRotation: 45, // Rotate x-axis labels if necessary
                    minRotation: 45
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.raw + ' users'; // Customize the tooltip label
                    }
                }
            }
        }
    }
});

const roleData = <?php echo json_encode($roleDistribution); ?>;
// Prepare the labels (roles) and datasets (user counts)
const roles = roleData.map(data => data.role);
const roleCounts = roleData.map(data => data.total);

// Set up the chart
const ctxRole = document.getElementById('roleDistributionChart').getContext('2d');
const roleDistributionChart = new Chart(ctxRole, {
    type: 'doughnut', // Use 'pie' or 'doughnut'
    data: {
        labels: roles, // Role names
        datasets: [{
            label: 'User Role Distribution',
            data: roleCounts, // Number of users per role
            backgroundColor: [
                '#F97C7C', '#FDAAAA', '#2B4570'
            ], // Different colors for each role
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top', // Position of the legend
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return `${tooltipItem.label}: ${tooltipItem.raw} users`; // Customize tooltip
                    }
                }
            }
        }
    }
});

// Monthly Submission
const monthlySubmissionData = <?= json_encode($submissionMonths); ?>;
const ctxSubmission = document.getElementById('submissionTrendChart').getContext('2d');
const submissionTrendChart = new Chart(ctxSubmission, {
    type: 'line',
    data: {
        labels: [
            'January', 'February', 'March', 'April', 'May', 'June', 
            'July', 'August', 'September', 'October', 'November', 'December'
        ], // Months
        datasets: [{
            label: 'Monthly Submissions',
            data: monthlySubmissionData, // Submissions per month
            backgroundColor: '#7180AC', // Line fill color
            borderColor: '#2B4570', // Line border color
            borderWidth: 2,
            tension: 0.2 // Smoother lines
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1 // Adjust as needed based on data
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return `${tooltipItem.raw} submissions`; // Tooltip format
                    }
                }
            },
            legend: {
                display: true,
                position: 'top' // Adjust legend position
            }
        }
    }
});

// Extract the data for the submission by category chart from PHP
const cData = <?php echo json_encode($categoryDistribution); ?>;

// Prepare the labels (categories) and datasets (submission counts)
const categories = cData.map(data => data.category);
const submissionCounts = cData.map(data => data.total);

// Set up the chart
const ctxCategory = document.getElementById('submissionCategoryChart').getContext('2d');
const submissionCategoryChart = new Chart(ctxCategory, {
    type: 'bar',
    data: {
        labels: categories, // Categories for the X-axis
        datasets: [{
            label: 'Number of Submissions',
            data: submissionCounts, // Number of submissions in each category
            backgroundColor: '#7180AC', // Bar color
            borderColor: '#2B4570', // Bar border color
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1 // Step size for the Y-axis
                }
            },
            x: {
                ticks: {
                    autoSkip: true, // Avoid label overlap
                    maxRotation: 45, // Rotate x-axis labels if necessary
                    minRotation: 45
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.raw + ' submissions'; // Customize the tooltip label
                    }
                }
            }
        }
    }
});

// Extract assignment status data from PHP
const assignmentStatusData = <?php echo json_encode($assignmentStatusData); ?>;

// Prepare the labels (assignment statuses) and datasets (counts)
const statuses = assignmentStatusData.map(data => data.assignment_status);
const statusCounts = assignmentStatusData.map(data => data.total);

// Set up the pie chart
const ctxStatus = document.getElementById('assignmentStatusChart').getContext('2d');
const assignmentStatusChart = new Chart(ctxStatus, {
    type: 'doughnut', // Pie chart type
    data: {
        labels: statuses, // Assignment statuses
        datasets: [{
            label: 'Assignment Status Distribution',
            data: statusCounts, // Number of assignments for each status
            backgroundColor: ['#F97C7C', '#FDAAAA', '#2B4570'], // Custom colors for each status
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top', // Position of the legend
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return `${tooltipItem.label}: ${tooltipItem.raw} assignments`; // Customize tooltip label
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
