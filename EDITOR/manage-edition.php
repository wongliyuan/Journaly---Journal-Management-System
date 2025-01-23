<?php
include '../config.php';

// Default values
$rowsPerPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $rowsPerPage;

// Fetch limited editions for the current page
try {
    $stmt = $pdo->prepare("
        SELECT 
            editions.id, 
            editions.title, 
            editions.publication_date, 
            editions.status, 
            users.username AS editor_name
        FROM editions
        INNER JOIN users ON editions.user_id = users.id
        ORDER BY editions.publication_date DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $rowsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $editions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching editions: " . $e->getMessage());
}

// Fetch total number of rows
$totalQuery = "SELECT COUNT(*) AS total FROM editions";
$totalStmt = $pdo->query($totalQuery);
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Fetch status counts for the dashboard
$draftEditionCount = 0;
$publishedEditionCount = 0;
$statQuery = "SELECT status, COUNT(*) AS count FROM editions GROUP BY status";
$statStmt = $pdo->query($statQuery);
$statusCounts = $statStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($statusCounts as $statusCount) {
    if ($statusCount['status'] === 'Draft') {
        $draftEditionCount += $statusCount['count'];
    } elseif ($statusCount['status'] === 'Published') {
        $publishedEditionCount += $statusCount['count'];
    }
}

// Fetch accepted submissions
try {
    $acceptedStmt = $pdo->prepare("
        SELECT id, title 
        FROM submissions 
        WHERE status = 'Accepted'
    ");
    $acceptedStmt->execute();
    $acceptedSubmissions = $acceptedStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching submissions: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $publicationDate = $_POST['publication_date'];
    $coverImage = $_FILES['cover_image'];
    $selectedSubmissions = $_POST['submissions']; // Array of selected submission IDs

    // Process the cover image, save the edition, and update submissions
    foreach ($selectedSubmissions as $submissionId) {
        $stmt = $pdo->prepare("UPDATE submissions SET status = 'Published' WHERE id = ?");
        $stmt->execute([$submissionId]);
    }

    // Save the edition record here
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - Manage Editions</title>
    <link rel="stylesheet" href="Estyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <!-- Add in the <head> section -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">

    <div class="dashboard-container">

        <div class="header-container">
            <h2>Manage Editions</h2>
            <button class="create-edition-btn" onclick="openCreateEditionModal()">
                <i class='bx bx-plus' style="margin-right: 5px; font-size: 20px;"></i>Create Edition
            </button>
        </div>

        <!-- Edition List -->
            <div class="stats-grid">
                    <div class="stat-box yellow" onclick="filterData('Draft')">
                        <div class="stat-header">
                            <h3><?= $draftEditionCount ?></h3>
                            <i class="bx bx-revision icon"></i>
                        </div>
                        <p>Draft Edition</p>
                    </div>
                    <div class="stat-box green" onclick="filterData('Published')">
                        <div class="stat-header">
                            <h3><?= $publishedEditionCount ?></h3>
                            <i class="bx bxs-check-square icon"></i>
                        </div>
                        <p>Published Edition</p>
                    </div>
            </div>
        <p onclick="filterData('')" style="font-size: 15px; color: blue; text-decoration: underline; text-align: right; cursor: pointer;">Clear Filter</p>

            <table class="table-container">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Publication Date</th>
                    <th>Status</th>
                    <th>Editor</th> <!-- New column for editor name -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($editions)): ?>
                    <?php foreach ($editions as $edition): ?>
                        <tr data-status="<?= htmlspecialchars($edition['status']) ?>">
                            <td><?= htmlspecialchars($edition['title']) ?></td>
                            <td><?= htmlspecialchars($edition['publication_date']) ?></td>
                            <td><?= htmlspecialchars($edition['status']) ?></td>
                            <td><?= htmlspecialchars($edition['editor_name']) ?></td>
                            <td>
                                <div class="action-icons">
                                    <i class="bx bx-edit-alt edit" onclick="editEdition(<?= $edition['id'] ?>)" title="Edit"></i>
                                    <i class="bx bx-trash delete" onclick="deleteEdition(<?= $edition['id'] ?>)" title="Delete"></i>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No editions available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            </table>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?status=<?= $selectedStatus ?>&page=<?= $page - 1 ?>" class="prev">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?status=<?= $selectedStatus ?>&page=<?= $i ?>" class="page-number <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?status=<?= $selectedStatus ?>&page=<?= $page + 1 ?>" class="next">Next</a>
                <?php endif; ?>
            </div>
</div>
</main>

    <!-- Create Edition Modal -->
    <div id="createEditionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Create New Edition</h3>

            <form id="createEditionForm" onsubmit="submitNewEdition(event)">
            <span class="close" onclick="closeCreateEditionModal()">&times;</span>

                <label for="editionTitle">Title:</label>
                <input type="text" id="editionTitle" name="title" required />

                <label for="publicationDate">Publication Date:</label>
                <input type="date" id="publicationDate" name="publication_date" required />

                <label for="coverImage">Cover Image:</label>
                <input type="file" id="coverImage" name="cover_image" accept="image/*" required />

                <label for="acceptedSubmissions">Select Submissions:</label>
                    <select id="acceptedSubmissions" name="submissions[]" multiple="multiple" style="width: 100%;">
                        <?php if (!empty($acceptedSubmissions)): ?>
                            <?php foreach ($acceptedSubmissions as $submission): ?>
                                <option value="<?= htmlspecialchars($submission['id']) ?>">
                                    <?= htmlspecialchars($submission['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option disabled>No submissions available for selection</option>
                        <?php endif; ?>
                    </select>

                <div class="button-container">
                    <button type="submit">Publish</button>
                    <button type="button">Save As Draft</button>
                </div>
            </form>
        </div>
    </div>

<script>
    function filterData(status) {
        const rows = document.querySelectorAll("tbody tr");
        rows.forEach(row => {
            if (status === "" || row.getAttribute("data-status") === status) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });

        // Reset pagination after filtering
        resetPagination();
    }

    // Open Create Edition Modal
    function openCreateEditionModal() {
        // Reset the form before opening the modal
        document.getElementById("createEditionForm").reset();

        // Clear the select2 selections
        $('#acceptedSubmissions').val(null).trigger('change');

        // Show the modal
        document.getElementById("createEditionModal").style.display = "flex";
    }

    // Close Create Edition Modal
    function closeCreateEditionModal() {
        // Reset the form
        document.getElementById("createEditionForm").reset();

        // Clear the select2 selections
        $('#acceptedSubmissions').val(null).trigger('change');

        // Hide the modal
        document.getElementById("createEditionModal").style.display = "none";
    }


    document.addEventListener("DOMContentLoaded", () => {
        // Initialize Select2 for multi-selection
        $('#acceptedSubmissions').select2({
            placeholder: "Choose submissions...",
            allowClear: true
        });
    });

    // Submit New Edition
    function submitNewEdition(event) {
        event.preventDefault();
        const formData = new FormData(document.getElementById("createEditionForm"));

        fetch('publish-edition.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  Swal.fire("Success", "Edition created successfully!", "success").then(() => {
                      location.reload();
                  });
              } else {
                  Swal.fire("Error", data.message || "Failed to create edition.", "error");
              }
          })
          .catch(error => {
              Swal.fire("Error", "An error occurred. Please try again later.", "error");
          });
    }

    // Edit Edition
    function editEdition(id) {
        window.location.href = `edit-edition.php?id=${id}`;
    }

    // Delete Edition
    function deleteEdition(id) {
        Swal.fire({
            title: "Are you sure?",
            text: "This action will permanently delete the edition.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('delete-edition.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          Swal.fire("Deleted!", "The edition has been deleted.", "success").then(() => {
                              location.reload();
                          });
                      } else {
                          Swal.fire("Error", data.message || "Failed to delete edition.", "error");
                      }
                  })
                  .catch(error => {
                      Swal.fire("Error", "An error occurred. Please try again later.", "error");
                  });
            }
        });
    }
</script>

</body>
</html>