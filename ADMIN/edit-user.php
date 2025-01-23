<?php
session_start();
include '../config.php';

if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Fetch user details from the database
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error_message'] = "User not found!";
            header("Location: my-user.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error fetching user details: " . $e->getMessage();
        header("Location: my-user.php");
        exit;
    }
} else {
    header("Location: my-user.php");
    exit;
}

// Handle form submission for updating user details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success_message'] = "User details updated successfully!";
        // Instead of immediate redirection, we use JavaScript in the HTML section
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - Edit User</title>
    <link rel="stylesheet" href="Astyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">
<?php
    // Display success or error message from session
    if (isset($_SESSION['success_message'])) {
        echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '" . $_SESSION['success_message'] . "'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'my-user.php';
            }
        });
        </script>";
        unset($_SESSION['success_message']); 
    }

    if (isset($_SESSION['error_message'])) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '" . $_SESSION['error_message'] . "'
            });
        </script>";
        unset($_SESSION['error_message']); // Clear the error message
    }
?>

<form action="edit-user.php?id=<?= htmlspecialchars($userId) ?>" method="post">
    <h2>Edit User</h2>
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label for="role">Role:</label>
    <select id="role" name="role" required>
        <option value="Admin" <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
        <option value="Editor" <?= $user['role'] == 'Editor' ? 'selected' : '' ?>>Editor</option>
        <option value="User" <?= $user['role'] == 'User' ? 'selected' : '' ?>>User</option>
    </select>

    <div class="button-container">
        <button type="submit">Save Changes</button>
        <button type="button" onclick="window.location.href='my-user.php';">Cancel</button>
    </div>
</form>
</main>

</body>
</html>
