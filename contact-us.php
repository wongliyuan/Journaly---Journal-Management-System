<?php
session_start();
include '../config.php';

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
                        header("Location: ../EDITOR/dashboard.php");
                        break;
                    case 'Admin':
                        header("Location: ../ADMIN/manage-user.php");
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
                            window.location.href = 'login.php';
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="Gstyle.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
</head>
<body>

    <?php include 'navbar.php'; ?>

<section class="home">
    <h4 style="color: #777; text-align: center; font-size: 18px; margin-top: 10px;">Kindly drop your feedback or complaint below the form. We will get back to you as soon as possible.</h4>

    <section class="contact">
        <div class="container">
        <img src="images/contact.svg" alt="contact">
            <form id="contact-form" action="https://formspree.io/f/xqkrvyov" method="POST">
                <div class="input-box">
                    <div class="input-field field">
                        <input type="text" name="Full Name" required placeholder="Enter your Name..." id="name" class="item" autocomplete="off">
                    </div>
                    <div class="input-field field">
                        <input type="text" name="Email Address" required placeholder="Enter you EmailAddress..." id="email" class="item" autocomplete="off">
                    </div>
                </div>

                <div class="input-box">
                    <div class="input-field field">
                        <input type="text" name="Phone Number" required placeholder="Enter your PhoneNumber..." id="phone" class="item" autocomplete="off">
                    </div>
                    <div class="input-field field">
                        <input type="text" name="Subject" required placeholder="Enter Subject..." id="subject" class="item" autocomplete="off">
                    </div>
                </div>

                <div class="textarea-field field">
                    <textarea name="Message" required id="message" cols="30" rows="10" placeholder="Write your Message here..." class="item" autocomplete="off"></textarea>
                </div>

                <button type="submit">
                    Send Message
                </button>
            </form>
        </div>
    </section>

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
    document.getElementById("contact-form").addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent form submission to validate first

        // Get form values
        const name = document.getElementById("name").value;
        const email = document.getElementById("email").value;
        const phone = document.getElementById("phone").value;
        const subject = document.getElementById("subject").value;
        const message = document.getElementById("message").value;

        // Check if any field is empty and show SweetAlert
        if (name === "" || email === "" || phone === "" || subject === "" || message === "") {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'All fields must be filled out!',
        });
        } else {
        // If all fields are filled, submit the form
        this.submit(); // This will submit the form
        }
    });

    const formOpenBtn = document.querySelector("#form-open"),
    home = document.querySelector(".home"),
    formContainer = document.querySelector(".form_container"),
    formCloseBtn = document.querySelector(".form_close"),
    signupBtn = document.querySelector("#signup"),
    loginBtn = document.querySelector("#login"),
    pwShowHide = document.querySelectorAll(".pw_hide");

    formOpenBtn.addEventListener("click", () => home.classList.add("show"));
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
    </script>

</body>
</html>
