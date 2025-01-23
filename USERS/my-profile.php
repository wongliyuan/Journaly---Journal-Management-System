<?php
session_start();

// Include database connection
include '../config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../GENERAL/homepage.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from the database
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: error.php");
    exit();
}

// Handle account deletion
if (isset($_POST['delete_account'])) {
    $delete_query = "DELETE FROM users WHERE id = :user_id";
    $stmt_delete = $pdo->prepare($delete_query);
    $stmt_delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    if ($stmt_delete->execute()) {
        session_destroy();
        echo "Account deleted successfully!";
    } else {
        echo "Error deleting account.";
    }
    exit();
}

// Fetch count of published articles
$query_articles = "SELECT COUNT(*) FROM submissions WHERE user_id = :user_id AND status = 'Published'";
$stmt_articles = $pdo->prepare($query_articles);
$stmt_articles->bindParam(':user_id', $user_id, PDO::PARAM_INT);

// Execute the query and fetch the count
if ($stmt_articles->execute()) {
    $articles_published = $stmt_articles->fetchColumn();
} else {
    // Handle query failure
    echo "Error executing query: " . implode(", ", $stmt_articles->errorInfo());
    exit();
}

// Fetch count of completed reviews for the viewed user
$query_reviews = "SELECT COUNT(*) FROM review_assignments WHERE reviewer_id = :reviewer_id AND status = 'Completed'";
$stmt_reviews = $pdo->prepare($query_reviews);
$stmt_reviews->bindParam(':reviewer_id', $view_user_id, PDO::PARAM_INT); // Changed to view_user_id
$stmt_reviews->execute();
$reviews_completed = $stmt_reviews->fetchColumn();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - My Profile</title>
    <link rel="stylesheet" href="Ustyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="main-content" style="margin-top: 40px;">
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
            <div class="profile-info">
                <h1 style="margin-top:10px;"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p style="font-size: 15px;"class="tagline"><?php echo htmlspecialchars($user['role']); ?></p>
                <div class="profile-actions">
                    <button class="btn hire-me-btn" onclick="openEditProfileModal()">Edit Profile</button>
                    <button class="btn follow-btn" onclick="confirmDeleteAccount()">Delete Account</button>
                </div>
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
                        <li>Articles Published: <?php echo $articles_published; ?></li>
                        <li>Reviews Completed: <?php echo $reviews_completed; ?></li>
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

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditProfileModal()">&times;</span>
        
        <form id="updateProfileForm" method="POST" enctype="multipart/form-data" action="edit-profile.php">
            <h2>Edit Profile</h2>

                <!-- Profile Image -->
                <div class="input-row">
                    <div class="input-group">
                        <label for="profile_image">Profile Image</label>
                        <div class="file-input">
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" />
                            <label for="profile_image" class="custom-file-label">
                                <i class='bx bx-upload'></i>
                                <?php echo isset($user['profile_picture']) && !empty($user['profile_picture']) 
                                    ? basename($user['profile_picture']) 
                                    : 'Click here to upload image'; ?>
                            </label>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" />
                    </div>
                </div>

                <!-- Password Fields -->
                <div class="input-row">
                    <div class="input-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password" />
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" />
                    </div>
                </div>

                <!-- Bio, Institution, Country, and Expertise Area -->
                <div class="input-row">
                    <div class="input-group">
                        <label for="institution">Institution</label>
                        <input type="text" id="institution" name="institution" value="<?php echo htmlspecialchars($user['institution']); ?>" />
                    </div>
                    <div class="input-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country']); ?>" />
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="expertise_area">Expertise Area</label>
                        <input type="text" id="expertise_area" name="expertise_area" value="<?php echo htmlspecialchars($user['expertise_area']); ?>" />
                    </div>
                    <div class="input-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                </div>

            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</div>

<script>
    function openEditProfileModal() {
        document.getElementById('editProfileModal').style.display = 'block';
    }

    function closeEditProfileModal() {
        document.getElementById('editProfileModal').style.display = 'none';
    }

    // Function to update the file input label with the name of the selected file
    document.addEventListener('DOMContentLoaded', function () {
        const fileInputs = document.querySelectorAll('.file-input input[type="file"]');

        fileInputs.forEach(input => {
          input.addEventListener('change', function () {
            const fileName = this.files[0] ? this.files[0].name : "No file chosen";
            const label = this.nextElementSibling;
            label.innerHTML = `<i class='bx bx-upload'></i> ${fileName}`; // Update the label text with the file name
          });
        });
      });

    // Handle form submission with AJAX
    document.querySelector('#updateProfileForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('edit-profile.php', {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                    }).then(() => {
                        // Redirect to my-profile.php
                        window.location.href = '../USERS/my-profile.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Try Again',
                    });
                }
            })
            .catch((error) => {
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred.',
                    icon: 'error',
                    confirmButtonText: 'Try Again',
                });
                console.error('Error:', error);
            });
    });

    function confirmDeleteAccount() {
        Swal.fire({
            title: 'Are you sure?',
            text: "Your account will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: '',  // Current file (same file)
                    data: { delete_account: true },
                    success: function(response) {
                        alert(response);
                        window.location.href = '../GENERAL/homepage.php'; 
                    },
                    error: function() {
                        alert('Error deleting account.');
                    }
                });
            }
        });
    }
</script>

</body>
</html>
