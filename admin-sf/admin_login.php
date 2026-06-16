<?php
// Start or resume the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function redirectLoggedInUser() {
    // Check if the admin_id exists
    if (isset($_SESSION['admin_id'])) {
        // Check if the redirect parameter is set
        if (isset($_GET['redirect'])) {
            $redirect = trim((string) $_GET['redirect']);
            if ($redirect === '' || preg_match('#^https?://#i', $redirect) || strpos($redirect, '..') !== false) {
                $redirect = 'index';
            }
            header("Location: $redirect");
            exit();
        }
        header("Location: index");
        exit();
    }
}

// Call the redirectLoggedInUser function
redirectLoggedInUser();

// Check if the form is submitted
if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST") {

    include 'dbh.inc.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // SQL query to retrieve the hashed password from the database using a prepared statement
    $sql = "SELECT id, username, email, password_hash FROM admin_users WHERE LOWER(username) = LOWER(?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // If the prepared statement failed
        header("Location: admin_login?error=stmt_failed");
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        // Verify the password using password_verify()
        $stmt->bind_result($admin_id, $dbUsername, $dbEmail, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Login successful, set the session variable
            $_SESSION['admin_id'] = $admin_id;

            // Redirect to the admin dashboard
            redirectLoggedInUser();
        } else {
            // Invalid password
            header("Location: admin_login?error=invalid_password");
            exit();
        }
    } else {
        // User not found
        header("Location: admin_login?error=user_not_found");
        exit();
    }

    // Close the prepared statement and the database connection
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sir Francis Admin Login</title>
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png" />
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="admin-theme.css" />
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>
<body class="admin-sf">

<style>
    body.admin-sf {
        align-items: center;
        background: var(--sf-cream);
        display: flex;
        justify-content: center;
        min-height: 100vh;
        padding: 24px;
    }

    .login-container {
        background: #fff;
        border: 1px solid var(--sf-border);
        border-radius: 8px;
        box-shadow: 0 16px 40px rgba(23, 34, 53, .12);
        display: grid;
        gap: 18px;
        max-width: 390px;
        width: 100%;
        margin: 0 auto;
        padding: 30px;
    }

    .login-logo {
        display: block;
        height: auto;
        margin: 0 auto 6px;
        max-width: 210px;
        width: 100%;
    }

    .login-container h2 {
        color: var(--sf-navy);
        font-size: 24px;
        margin: 0;
        text-align: center;
    }

    .login-container .error {
        background: #fff0f0;
        border-radius: 6px;
        color: #9b111e;
        margin: 0;
        padding: 10px 12px;
    }

    .password-toggle {
        color: var(--sf-navy);
        cursor: pointer;
        position: absolute;
        right: 12px;
        top: 73%;
        transform: translateY(-50%);
    }
</style>

<div class="login-container">
    <img src="../assets/img/logo/logo.png" alt="Sir Francis" class="login-logo">
    <h2>Admin Login</h2>

    <?php
    if (isset($_GET['error'])) {
        $error = $_GET['error'];

        switch ($error) {
            case 'stmt_failed':
                echo '<p class="error">There was a problem with the login system. Please try again later.</p>';
                break;
            case 'invalid_password':
                echo '<p class="error">Invalid password. Please try again.</p>';
                break;
            case 'user_not_found':
                echo '<p class="error">User not found. Please check your admin username.</p>';
                break;
            case 'invalid_request':
                echo '<p class="error">Invalid request method.</p>';
                break;
            default:
                echo '<p class="error">An unknown error occurred.</p>';
        }
    }
    ?>

    <form action="" method="post">
        <div class="mb-3">
            <label for="username" class="form-label">Username:</label>
            <input type="text" id="username" name="username" class="form-control" autocomplete="username" required>
        </div>

        <div class="mb-3 position-relative">
            <label for="password" class="form-label">Password:</label>
            <input type="password" id="password" name="password" class="form-control" required>
            
            <!-- Eye icon for password toggle -->
            <i class="fas fa-eye-slash password-toggle" id="togglePassword"></i>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Login</button>
        <a href="admin_forgot_password" class="d-block mt-3">Forgot admin password?</a>
    </form>
</div>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
    // JavaScript to toggle password visibility and change eye icon
    var passwordInput = document.getElementById('password');
    var togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', function() {
        var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Change eye icon based on password visibility
        togglePassword.classList.toggle('fa-eye-slash', type === 'password');
        togglePassword.classList.toggle('fa-eye', type === 'text');
    });
</script>

</body>
</html>
