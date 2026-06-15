<?php
include 'session_logins.php';
include 'header.php';
?>
<?php
$page_url_canonical = "https://www.fishgelatine.co.za/v2/forgot-password";
$title_og = 'Forgot Password | Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/forgot-password";

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

<title>Forgot Password - Sir Francis</title>
<?php
include 'page_menues.php';
?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 400px;">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Forgot Your Password?</h2>
            <p class="card-text text-center mb-4">Enter your email address below to receive a password reset link.</p>
            <form id="forgot-password-form" action="forgot-password.inc.php" method="post">
                <div class="form-group">
                    <input name="user-email" class="form-control" placeholder="Email" type="email" required>
                    <div class="error-message text-danger" id="forgot-password-email-error"></div>
                    <div class="success-message text-success"></div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-dark btn-md mt-3">
                        <span>Send Reset Link</span>
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
            function clearErrors(formId) {
                $(`${formId} .error-message`).text('');
                $(`${formId} .success-message`).text('');
            }

            function displayErrors(errors, formId) {
                for (const [field, message] of Object.entries(errors)) {
                    $(`#${formId}-${field}-error`).text(message);
                }
            }

            $('#forgot-password-form').submit(function (e) {
                e.preventDefault();
                clearErrors('#forgot-password-form');
                $.ajax({
                    type: 'POST',
                    url: 'forgot-password.inc.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            $('#forgot-password-form')[0].reset();
                            $('.success-message').text(response.message);
                        } else {
                            displayErrors(response.errors, 'forgot-password');
                        }
                    },
                    error: function () {
                        $('#forgot-password-email-error').text('Password reset could not be requested. Please try again.');
                    },
                });
            });
        });
    </script>

<?php
include 'footer.php';
?>
