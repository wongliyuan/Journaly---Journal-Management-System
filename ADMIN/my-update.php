<?php
session_start();

// Include the database connection
include '../config.php';

// Handle success and error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear messages after storing them in variables
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch announcements created by this user
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE created_by = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitAnnouncement'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];

    // Validate the title and content
    if (empty($title) || empty($content)) {
        $_SESSION['error_message'] = 'Title and content are required fields.';
        header('Location: my-update.php');
        exit();
    }

    // Handle file upload if provided
    $attachment = null; // Default to no file
    if (!empty($_FILES['attachment']['name'])) {
        $uploadDir = '../uploads/';
        $attachment = basename($_FILES['attachment']['name']);
        $uploadFile = $uploadDir . $attachment;

        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadFile)) {
            $_SESSION['error_message'] = 'File upload failed.';
            header('Location: my-update.php');
            exit();
        }
    }

    // Insert into the database
    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, attachment, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $attachment, $userId]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = 'Announcement created successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to create announcement.';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    }

    // Redirect back to the page with the success/error message
    header('Location: my-update.php');
    exit();
}

// Handle Deletion Logic
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];

    // Ensure the announcement belongs to the logged-in user
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ? AND created_by = ?");
    $stmt->execute([$deleteId, $userId]);

    $_SESSION['success_message'] = $stmt->rowCount() > 0
        ? 'Announcement deleted successfully!'
        : 'You do not have permission to delete this announcement.';
    header('Location: my-update.php');
    exit();
}

// Handle Edit Logic
if (isset($_GET['edit_id'])) {
    $editId = $_GET['edit_id'];

    // Fetch the announcement if it belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ? AND created_by = ?");
    $stmt->execute([$editId, $userId]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($announcement ?: []);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateAnnouncement'])) {
    $id = $_POST['announcement_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $attachment = $_FILES['attachment']['name'] ?? ''; // New attachment (if any)

    if (empty($title) || empty($content)) {
        $_SESSION['error_message'] = 'Title and content are required fields.';
        header('Location: my-update.php');
        exit();
    }

    // Get the current timestamp
    $updatedAt = date('Y-m-d H:i:s'); 

    // Fetch the current attachment from the database (if no new file is uploaded)
    $stmt = $pdo->prepare("SELECT attachment FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    $currentAttachment = $stmt->fetchColumn();

    // If a new file is uploaded, handle it
    if (!empty($attachment)) {
        $uploadDir = '../uploads/';
        $uploadFile = $uploadDir . basename($_FILES['attachment']['name']);
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadFile)) {
            $attachment = $attachment; // Set the new attachment
        } else {
            $_SESSION['error_message'] = 'File upload failed.';
            header('Location: my-update.php');
            exit();
        }
    } else if (isset($_POST['deleteAttachment']) && $_POST['deleteAttachment'] === 'true') {
        // If the delete button was clicked, delete the file
        if ($currentAttachment) {
            $filePath = "../uploads/" . $currentAttachment;
            if (file_exists($filePath)) {
                unlink($filePath); // Delete the file
            }
        }
        $attachment = null; // Remove the attachment from the database
    } else {
        // Keep the current attachment if no new file is uploaded
        $attachment = $currentAttachment;
    }

    try {
        // Update the announcement (whether new file uploaded or not)
        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, attachment = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$title, $content, $attachment, $updatedAt, $id]);

        $_SESSION['success_message'] = 'Announcement updated successfully!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    }

    // Redirect back to the page with the success/error message
    header('Location: my-update.php');
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - My Updates</title>
    <link rel="stylesheet" href="Astyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">
    <div class="dashboard-container">
        <div class="header-container">
            <h2>My Updates</h2>
                <button id="createAnnouncementBtn">
                    <i class='bx bx-plus' style="margin-right: 5px; font-size: 20px;"></i>Create Update
                </button>
        </div>

            
        <!-- Announcements List -->
        <table class="table-container">
        <thead>
            <tr>
                <th>Title</th>
                <th>Content</th>
                <th>Attachment</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <tr>
                        <td><?= htmlspecialchars($announcement['title']); ?></td>
                        <td><?= nl2br(htmlspecialchars($announcement['content'])); ?></td>
                        <td>
                            <?php if ($announcement['attachment']): ?>
                                <a href="../uploads/<?= htmlspecialchars($announcement['attachment']); ?>" download>Download File</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($announcement['created_at']))); ?></td>
                        <td>
                            <div class="action-icons">
                                <i class="bx bx-edit-alt edit" onclick="openEditModal(<?= $announcement['id']; ?>)" title="Edit" ></i>
                                <i class="bx bx-trash delete" onclick="confirmDelete(<?= $announcement['id']; ?>)" title="Delete"></i>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No announcements found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Create Modal Structure -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <h2>Create Update</h2>
            <form id="announcementForm" action="my-update.php" method="POST" enctype="multipart/form-data">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
                
                <label for="content">Content:</label>
                <textarea id="content" name="content" rows="5" required></textarea>
                
                <label for="attachment">Attach File (optional):</label>
                <input type="file" id="attachment" name="attachment">

                <div class="button-container">
                    <button type="submit" name="submitAnnouncement">Create</button>
                    <button type="button" onclick="window.location.href='my-update.php';">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal Structure -->
    <div id="edit-announcement-modal" class="modal">
        <div class="modal-content">
            <h2>Edit Announcement</h2>
            <form id="edit-announcement-form" action="my-update.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="announcement_id" id="edit-announcement-id">
                
                <label for="edit-title">Title:</label>
                <input type="text" id="edit-title" name="title" required>
                
                <label for="edit-content">Content:</label>
                <textarea id="edit-content" name="content" rows="5" required></textarea>
                
                <label for="edit-attachment">Attach File (optional):</label>
                <input type="file" id="edit-attachment" name="attachment">
                    <div id="current-attachment-container">
                        <p>Current Attachment: <span id="current-attachment"></span>
                        <button type="button" id="delete-attachment-btn">Delete</button>
                        <input type="hidden" name="deleteAttachment" id="deleteAttachmentField" value="false">
                        </p>
                    </div>

                <div class="button-container">
                    <button type="submit" name="updateAnnouncement">Update</button>
                    <button type="button" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
        <?php if ($success_message): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?= addslashes($success_message); ?>'
        });
    <?php endif; ?>

    <?php if ($error_message): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?= addslashes($error_message); ?>'
        });
    <?php endif; ?>

    // Get elements
    const modal = document.getElementById('announcementModal');
    const btn = document.getElementById('createAnnouncementBtn');
    const cancelBtn = document.querySelector('.button-container button[type="button"]'); // The Cancel button
    const form = document.getElementById('announcementForm');
    const editModal = document.getElementById('editAnnouncementModal');

    // Show the modal when the button is clicked
    btn.addEventListener('click', () => {
        modal.style.display = 'block';
    });

    // Hide the modal when the Cancel button is clicked
    cancelBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Hide the modal when clicking outside the modal content
    window.addEventListener('click', (event) => {
        if (event.target === modal || event.target === editModal) {
            modal.style.display = 'none';
            editModal.style.display = 'none';
        }
    });

    function openEditModal(id) {
        fetch(`my-update.php?edit_id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    document.getElementById('edit-title').value = data.title;
                    document.getElementById('edit-content').value = data.content;
                    document.getElementById('edit-announcement-id').value = data.id;

                    const currentAttachment = document.getElementById('current-attachment');
                    if (data.attachment) {
                        currentAttachment.innerHTML = `<a href="../uploads/${data.attachment}" download>${data.attachment}</a>`;
                    } else {
                        currentAttachment.textContent = 'No attachment';
                    }

                    // Attach the event listener to the delete button
                    document.getElementById('delete-attachment-btn').addEventListener('click', function() {
                    document.getElementById('delete-attachment-btn').dataset.deleteAttachment = true;
                    document.getElementById('deleteAttachmentField').value = "true"; // Mark the attachment for deletion
                    document.getElementById('current-attachment').innerHTML = 'No attachment';
                });

                    document.getElementById('edit-announcement-modal').style.display = 'block';
                }
            })
            .catch(error => console.error('Error fetching announcement data:', error));
    }

    // Close modal
    function closeEditModal() {
        document.getElementById('edit-announcement-modal').style.display = 'none';
    }

    // Delete confirmation using SweetAlert
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `my-update.php?delete_id=${id}`;
            }
        });
    }
</script>

</body>
</html>
