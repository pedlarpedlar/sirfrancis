<?php
include 'session_logins.php';
include 'header.php';
?>
<?php
$page_url_canonical = "https://www.fishgelatine.co.za/v2/login";
$title_og = 'Sign In or Register - Dried Fruit, Nuts, Sweets | Sir Francis';
$page_url_og = "https://www.fishgelatine.co.za/v2/login";

function candybirdSafeLoginRedirect($redirectUrl) {
    $redirectUrl = trim((string) $redirectUrl);

    if ($redirectUrl === '' ||
        preg_match('/[\r\n]/', $redirectUrl) ||
        preg_match('#^(https?:)?//#i', $redirectUrl)) {
        return 'profile';
    }

    return ltrim($redirectUrl, '/');
}

function redirectLoggedInUser() {
    if (session_status() == PHP_SESSION_NONE) {
        // Start or resume the session
        session_start();
    }

    // Check if the user_id exists
    if (isset($_SESSION['user_id'])) {
        // Check if the redirect parameter is set
        if (isset($_GET['redirect'])) {
            $redirect = candybirdSafeLoginRedirect($_GET['redirect']);
            header("Location: $redirect");
            exit();
        }
    }
}

// Call the redirectLoggedInUser function
redirectLoggedInUser();
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

<title>Sign In - Sir Francis</title>
<?php
include 'page_menues.php';
?>

<!-- login area start -->
<div class="login-register-area pt-80 pb-80">
  <div class="container">
    <div class="row">
      <div class="col-lg-7 col-md-12 ml-auto mr-auto">
        <div class="login-register-wrapper">
          <div class="login-register-tab-list nav">
            <a class="active" data-toggle="tab" href="#lg1">
              <h4>login</h4>
            </a>
            <a data-toggle="tab" href="#lg2">
              <h4>register</h4>
            </a>
          </div>
          <div class="tab-content">
            <div id="lg1" class="tab-pane active">
              <div class="login-form-container">
                <div class="login-register-form">
                  <form id="login-form" action="login.inc.php" method="post">
                    <?php
                    if (isset($_GET['redirect'])) {
                      $redirect_url = candybirdSafeLoginRedirect($_GET['redirect']);
                    } else {
                      $redirect_url = 'profile';
                    }
                    ?>
                    <input class='mb-0 py-0' type="hidden" name="redirect_url" value="<?=htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8')?>">
                    <input class='mb-0 py-0' type="hidden" name="guest-identifier" value="<?=$guestIdentifier?>">
                    <div class="form-group">
                      <input class='mb-0 py-0' type="text" name="user-name" placeholder="Username" />
                      <span class="mx-2 error-message text-danger" id="login-username-error"></span>
                    </div>
                    <div class="form-group">
                      <input class='mb-0 py-0' type="password" name="user-password" placeholder="Password" />
                      <span class="mx-2 error-message text-danger" id="login-password-error"></span>
                    </div>
                    <div class="button-box">
                      <div class="login-toggle-btn">
                        <input id="remember" type="checkbox" name="remember" />
                        <label for="remember">Remember me</label>
                        <a href="forgot-password">Forgot Password?</a>
                      </div>
                      <button type="submit" class="btn btn-dark btn--md">
                        <span>Login</span>
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <div id="lg2" class="tab-pane">
              <div class="login-form-container">
                <div class="login-register-form">
                  <form id="register-form" action="register.inc.php" method="post">
                    <!-- Username -->
                    <div class="form-group">
                      <input type="text" name="user-name" placeholder="Username" />
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                      <input type="email" name="user-email" placeholder="Email" />
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                      <input type="password" name="user-password" placeholder="Password" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                      <input type="password" name="confirm-password" placeholder="Confirm Password" />
                    </div>

                    <!-- SINGLE ERROR BOX (for all errors) -->
                    <div id="register-error-box" class="alert alert-danger" style="display: none;"></div>

                    <!-- Submit button -->
                    <div class="button-box">
                      <button type="submit" class="btn btn-dark btn--md">
                        <span>Register</span>
                      </button>
                    </div>
                  </form>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
   $(document).ready(function () {
  // Clear previous error messages
  function clearErrors(formId) {
    $(`${formId} .error-message`).text('');
  }

  // Display error messages beneath form fields
  function displayErrors(errors, formId) {
    for (const [field, message] of Object.entries(errors)) {
      $(`#${formId}-${field}-error`).text(message);
    }
  }

  // Submit login form via AJAX
  $('#login-form').submit(function (e) {
    e.preventDefault();
    clearErrors('#login-form');
    $.ajax({
      type: 'POST',
      url: 'login.inc.php',
      data: $(this).serialize(),
      success: function (response) {
        if (response.success) {
          window.location.href = response.redirect_url;
        } else {
          displayErrors(response.errors, 'login');
        }
      },
      error: function () {
        console.error('An error occurred during the AJAX request.');
      },
    });
  });

  // Submit registration form via AJAX
  $('#register-form').submit(function (e) {
    e.preventDefault();

    // Clear old errors
    $('#register-error-box').hide().html('');

    $.ajax({
      type: 'POST',
      url: 'register.inc.php',
      data: $(this).serialize(),
      success: function (response) {
        if (response.success) {
          // Redirect on successful registration
          window.location.href = response.redirect_url || 'https://www.fishgelatine.co.za/v2/login';
        } else {
          // Combine all error messages into one string with line breaks
          let errorText = '';

          if (response.errors) {
            $.each(response.errors, function (key, value) {
              errorText += value + '<br>';
            });
          }

          if (response.message) {
            errorText += response.message;
          }

          // Show the error box with messages
          $('#register-error-box').html(errorText).show();
        }
      },
      error: function () {
        $('#register-error-box').text('An unexpected error occurred. Please try again.').show();
      },
    });
  });


  
});
</script>


<!-- login area end -->
<?php
include 'footer.php';
?>
