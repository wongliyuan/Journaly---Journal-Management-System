<?php
include '../config.php';

try {
    // Establish database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if the ID is provided
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the current publication data
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $publication = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the publication exists
    if (!$publication) {
        die("Submission not found.");
    }
} else {
    die("Invalid request.");
}

// Update the publication if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $journalTitle = filter_input(INPUT_POST, 'journalTitle', FILTER_SANITIZE_STRING);
    $keywords = filter_input(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $manuscriptType = filter_input(INPUT_POST, 'manuscriptType', FILTER_SANITIZE_STRING);
    $abstract = filter_input(INPUT_POST, 'abstract', FILTER_SANITIZE_STRING);
    $leadAuthorName = filter_input(INPUT_POST, 'leadAuthorName', FILTER_SANITIZE_STRING);
    $leadAuthorEmail = filter_input(INPUT_POST, 'leadAuthorEmail', FILTER_SANITIZE_EMAIL);
    $leadAuthorAffiliation = filter_input(INPUT_POST, 'leadAuthorAffiliation', FILTER_SANITIZE_STRING);
    $coAuthors = filter_input(INPUT_POST, 'coAuthors', FILTER_SANITIZE_STRING);

    // Handle file uploads
    $manuscriptFile = $_FILES['manuscriptFile'];
    $supplementaryMaterials = $_FILES['supplementaryMaterials'];

    // Upload the manuscript file
    $manuscriptFilePath = $publication['manuscript_file']; // Default to current file
    if ($manuscriptFile['error'] === UPLOAD_ERR_OK) {
        $manuscriptFileName = basename($manuscriptFile['name']);
        $manuscriptFilePath = '../uploads/' . $manuscriptFileName;
        move_uploaded_file($manuscriptFile['tmp_name'], $manuscriptFilePath);
    }

    // Upload supplementary materials (optional)
    $supplementaryMaterialsPath = $publication['supplementary_materials']; // Default to current file
    if ($supplementaryMaterials['error'] === UPLOAD_ERR_OK) {
        $supplementaryFileName = basename($supplementaryMaterials['name']);
        $supplementaryMaterialsPath = '../uploads/' . $supplementaryFileName;
        move_uploaded_file($supplementaryMaterials['tmp_name'], $supplementaryMaterialsPath);
    }

    // Update the database
    $sql = "UPDATE submissions SET 
                title = :title, 
                keywords = :keywords, 
                category = :category, 
                manuscript_type = :manuscript_type, 
                abstract = :abstract, 
                manuscript_file = :manuscript_file, 
                supplementary_materials = :supplementary_materials, 
                lead_author_name = :lead_author_name, 
                lead_author_email = :lead_author_email, 
                lead_author_affiliation = :lead_author_affiliation, 
                co_authors = :co_authors, 
                updated_at = NOW() 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([
        ':title' => $journalTitle,
        ':keywords' => $keywords,
        ':category' => $category,
        ':manuscript_type' => $manuscriptType,
        ':abstract' => $abstract,
        ':manuscript_file' => $manuscriptFilePath,
        ':supplementary_materials' => $supplementaryMaterialsPath,
        ':lead_author_name' => $leadAuthorName,
        ':lead_author_email' => $leadAuthorEmail,
        ':lead_author_affiliation' => $leadAuthorAffiliation,
        ':co_authors' => $coAuthors,
        ':id' => $id
    ])) {
        // Redirect to the same page with a success flag
        header("Location: edit-submission.php?id=$id&success=1");
        exit();
    } else {
        // Redirect with an error flag
        header("Location: edit-submission.php?id=$id&success=0");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - Edit Submission</title>
    <link rel="stylesheet" href="Ustyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">
    <h2>Edit Submission</h2>

    <form class="journal-form" method="POST" enctype="multipart/form-data">
      
      <!-- Article Information Section -->
      <h3>Article Information</h3>
      <div class="bento-grid">
        <div class="form-group">
          <label for="journalTitle">Title</label>
          <input type="text" id="journalTitle" name="journalTitle" value="<?php echo htmlspecialchars($publication['title']); ?>" required />
        </div>
        <div class="form-group">
          <label for="keywords">Keywords (separate with commas)</label>
          <input type="text" id="keywords" name="keywords" value="<?php echo htmlspecialchars($publication['keywords']); ?>" required />
        </div>
        <div class="form-group">
          <label for="category">Category</label>
          <select id="category" name="category" required>
            <option value="" disabled>Select Category</option>
            <option value="AI" <?php echo ($publication['category'] === 'AI') ? 'selected' : ''; ?>>Artificial Intelligence</option>
            <option value="CSN" <?php echo ($publication['category'] === 'CSN') ? 'selected' : ''; ?>>Computer System and Network</option>
            <option value="DS" <?php echo ($publication['category'] === 'DS') ? 'selected' : ''; ?>>Data Science</option>
            <option value="IS" <?php echo ($publication['category'] === 'IS') ? 'selected' : ''; ?>>Information Systems</option>
            <option value="MM" <?php echo ($publication['category'] === 'MM') ? 'selected' : ''; ?>>Multimedia</option>
            <option value="SE" <?php echo ($publication['category'] === 'SE') ? 'selected' : ''; ?>>Software Engineering</option>
          </select>
        </div>
        <div class="form-group">
          <label for="manuscriptType">Manuscript Type</label>
          <select id="manuscriptType" name="manuscriptType" required>
            <option value="" disabled>Select Type</option>
            <option value="original_research" <?php echo ($publication['manuscript_type'] === 'original_research') ? 'selected' : ''; ?>>Original Research</option>
            <option value="review_article" <?php echo ($publication['manuscript_type'] === 'review_article') ? 'selected' : ''; ?>>Review Article</option>
            <option value="case_study" <?php echo ($publication['manuscript_type'] === 'case_study') ? 'selected' : ''; ?>>Case Study</option>
          </select>
        </div>
        <div class="form-group">
          <label for="abstract">Abstract</label>
          <textarea id="abstract" name="abstract" rows="4" required><?php echo htmlspecialchars($publication['abstract']); ?></textarea>
        </div>
      </div>

      <!-- Document Upload Section -->
      <h3>Document Upload</h3>
      <div class="bento-grid">
          <div class="form-group">
            <label for="manuscriptFile">Upload Manuscript</label>
            <div class="file-input">
                <input type="file" id="manuscriptFile" name="manuscriptFile" accept=".pdf, .doc, .docx" />
                <label for="manuscriptFile" class="custom-file-label">
                    <i class='bx bx-upload'></i>
                    <?php echo isset($publication['manuscript_file']) && !empty($publication['manuscript_file']) 
                        ? basename($publication['manuscript_file']) 
                        : 'Click here to upload document'; ?>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="supplementaryMaterials">Upload Supplementary Materials (optional)</label>
            <div class="file-input">
                <input type="file" id="supplementaryMaterials" name="supplementaryMaterials" accept=".pdf, .doc, .docx, .csv, .xlsx" />
                <label for="supplementaryMaterials" class="custom-file-label">
                    <i class='bx bx-upload'></i>
                    <?php echo isset($publication['supplementary_materials']) && !empty($publication['supplementary_materials']) 
                        ? basename($publication['supplementary_materials']) 
                        : 'Click here to upload additional materials'; ?>
                </label>
            </div>
        </div>
      </div>

      <!-- Author Information Section -->
      <h3>Author Information</h3>
      <div class="bento-grid">
        <div class="form-group">
          <label for="leadAuthorName">Lead Author's Full Name</label>
          <input type="text" id="leadAuthorName" name="leadAuthorName" value="<?php echo htmlspecialchars($publication['lead_author_name']); ?>" required />
        </div>
        <div class="form-group">
          <label for="leadAuthorEmail">Lead Author's Email</label>
          <input type="email" id="leadAuthorEmail" name="leadAuthorEmail" value="<?php echo htmlspecialchars($publication['lead_author_email']); ?>" required />
        </div>
        <div class="form-group">
          <label for="leadAuthorAffiliation">Lead Author's Affiliation</label>
          <input type="text" id="leadAuthorAffiliation" name="leadAuthorAffiliation" value="<?php echo htmlspecialchars($publication['lead_author_affiliation']); ?>" required />
        </div>
        <div class="form-group">
          <label for="coAuthors">Co-Authors (Full Name, Email, Affiliation)</label>
          <textarea id="coAuthors" name="coAuthors" rows="3" placeholder="Name, Email, Affiliation; Name, Email, Affiliation"><?php echo htmlspecialchars($publication['co_authors']); ?></textarea>
        </div>
      </div>

      <!-- Submit Button -->
      <button type="submit">Update</button>
    </form>
</main>

    <script>
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

      // Function to check for query parameters
      function getQueryParam(param) {
        let urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
      }

      // Check if the form was successfully submitted
      document.addEventListener('DOMContentLoaded', function() {
          let success = getQueryParam('success');
          
          if (success === '1') {
              Swal.fire({
                  title: "Good job!",
                  text: "Submission updated successfully!",
                  icon: "success"
              });
          } else if (success === '0') {
              Swal.fire({
                  title: "Error!",
                  text: "There was a problem updating your data.",
                  icon: "error"
              });
          }
      });
    </script>

</body>
</html>
