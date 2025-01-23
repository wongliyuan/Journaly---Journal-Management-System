<?php
session_start();

// Include database connection
include '../config.php';

// Check if the user ID is provided via the URL
if (isset($_GET['id'])) {
    $view_user_id = $_GET['id'];

    // Fetch the user details from the database
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $view_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // If user doesn't exist, redirect to error page
        header("Location: error.php");
        exit();
    }

    // If the user is an editor, fetch the count for Invitation Sent and Reviews Completed
    if ($user['role'] == 'Editor') {
        // Fetch count of Invitations Sent
        $query_invitations = "SELECT COUNT(*) FROM review_assignments WHERE editor_id = :user_id";
        $stmt_invitations = $pdo->prepare($query_invitations);
        $stmt_invitations->bindParam(':user_id', $view_user_id, PDO::PARAM_INT);
        $stmt_invitations->execute();
        $invitations_sent = $stmt_invitations->fetchColumn();

        // Fetch count of Reviews Completed
        $query_reviews_completed = "SELECT COUNT(*) FROM review_assignments WHERE editor_id = :user_id AND status = 'Completed'";
        $stmt_reviews_completed = $pdo->prepare($query_reviews_completed);
        $stmt_reviews_completed->bindParam(':user_id', $view_user_id, PDO::PARAM_INT);
        $stmt_reviews_completed->execute();
        $reviews_completed = $stmt_reviews_completed->fetchColumn();
    } else {
        // For non-editors, fetch published articles as before
        $query_articles = "SELECT COUNT(*) FROM submissions WHERE user_id = :user_id AND status = 'Published'";
        $stmt_articles = $pdo->prepare($query_articles);
        $stmt_articles->bindParam(':user_id', $view_user_id, PDO::PARAM_INT);
        $stmt_articles->execute();
        $articles_published = $stmt_articles->fetchColumn();

        // For non-editors, fetch count of Reviews Completed
        $query_reviews_completed = "SELECT COUNT(*) FROM review_assignments WHERE reviewer_id = :user_id AND status = 'Completed'";
        $stmt_reviews_completed = $pdo->prepare($query_reviews_completed);
        $stmt_reviews_completed->bindParam(':user_id', $view_user_id, PDO::PARAM_INT);
        $stmt_reviews_completed->execute();
        $reviews_completed = $stmt_reviews_completed->fetchColumn();
    }
    
} else {
    // If no user_id is passed, redirect to error page
    header("Location: error.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - View Profile</title>
    <link rel="stylesheet" href="Astyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="main-content">
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
            <div class="profile-info">
                <h1 style="margin-top:10px;"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p style="font-size: 15px;"class="tagline"><?php echo htmlspecialchars($user['role']); ?></p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="profile-main">
            <!-- Left Section -->
            <div class="profile-left">
                <div class="section">
                    <h3>About Me</h3>
                    <p><?php echo htmlspecialchars($user['bio']) ?: 'This user has not written a bio yet.'; ?></p>
                </div>
                <div class="section">
                    <h3>Institution</h3>
                    <p><?php echo htmlspecialchars($user['institution']) ?: 'Institution not provided.'; ?></p>
                </div>
                <div class="section">
                    <h3>Journal Activity</h3>
                    <ul>
                        <?php if ($user['role'] == 'Editor'): ?>
                            <li>Invitation Sent: <?php echo $invitations_sent; ?></li>
                            <li>Reviews Completed: <?php echo $reviews_completed; ?></li>
                        <?php else: ?>
                            <li>Articles Published: <?php echo $articles_published; ?></li>
                            <li>Reviews Completed: <?php echo $reviews_completed; ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Right Section -->
            <div class="profile-right">
                <div class="section">
                    <h3>Contact Information</h3>
                    <p><i class='bx bx-envelope'></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class='bx bx-calendar'></i> Joined: <?php echo htmlspecialchars($user['created_at']); ?></p>
                </div>
                <div class="section">
                    <h3>Country</h3>
                    <p><?php echo htmlspecialchars($user['country']) ?: 'Country not provided.'; ?></p>
                </div>
                <div class="section">
                    <h3>Expertise Area</h3>
                    <p><?php echo htmlspecialchars($user['expertise_area']) ?: 'Expertise not provided.'; ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
