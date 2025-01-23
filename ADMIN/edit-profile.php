<?php
session_start();
include '../config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'] ?? '';
    $institution = $_POST['institution'] ?? '';
    $country = $_POST['country'] ?? '';
    $expertise_area = $_POST['expertise_area'] ?? '';
    $profile_picture = $_POST['profile_picture'] ?? null;

    // Profile picture upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $image_tmp = $_FILES['profile_image']['tmp_name'];
    $image_name = uniqid() . '_' . basename($_FILES['profile_image']['name']); // Generate unique name
    $image_path = '../uploads/' . $image_name;

    if (move_uploaded_file($image_tmp, $image_path)) {
        $profile_picture = $image_name; // Update picture path
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload profile picture.']);
        exit();
    }
} else {
    // If no new picture is uploaded, retrieve the current profile picture from the database
    $query = "SELECT profile_picture FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $profile_picture = $result['profile_picture']; // Keep the existing profile picture
}

    // Handle password update if provided
    $password_update = '';
    if (!empty($_POST['password'])) {
        if ($_POST['password'] === $_POST['confirm_password']) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_update = ", password = :password";
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
            exit();
        }
    }

    // Update user query
    $update_query = "UPDATE users 
    SET bio = :bio, institution = :institution, country = :country, 
        expertise_area = :expertise_area, profile_picture = :profile_picture $password_update
    WHERE id = :user_id";

    $stmt_update = $pdo->prepare($update_query);

    // Bind profile_picture conditionally
    if ($profile_picture) {
    $stmt_update->bindParam(':profile_picture', $profile_picture, PDO::PARAM_STR);
    } else {
    $stmt_update->bindValue(':profile_picture', null, PDO::PARAM_NULL);
    }

    // Bind parameters
    $stmt_update->bindParam(':bio', $bio, PDO::PARAM_STR);
    $stmt_update->bindParam(':institution', $institution, PDO::PARAM_STR);
    $stmt_update->bindParam(':country', $country, PDO::PARAM_STR);
    $stmt_update->bindParam(':expertise_area', $expertise_area, PDO::PARAM_STR);
    // $stmt_update->bindParam(':profile_picture', $profile_picture, PDO::PARAM_STR);
    $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if (!empty($password_update)) {
        $stmt_update->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    }

    // Execute update
    if ($stmt_update->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating profile.']);
    }
    exit();
}
?>