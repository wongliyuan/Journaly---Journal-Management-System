<?php

include '../config.php';
session_start();

try {
    // Query to count the total number of assignments for each status
    $query = "SELECT assignment_status, COUNT(*) AS count 
              FROM editor_assignments 
              GROUP BY assignment_status";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    // Initialize counts
    $pendingCount = $acceptedCount = $rejectedCount = 0;

    // Fetch counts by status
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statusCounts as $statusCount) {
        switch ($statusCount['assignment_status']) {
            case 'Pending':
                $pendingCount = $statusCount['count'];
                break;
            case 'Accepted':
                $acceptedCount = $statusCount['count'];
                break;
            case 'Rejected':
                $rejectedCount = $statusCount['count'];
                break;
        }
    }
} catch (PDOException $e) {
    die("Error retrieving editor assignments: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Journaly - My Workspace</title>
  <link rel="stylesheet" href="Astyle.css">
  <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">

  <div class="dashboard-container">
    <div class="header-container">
        <h2>My Workspace</h2>
        <button class="manage-edition-btn" onclick="window.location.href='manage-edition.php'">
            <i class='bx bx-folder-plus' style="margin-right: 5px; font-size: 20px;"></i>Manage Edition
        </button>
    </div>

    <!-- Stats boxes to show total counts -->
    <div class="stats-grid">
        <div class="stat-box orange">
            <div class="stat-header">
                <h3><?= $pendingCount ?></h3>
                <i class="bx bxs-error-circle icon"></i>
            </div>
            <p>Pending Assignments</p>
        </div>
        <div class="stat-box green">
            <div class="stat-header">
                <h3><?= $acceptedCount ?></h3>
                <i class="bx bxs-check-square icon"></i>
            </div>
            <p>Accepted Assignments</p>
        </div>
        <div class="stat-box red">
            <div class="stat-header">
                <h3><?= $rejectedCount ?></h3>
                <i class="bx bxs-x-square icon"></i>
            </div>
            <p>Rejected Assignments</p>
        </div>
    </div>

    <table id="submissions-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Category</th>
          <th>Manuscript</th>
          <th>Submission Date</th>
          <th>Assign Editor</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

    <!-- Pagination Container -->
    <div id="pagination-container" class="pagination"></div>

  </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const submissionsTable = document.querySelector("#submissions-table tbody");
  const paginationContainer = document.querySelector("#pagination-container");
  let currentPage = 1;

  function fetchSubmissions(page = 1) {
    fetch(`fetch-submission.php?page=${page}`)
      .then(response => response.json())
      .then(data => {
        submissionsTable.innerHTML = data.submissions.map(submission => `
          <tr>
            <td>${submission.id}</td>
            <td>${submission.title}</td>
            <td>${submission.category}</td>
            <td><a href="${submission.manuscript_url}" target="_blank">View</a></td>
            <td>${submission.submission_date}</td>
            <td>
              <select class="editor-select" data-submission-id="${submission.id}">
                <option value="">Select Editor</option>
              </select>
              <button class="assign-btn" data-submission-id="${submission.id}" disabled>Assign</button>
            </td>
          </tr>
        `).join('');
        populateEditors();
        renderPagination(data.totalPages, page);
      });
  }

  function renderPagination(totalPages, currentPage) {
    paginationContainer.innerHTML = '';

    let paginationHTML = `
      <a href="#" class="prev ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}">Previous</a>
    `;

    for (let i = 1; i <= totalPages; i++) {
      paginationHTML += `
        <a href="#" class="${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</a>
      `;
    }

    paginationHTML += `
      <a href="#" class="next ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}">Next</a>
    `;

    paginationContainer.innerHTML = paginationHTML;

    // Add event listeners to pagination links
    document.querySelectorAll('.pagination a').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        const page = parseInt(this.getAttribute('data-page'));
        if (page >= 1 && page <= totalPages) {
          fetchSubmissions(page);
        }
      });
    });
  }

  function populateEditors() {
    fetch('fetch-editor.php')
      .then(response => response.json())
      .then(data => {
        const editorOptions = data.map(editor => `
          <option value="${editor.id}">
            ${editor.username} (${editor.expertise_area}, ${editor.assigned_submissions} assignments)
          </option>
        `).join('');

        document.querySelectorAll(".editor-select").forEach(select => {
          select.innerHTML += editorOptions;

          // Enable assign button when an editor is selected
          select.addEventListener("change", function () {
            const assignBtn = this.closest("td").querySelector(".assign-btn");
            assignBtn.disabled = !this.value;
          });
        });
      });
  }

  // Initial fetch
  fetchSubmissions(currentPage);

  // Handle assignment (unchanged)
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("assign-btn")) {
      const submissionId = e.target.dataset.submissionId;
      const editorId = e.target.closest("td").querySelector(".editor-select").value;

      fetch('assign-editor.php', {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ submission_id: submissionId, editor_id: editorId })
      })
      .then(response => response.json())
      .then(data => {
        Swal.fire({
          icon: "success",
          title: "Success",
          text: data.message,
        }).then(() => fetchSubmissions(currentPage));
      });
    }
  });
});
</script>

</body>
</html>
