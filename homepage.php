<?php
session_start();
include '../config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'login' && isset($_POST['email'], $_POST['password'])) {
        // Login logic
        $email = $_POST['email'];
        $password = $_POST['password'];
        $alertMessage = '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'User':
                        header("Location: ../USERS/my-submission.php");
                        break;
                    case 'Editor':
                        header("Location: ../EDITOR/my-dashboard.php");
                        break;
                    case 'Admin':
                        header("Location: ../ADMIN/my-dashboard.php");
                        break;
                    default:
                        echo "<script>Swal.fire('Error', 'Role not defined for this user.', 'error');</script>";
                        break;
                }
                exit();
            } else {
                echo $alertMessage = "<script>Swal.fire('Error', 'Incorrect password.', 'error');</script>";
            }
        } else {
            echo $alertMessage = "<script>Swal.fire('Error', 'No user found with that email.', 'error');</script>";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'signup' && isset($_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
        // Signup logic
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $alertMessage = '';

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please provide a valid email address.',
                });
            </script>";
        } elseif ($password !== $confirm_password) {
            $alertMessage = "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Passwords do not match!',
                });
            </script>";
        } else {
            $check_stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $alertMessage = "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Email is already registered!',
                    });
                </script>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, 'User')");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);

                if ($stmt->execute()) {
                    $alertMessage = "<script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Account created successfully. You can now log in!',
                        }).then(() => {
                            window.location.href = '../GENERAL/homepage.php';
                        });
                    </script>";
                } else {
                    $alertMessage = "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Database Error',
                            text: 'Could not create account.',
                        });
                    </script>";
                }
            }
        }

        echo $alertMessage;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly</title>
    <link rel="stylesheet" href="Gstyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
  </head>
  <body>
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="home">
    <section class="hero">
      <div class="hero-content">
        <h1>Manage, Review, and Publish Research with Ease</h1>
        <p>Streamline submissions, enhance collaboration, and accelerate publication with Journaly.</p>
        <a class="cta-button" id="form-open">Get Started</a>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features">
    <h2>Key Features</h2>
    <div class="feature">
        <i class="bx bxs-paper-plane"></i>
        <h3>Easy Submission</h3>
        <p>Streamline the process of submitting research papers with an intuitive interface.</p>
    </div>
    <div class="feature">
        <i class="bx bxs-comment-detail"></i>
        <h3>Peer Review</h3>
        <p>Engage in collaborative reviews with constructive feedback to enhance submissions.</p>
    </div>
    <div class="feature">
        <i class="bx bxs-notepad"></i>
        <h3>Manage Issues</h3>
        <p>Easily compile and publish journal issues with efficient tools and workflows.</p>
    </div>
    <div class="feature">
        <i class='bx bxs-bell-ring'></i>
        <h3>Automated Notifications</h3>
        <p>Stay informed with automatic updates on submissions and review progress.</p>
    </div>
    <div class="feature">
        <i class='bx bxs-chat'></i>
        <h3>Chat Function</h3>
        <p>Facilitate seamless communication between editors, reviewers, and authors.</p>
    </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
    <h2>How It Works</h2>
    <div class="steps">
        <!-- Step 1 -->
        <div class="step">
        <img src="images/signup.svg" alt="Sign Up" class="step-img" />
        <div class="step-content">
            <h3>Step 1: Sign Up</h3>
            <p>Create an account to start managing your journal or reviewing submissions.</p>
        </div>
        </div>

        <!-- Step 2 -->
        <div class="step">
        <!-- <img src="images/review.svg" alt="Submit or Review" class="step-img" /> -->
        <div class="step-content">
            <h3>Step 2: Submit or Review</h3>
            <p>Submit your papers or join as a peer reviewer to evaluate submissions.</p>
        </div>
        <img src="images/review.svg" alt="Submit or Review" class="step-img" />
        </div>

        <!-- Step 3 -->
        <div class="step">
        <img src="images/publish.svg" alt="Publish and Share" class="step-img" />
        <div class="step-content">
            <h3>Step 3: Publish and Share</h3>
            <p>Manage accepted submissions, organize issues, and publish your research.</p>
        </div>
        </div>
    </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; <span id="year"></span> Journaly. All Rights Reserved.</p>
    </footer>

    <div class="form_container">
        <i class='bx bx-x form_close'></i>
        <!-- Login From -->
        <div class="form login_form">
            <form action="" method="POST">
                <input type="hidden" name="action" value="login">
                <h2>Login</h2>
                <div class="input_box">
                    <input type="email" name="email" placeholder="Enter your email" required />
                    <i class='bx bx-envelope email'></i>
                </div>
                <div class="input_box">
                    <input type="password" name="password" placeholder="Enter your password" required />
                    <i class='bx bx-lock password'></i>
                    <i class='bx bx-hide pw_hide'></i>
                </div>
                <div class="option_field">
                    <!-- <span class="checkbox">
                        <input type="checkbox" id="check" />
                        <label for="check">Remember me</label>
                    </span> -->
                    <a href="forgot-password.php" class="forgot_pw">Forgot password?</a>
                </div>
                <button class="button">Login Now</button>
                <div class="login_signup">Don't have an account? <a href="#" id="signup">Signup</a></div>
            </form>
        </div>
        <!-- Signup From -->
        <div class="form signup_form">
            <form action="" method="POST">
                <input type="hidden" name="action" value="signup">
                <h2>Sign Up</h2>
                <div class="input_box">
                    <input type="email" name="email" placeholder="Enter your email" required />
                    <i class='bx bx-envelope email'></i>
                </div>
                <div class="input_box">
                    <input type="password" name="password" placeholder="Create password" required />
                    <i class='bx bx-lock password'></i>
                    <i class='bx bx-hide pw_hide'></i>
                </div>
                <div class="input_box">
                    <input type="password" name="confirm_password" placeholder="Confirm password" required />
                    <i class='bx bx-lock password'></i>
                    <i class='bx bx-hide pw_hide'></i>
                </div>
                <button class="button">Signup Now</button>
                <div class="login_signup">Already have an account? <a href="#" id="login">Login</a></div>
            </form>
        </div>
      </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php
    // Include SweetAlert script if any alert message is set
    if (isset($alertMessage)) {
        echo $alertMessage;
    }
    ?>

<script>
    const formOpenBtns = document.querySelectorAll("#form-open"), // Select all elements with ID "form-open"
        home = document.querySelector(".home"),
        formContainer = document.querySelector(".form_container"),
        formCloseBtn = document.querySelector(".form_close"),
        signupBtn = document.querySelector("#signup"),
        loginBtn = document.querySelector("#login"),
        pwShowHide = document.querySelectorAll(".pw_hide");

    formOpenBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            home.classList.add("show");
        });
    });

    formCloseBtn.addEventListener("click", () => home.classList.remove("show"));

    pwShowHide.forEach((icon) => {
        icon.addEventListener("click", () => {
            let getPwInput = icon.parentElement.querySelector("input");
            if (getPwInput.type === "password") {
                getPwInput.type = "text";
                icon.classList.replace("uil-eye-slash", "uil-eye");
            } else {
                getPwInput.type = "password";
                icon.classList.replace("uil-eye", "uil-eye-slash");
            }
        });
    });

    signupBtn.addEventListener("click", (e) => {
        formContainer.classList.add("active");
    });
    loginBtn.addEventListener("click", (e) => {
        e.preventDefault();
        formContainer.classList.remove("active");
    });

    document.getElementById("year").textContent = new Date().getFullYear();
</script>

</body>
</html>
