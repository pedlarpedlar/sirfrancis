<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Beautiful Email</title>

    <!-- Font Imports -->
    <style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap');
</style>
</head>

<body style="font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #F1F0E8;">

<!-- Header Section -->
<table width="100%" style="background-color: #28364B; color: #ffffff;" cellpadding="20">
    <tr>
        <td align="left" style="padding-left: 20px;">
            <img src="https://sirfrancis.co.za/assets/img/logo/logo-gold.png" alt="Sir Francis" width="150" style="max-width: 150px;">
        </td>
        <td align="right" style="padding-right: 20px;">
            <h3 style="font-size: 30px; font-weight: bold; color: #CEBD88; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">New User Registration</h3>
        </td>
    </tr>
</table>
<!-- Rope Div -->
<!-- <div style="width: 100%; height: 20px; margin-top: -4px; background-image: url('https://sirfrancis.co.za/assets/img/rope.png'); background-repeat: repeat-x; background-position: bottom left; position: relative;"></div> -->
<!-- Body Section -->
<table width="100%" cellpadding="30" style="font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff;">

        <tr>
        <td>
            <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; font-family: 'Raleway', cursive;">Dear Admin,</h1>
            <p style="font-size: 14px; line-height: 1.6; padding: 0 20px;">
            A new user has registered on our platform. Here are the user details:
            </p>
        </td>
    </tr>
</table>

<!-- Table with Information -->
<div style="margin-bottom: 20px;">
    <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff;">
        <thead>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">User ID</th>
                <th style="padding: 10px; border: 1px solid #ccc;">User Name</th>
                <th style="padding: 10px; border: 1px solid #ccc;">Email</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 10px; border: 1px solid #ccc;">{user_id} Code: {special_code}</td>
                <td style="padding: 10px; border: 1px solid #ccc;">{user_name}</td>
                <td style="padding: 10px; border: 1px solid #ccc;">{user_email}</td>
            </tr>
        </tbody>
    </table>
</div>


<p style="font-size: 14px; line-height: 1.6; padding: 0 20px;">
            Please take appropriate action to welcome the new user and provide them with any necessary assistance.
            </p>

<!-- Button -->
<table cellspacing="0" cellpadding="10" bgcolor="#F1F0E8" style="margin-top: 30px; padding: 15px; border-radius: 0;">
    <tr>
        <td align="center">
            <a href="https://sirfrancis.co.za/admin-sf/users?id={user_id}" target="_blank" style="font-size: 12px; font-weight: bold; color: #CEBD88; text-decoration: none; padding: 10px 20px; display: inline-block; border-radius: 0; background-color: #28364B;">View User</a>
        </td>
        <td>
            <!-- End of Button -->
            <p style="font-size: 10px; line-height: 1.6; padding: 0 20px;">
                 Thank you for your attention to this matter.
            </p>
            <p style="font-size: 10px; line-height: 1.6; padding: 0 20px;">
                Sir Francis Management Team
            </p>

        </td>
    </tr>
</table>

<!-- Footer Section -->
<table width="100%" style="background-color: #F1F0E8;color:#28364B;" cellpadding="20">
    <tr>
        <td align="center">
            <h4 style="font-size: 18px; font-weight: bold; color: #28364B; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">- Web Owner Email -</h4>
            <p style="font-size: 10px; color: #28364B;">
            </p>
        </td>
    </tr>

    <tr>
        <td align="center">
          <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="font-size:10px; display: inline-block; color: #28364B;"> <a href="https://sirfrancis.co.za/privacypolicy" style="text-decoration: none; color: #28364B; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Privacy policy</a></li>
          </ul>
      </td>
  </tr>

    <tr>
        <td align="center" style="padding: 20px;">
            <!-- Social Media Icons -->
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <a href="https://sirfrancis.co.za" style="margin-right: 10px;"><img src="https://sirfrancis.co.za/assets/img/emails/fb_icon.png" alt="Facebook" width="30"></a>
                    </td>
                    <td align="center">
                        <a href="https://sirfrancis.co.za" target="_blank" style="margin-right: 10px;"><img src="https://sirfrancis.co.za/assets/img/emails/ig_icon.png" alt="Instagram" width="30"></a>
                    </td>
                    <td align="center">
                        <a href="https://sirfrancis.co.za" target="_blank"><img src="https://sirfrancis.co.za/assets/img/emails/x_icon.png" alt="Twitter" width="30"></a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding: 20px;">
            <p style="font-size: 12px; line-height: 1.5; color: #28364B; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">
                Copyright &copy; <?php echo date("Y"); ?> Sir Francis. All rights reserved.
                <br>
            </p>
        </td>
    </tr>
</table>

</body>

</html>