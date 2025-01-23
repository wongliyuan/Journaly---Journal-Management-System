<?php
session_start();
include '../config.php';

// Define number of rows per page
$rowsPerPage = 5;

// Get current page number from query string (default is 1 if not set)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startFrom = ($page - 1) * $rowsPerPage;

// Get selected role from query string (optional)
$selectedRole = isset($_GET['role']) ? $_GET['role'] : '';

// Prepare the base query for fetching users
$query = "SELECT id, username, email, role, created_at FROM users";

// If a role is selected, filter by it
if ($selectedRole) {
    $query .= " WHERE role = :role";
}

// Add pagination
$query .= " LIMIT :startFrom, :rowsPerPage";

try {
    // Prepare the query
    $stmt = $pdo->prepare($query);

    // Bind parameters for role filtering
    if ($selectedRole) {
        $stmt->bindParam(':role', $selectedRole, PDO::PARAM_STR);
    }
    $stmt->bindParam(':startFrom', $startFrom, PDO::PARAM_INT);
    $stmt->bindParam(':rowsPerPage', $rowsPerPage, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch all results as an associative array
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error retrieving users: " . $e->getMessage());
}

// Fetch total number of users for stats (without pagination)
$totalQuery = "SELECT COUNT(*) AS total FROM users";
if ($selectedRole) {
    $totalQuery .= " WHERE role = :role";
}
$totalStmt = $pdo->prepare($totalQuery);
if ($selectedRole) {
    $totalStmt->bindParam(':role', $selectedRole, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Count total users by role for the dashboard
$statQuery = "SELECT role, COUNT(*) AS count FROM users GROUP BY role";
$statStmt = $pdo->query($statQuery);
$roleCounts = $statStmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize counts for each role
$adminCount = $editorCount = $userCount = 0;

foreach ($roleCounts as $roleCount) {
    switch ($roleCount['role']) {
        case 'Admin':
            $adminCount = $roleCount['count'];
            break;
        case 'Editor':
            $editorCount = $roleCount['count'];
            break;
        case 'User':
            $userCount = $roleCount['count'];
            break;
    }
}

// Display success or error messages from the session
if (isset($_SESSION['success_message'])) {
    echo "<p class='success'>" . $_SESSION['success_message'] . "</p>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<p class='error'>" . $_SESSION['error_message'] . "</p>";
    unset($_SESSION['error_message']);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journaly - My Users</title>
    <link rel="stylesheet" href="Astyle.css">
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="main-content">
    <div class="dashboard-container">
        <div class="header-container">
            <h2>My Users</h2>
                <button class="addUserBtn" onclick="window.location.href='add-user.php'">
                    <i class='bx bxs-user-plus' style="margin-right: 5px; font-size: 20px;"></i>Create User
                </button>
        </div>
        <!-- Stats boxes with links to filter by role -->
        <div class="stats-grid">
            <div class="stat-box red" onclick="filterData('Admin')">
                <div class="stat-header">
                    <h3><?= $adminCount ?></h3>
                </div>
                <p>Admin</p>
            </div>
            <div class="stat-box orange" onclick="filterData('Editor')">
                <div class="stat-header">
                    <h3><?= $editorCount ?></h3>
                </div>
                <p>Editor</p>
            </div>
            <div class="stat-box green" onclick="filterData('User')">
                <div class="stat-header">
                    <h3><?= $userCount ?></h3>
                </div>
                <p>User</p>
            </div>
        </div>

        <p onclick="filterData('')" style="font-size: 15px; color: blue; text-decoration: underline; text-align: right; cursor: pointer;">Clear Filter</p>

        <!-- Table -->
        <table class="table-container">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id'])?> </td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td>
                                <div class="action-icons">
                                    <i class="bx bx-show view" onclick="viewUser(<?= $user['id'] ?>)" title="View User"></i>
                                    <i class="bx bx-edit-alt edit" onclick="editUser(<?= $user['id'] ?>)" title="Edit User"></i>
                                    <i class="bx bx-trash delete" onclick="deleteUser(<?= $user['id'] ?>)" title="Delete User"></i>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?role=<?= $selectedRole ?>&page=<?= $page - 1 ?>" class="prev">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?role=<?= $selectedRole ?>&page=<?= $i ?>" class="page-number <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?role=<?= $selectedRole ?>&page=<?= $page + 1 ?>" class="next">Next</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function filterData(role) {
        let url = window.location.href.split('?')[0]; // Remove any query parameters
        if (role) {
            url += `?role=${role}`; // Append role to the URL
        }
        window.location.href = url; // Redirect to the new URL
    }

    function viewUser(id) {
        window.location.href = `view-user.php?id=${id}`;
    }

    function editUser(id) {
        window.location.href = `edit-user.php?id=${id}`;
    }

    function deleteUser(id) {
        Swal.fire({
            title: "Delete User?",
            text: "Are you sure you want to delete this user?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('delete-user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'User has been deleted.',
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete user.',
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while deleting the user.',
                    });
                });
            }
        });
    }
</script>
</body>
</html>
