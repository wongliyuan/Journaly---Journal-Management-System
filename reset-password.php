<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="Gstyle.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
</head>
<body style="background: url('https://images.unsplash.com/photo-1731955196267-e863d6f39794?q=80&w=2893&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center fixed; background-size: cover;">
    
<?php include 'navbar.php'; ?>

<?php
include '../config.php';

$error_message = ''; // Variable to store error messages

// Check if the token is present in the URL when loading the page
$token = $_GET['token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($token)) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid or Missing Token',
                text: 'The token is missing or invalid. Redirecting you to the homepage.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '../GENERAL/homepage.php';
            });
        </script>";
        exit;
    }

    // Check if the token exists in the database
    $stmt = $pdo->prepare("SELECT expires_at FROM password_resets WHERE token = :token");
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Token',
                text: 'The reset link is invalid or has already been used.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '../GENERAL/homepage.php';
            });
        </script>";
        exit;
    }

    // Validate the token's expiration
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strtotime($reset['expires_at']) < time()) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Expired Token',
                text: 'The reset link has expired. Redirecting you to the homepage.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '../GENERAL/homepage.php';
            });
        </script>";
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token)) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid or Missing Token',
                text: 'The token is missing or invalid. Redirecting you to the homepage.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '../GENERAL/homepage.php';
            });
        </script>";
        exit;
    }

    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if the token is valid
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if the token has expired
            if (strtotime($reset['expires_at']) < time()) {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Token has expired.',
                        confirmButtonText: 'OK'
                    });
                </script>";
                exit;
            }

            // Update the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :user_id");
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':user_id', $reset['user_id']);

            if ($stmt->execute()) {
                // Delete the used reset token
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
                $stmt->bindParam(':token', $token);
                $stmt->execute();

                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Password has been reset successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '../GENERAL/homepage.php';
                    });
                </script>";
                exit;

            } else {
                $error_message = "Database error. Please try again.";
            }
        } else {
            $error_message = "Invalid token.";
        }
    }
}
?>

<div class="formContainer">
    <form action="reset-password.php" method="POST">
        <h2>Reset Password</h2>
        <?php if (!empty($error_message)) : ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?php echo $error_message; ?>',
                    confirmButtonText: 'OK'
                });
            </script>
        <?php endif; ?>
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="input_box">
            <input type="password" name="password" placeholder="Enter new password" required>
            <i class='bx bx-lock password'></i>
        </div>
        <div class="input_box">
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            <i class='bx bx-lock password'></i>
        </div>
        <button type="submit" name="submit">Reset Password</button>
    </form>
</div>


