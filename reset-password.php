<?php

$token = $_GET['token'] ?? '';

// Check if token is empty or not provided
if (empty($token)) {
    // Redirect user to the forgot password page
    header("Location: forgot-password");
    exit();
}

// Include your database connection file
include_once "dbh.inc.php"; // Adjust the filename as needed

// Check the validity and expiration of the token
$sql = "SELECT id, user_id, token, expiration FROM password_resets WHERE token = ? AND expiration > UNIX_TIMESTAMP()";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) <= 0) {
    // Invalid or expired token
    // Redirect user to the forgot password page
    header("Location: forgot-password");
    exit();
}

?>


<?php
include 'session_logins.php';
include 'header.php';
?>
<?php
$page_url_canonical = "https://www.fishgelatine.co.za/v2/reset-password";
$title_og = 'Reset your Password | Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/reset-password";

?>

<!-- Canonical URL to Avoid Duplicate Content Issues -->
<link rel="canonical" href="<?=$page_url_canonical?>">

<!-- Meta Description Tag -->
<meta name="description" content="<?=$description_meta?>">

<!-- Open Graph Meta Tags for Facebook, Twitter, etc. -->
<meta property="og:title" content="<?=$title_og?>">
<meta property="og:description" content="<?=$description_og?>">
<meta property="og:image" content="<?=$image_url_og?>">
<meta property="og:url" content="<?=$page_url_og?>">
<meta property="og:type" content="website">

<title>Reset your Password - Sir Francis</title>
<?php
include 'page_menues.php';
?>


<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 400px;">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Reset Your Password</h2>
            <p class="card-text text-center mb-4">Enter your new password below.</p>
            <div class="alert alert-success d-none" id="reset-success-message"></div>
            <div class="alert alert-danger d-none" id="reset-general-error"></div>

            <form id="reset-password-form" action="reset-password.inc.php" method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <input type="password" name="user-password" class="form-control" placeholder="New Password" required>
                    <div class="error-message text-danger" id="reset-password-error"></div>
                </div>

                <div class="form-group">
                    <input type="password" name="user-confirm-password" class="form-control" placeholder="Confirm Password" required>
                    <div class="error-message text-danger" id="reset-confirm-password-error"></div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-dark btn-md mt-3">
                        <span>Reset Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <script>
        $(document).ready(function () {
            function clearErrors() {
                $('.error-message').text('');
                $('#reset-general-error, #reset-success-message').addClass('d-none').text('');
            }

            function displayErrors(errors) {
                for (const [field, message] of Object.entries(errors)) {
                    $(`#reset-${field}-error`).text(message);
                }
            }

            $('#reset-password-form').submit(function (e) {
                e.preventDefault();
                clearErrors();
                $.ajax({
                    type: 'POST',
                    url: 'reset-password.inc.php',
                    data: $(this).serialize(),
                    dataType: 'json', // Expect JSON response from server
                    success: function (response) {
                        if (response.success) {
                            $('#reset-success-message').removeClass('d-none').text(response.message || 'Password reset successfully.');
                            setTimeout(function () {
                                window.location.href = response.redirect_url || 'login';
                            }, 900);
                        } else {
                            if (response.errors) {
                                displayErrors(response.errors);
                            }
                            $('#reset-general-error').removeClass('d-none').text(response.message || 'Please check the highlighted fields and try again.');
                        }
                    },
                    error: function (xhr, textStatus, errorThrown) {
                        $('#reset-general-error').removeClass('d-none').text('Password reset could not be completed. Please try again or request a new reset link.');
                    },
                });
            });
        });
    </script>


<?php
include 'footer.php';
?>
