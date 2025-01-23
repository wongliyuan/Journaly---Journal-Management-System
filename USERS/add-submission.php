<?php 
session_start();
include '../config.php'; 

try{
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    $manuscriptFilePath = '';
    if ($manuscriptFile['error'] === UPLOAD_ERR_OK) {
        $manuscriptFileName = basename($manuscriptFile['name']);
        $manuscriptFilePath = '../uploads/' . $manuscriptFileName;
        move_uploaded_file($manuscriptFile['tmp_name'], $manuscriptFilePath);
    }

    // Upload supplementary materials (optional)
    $supplementaryMaterialsPath = '';
    if ($supplementaryMaterials['error'] === UPLOAD_ERR_OK) {
        $supplementaryFileName = basename($supplementaryMaterials['name']);
        $supplementaryMaterialsPath = '../uploads/' . $supplementaryFileName;
        move_uploaded_file($supplementaryMaterials['tmp_name'], $supplementaryMaterialsPath);
    }

    // Get the user ID from the session
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        header("Location: ../GENERAL/homepage.php"); // Redirect to login if user is not logged in
        exit();
    }

    // Insert form data into the database
    $sql = "INSERT INTO submissions (
                title, keywords, category, manuscript_type, abstract, manuscript_file, 
                supplementary_materials, lead_author_name, lead_author_email, 
                lead_author_affiliation, co_authors, user_id
            ) VALUES (
                :title, :keywords, :category, :manuscript_type, :abstract, :manuscript_file, 
                :supplementary_materials, :lead_author_name, :lead_author_email, 
                :lead_author_affiliation, :co_authors, :user_id
            )";

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
        ':user_id' => $userId
    ])) {
        // Redirect to the same page with a success flag
        header("Location: add-submission.php?success=1");
        exit();
    } else {
        // Redirect with an error flag
        header("Location: add-submission.php?success=0");
        exit();
    }
} 
}catch (PDOException $e) {
  die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - New Submission</title>
    <link rel="stylesheet" href="Ustyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content">
    <h2>Create New Submission</h2>
    <form class="journal-form" method="POST" enctype="multipart/form-data">
      
      <!-- Article Information Section -->
      <h3>Article Information</h3>
      <div class="bento-grid">
        <div class="form-group">
          <label for="journalTitle">Title</label>
          <input type="text" id="journalTitle" name="journalTitle" placeholder="Enter the title here..." required />
        </div>
        <div class="form-group">
          <label for="keywords">Keywords (separate with commas)</label>
          <input type="text" id="keywords" name="keywords" placeholder="keyword 1, keyword 2, keyword 3" required />
        </div>
        <div class="form-group">
          <label for="category">Category</label>
          <select id="category" name="category" required>
            <option value="" disabled selected>Select Category</option>
            <option value="AI">Artificial Intelligence</option>
            <option value="CSN">Computer System and Network</option>
            <option value="DS">Data Science</option>
            <option value="IS">Information Systems</option>
            <option value="MM">Multimedia</option>
            <option value="SE">Software Engineering</option>
          </select>
        </div>
        <div class="form-group">
          <label for="manuscriptType">Manuscript Type</label>
          <select id="manuscriptType" name="manuscriptType" required>
            <option value="" disabled selected>Select Type</option>
            <option value="original_research">Original Research</option>
            <option value="review_article">Review Article</option>
            <option value="case_study">Case Study</option>
          </select>
        </div>
        <div class="form-group">
          <label for="abstract">Abstract</label>
          <textarea id="abstract" name="abstract" rows="4" placeholder="Provide a brief summary of the research..." required></textarea>
        </div>
      </div>

      <!-- Document Upload Section -->
      <h3>Document Upload</h3>
      <div class="bento-grid">
        <div class="form-group">
          <label for="manuscriptFile">Upload Manuscript</label>
          <div class="file-input">
            <input type="file" id="manuscriptFile" name="manuscriptFile" accept=".pdf, .doc, .docx" required />
            <label for="manuscriptFile" class="custom-file-label"><i class='bx bx-upload'></i>Click here to upload document</label>
          </div>
        </div>
        <div class="form-group">
          <label for="supplementaryMaterials">Upload Supplementary Materials (optional)</label>
          <div class="file-input">
            <input type="file" id="supplementaryMaterials" name="supplementaryMaterials" accept=".pdf, .doc, .docx, .csv, .xlsx" />
            <label for="supplementaryMaterials" class="custom-file-label"><i class='bx bx-upload'></i>Click here to upload additional materials</label>
          </div>
        </div>
      </div>

    <!-- Author Information Section -->
    <h3>Authors Information</h3>
    <div class="bento-grid">
      <div class="form-group">
        <label for="leadAuthorName">Author's Full Name</label>
        <input type="text" id="leadAuthorName" name="leadAuthorName" placeholder="Full Name of Author" required />
      </div>
      <div class="form-group">
        <label for="leadAuthorEmail">Author's Email</label>
        <input type="email" id="leadAuthorEmail" name="leadAuthorEmail" placeholder="author@example.com" required />
      </div>
      <div class="form-group">
        <label for="leadAuthorAffiliation">Author's Affiliation</label>
        <input type="text" id="leadAuthorAffiliation" name="leadAuthorAffiliation" placeholder="University/Organization" required />
      </div>
      <div class="form-group">
        <label for="coAuthors">Co-Authors (Full Name, Email, Affiliation)</label>
        <textarea id="coAuthors" name="coAuthors" rows="3" placeholder="Name, Email, Affiliation; Name, Email, Affiliation"></textarea>
      </div>
      
    </div>

      <!-- Submit Button -->
       <div class="button-container">
        <button type="submit">Submit</button>
        <button type="button" onclick="window.location.href='my-submission.php'";>Cancel</button>
       </div>

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
            label.textContent = fileName; // Update the label text with the file name
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
                text: "Submission successful!",
                icon: "success"
            });
        } else if (success === '0') {
            Swal.fire({
                title: "Error!",
                text: "There was a problem submitting your data.",
                icon: "error"
            });
        }
    });
    </script>

</body>
</html>
