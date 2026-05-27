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
<table width="100%" style="background-color: #6b0099; color: #ffffff;" cellpadding="20">
    <tr>
        <td align="left" style="padding-left: 20px;">
            <img src="https://www.candybird.co.za/assets/img/emails/footer-image1.png" alt="candybird" width="150" style="max-width: 150px;">
        </td>
        <td align="right" style="padding-right: 20px;">
            <h3 style="font-size: 30px; font-weight: bold; color: #FCB42F; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Order Update</h3>
        </td>
    </tr>
</table>
<!-- Rope Div -->
<!-- <div style="width: 100%; height: 20px; margin-top: -10px; background-image: url('https://www.candybird.co.za/assets/img/rope.png'); background-repeat: repeat-x; background-position: bottom left; position: relative;"></div> -->

<div style="margin-bottom: 20px; margin-top: 20px;">
    <table class="table table-bordered table-striped" style="width: 50%; border-collapse: collapse; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff; text-align: left;">
        <thead>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Order Number:</th>
                <td style="padding: 10px; border: 1px solid #ccc;">{order_id}</td>
            </tr>
        </thead>
    </table>
</div>

<!-- Body Section -->
<table width="100%" cellpadding="30" style="font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff;">
    <tr>
        <td>
            <h1 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Hi {recipient_name},</h1>
            <p style="font-size: 14px; line-height: 1.6; padding: 0 20px;">
            You updated the status of order {order_id}. The following message was emailed to the client:
            </p>
            <p style="font-size: 14px; line-height: 1.6; padding: 0 20px;">
            {custom_message}
            </p>
        </td>
    </tr>
</table>

<h3 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Order details</h3>

<!-- Table with Information -->
<div style="margin-bottom: 20px;">
    <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff; text-align: left;">
        <thead>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Order Status:</th>
                <td style="padding: 10px; border: 1px solid #ccc;">{order_status}</td>
            </tr>
        </thead>
    </table>
</div>

<a href="https://www.candybird.co.za/admin-cb/manage_order?order_id={order_id}" target="_blank" style="font-size: 12px; font-weight: bold; color: #FCB42F; text-decoration: none; padding: 10px 20px; display: inline-block; border-radius: 0; background-color: #6b0099;">Manage Order</a>


<!-- Footer Section -->
<table width="100%" style="background-color: #F1F0E8;color:#6b0099;" cellpadding="20">
    <tr>
        <td align="center">
            <h4 style="font-size: 18px; font-weight: bold; color: #6b0099; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">- Web Owner Email -</h4>
            <p style="font-size: 10px; color: #6b0099;">
            </p>
        </td>
    </tr>

    <tr>
        <td align="center">
          <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="font-size:10px; display: inline-block; color: #6b0099;"> <a href="https://www.candybird.co.za/privacypolicy" style="text-decoration: none; color: #6b0099; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Privacy policy</a></li>
            <li style="font-size:10px; display: inline-block; color: white;"> <span style="padding-left:5px;padding-right: 5px;">&#8226;</span> <a href="https://www.candybird.co.za/return_policy" style="text-decoration: none; color: #6b0099; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Iqaala Buyer Protection</a></li>
            <li style="font-size:10px; display: inline-block; color: white;"> <span style="padding-left:5px;padding-right: 5px;">&#8226;</span> <a href="https://www.candybird.co.za/recipes" style="text-decoration: none; color: #6b0099; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Recipe Corner</a></li>
          </ul>
      </td>
  </tr>

    <tr>
        <td align="center" style="padding: 20px;">
            <!-- Social Media Icons -->
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <a href="https://www.facebook.com/candybirdnuts" style="margin-right: 10px;"><img src="https://www.candybird.co.za/assets/img/emails/fb_icon.png" alt="Facebook" width="30"></a>
                    </td>
                    <td align="center">
                        <a href="https://www.instagram.com/candybirdnuts" target="_blank" style="margin-right: 10px;"><img src="https://www.candybird.co.za/assets/img/emails/ig_icon.png" alt="Instagram" width="30"></a>
                    </td>
                    <td align="center">
                        <a href="https://x.com/candybirdnuts" target="_blank"><img src="https://www.candybird.co.za/assets/img/emails/x_icon.png" alt="Twitter" width="30"></a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding: 20px;">
            <p style="font-size: 12px; line-height: 1.5; color: #6b0099; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">
                Copyright &copy; <?php echo date("Y"); ?> CandyBird. All rights reserved.
                <br>
            </p>
        </td>
    </tr>
</table>

</body>

</html>