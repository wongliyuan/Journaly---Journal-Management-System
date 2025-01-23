<?php
// Start the session at the top
session_start();

// Include the database connection
include '../config.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the user data from the form
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if the username or email already exists
    try {
        // Prepare the query to check for existing username or email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // If a user is found with the same username or email, show an error message
        if ($stmt->rowCount() > 0) {
            $_SESSION['error_message'] = "Error: Username or Email already exists. Please choose a different one.";
            header("Location: add-user.php"); // Redirect to the form page
            exit();
        } else {
            // Prepare the SQL query to insert the new user
            $query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role', $role);

            // Execute the query
            $stmt->execute();

            // Set success message and redirect to my-user.php
            $_SESSION['success_message'] = "User added successfully!";
            header("Location: my-user.php"); //directed to my-user page
            exit();
        }
    } catch (PDOException $e) {
        // Catch and display error if any
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Close the database connection (optional since PDO is not persistent)
$pdo = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - Add User</title>
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
            title: 'Successfully',
            text: '" . $_SESSION['success_message'] . "'
        });
        </script>";
        unset($_SESSION['success_message']); 
    }

    // If there's an error message in the session, trigger SweetAlert for it
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

    <!-- Add User Form -->
    <form action="add-user.php" method="POST">
        <h2>Create New User</h2>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required />

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required />

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required />

        <label for="role">Role:</label>
        <select id="role" name="role" required>
            <option value="Admin">Admin</option>
            <option value="Editor">Editor</option>
            <option value="User">User</option>
        </select>

        <div class="button-container">
        <button type="submit">Create</button>
        <button type="button" onclick="window.location.href='my-user.php';">Cancel</button>
        </div>
    </form>
</main>

</body>
</html>
