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
            <img src="https://www.fishgelatine.co.za/v2/assets/img/logo/logo.png" alt="Sir Francis" width="150" style="max-width: 150px;">
        </td>
        <td align="right" style="padding-right: 20px;">
            <h3 style="font-size: 30px; font-weight: bold; color: #CEBD88; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Payment Confirmation</h3>
        </td>
    </tr>
</table>
<!-- Rope Div -->
<!-- <div style="width: 100%; height: 20px; margin-top: -10px; background-image: url('https://www.fishgelatine.co.za/v2/assets/img/rope.png'); background-repeat: repeat-x; background-position: bottom left; position: relative;"></div> -->

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
            Thank you, we’ve received your payment of {order_total} for order {order_id}.
            </p>
            <p style="font-size: 14px; line-height: 1.6; padding: 0 20px;">
            Your order is being carefully prepared.
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
                <th style="padding: 10px; border: 1px solid #ccc;">Delivery Address:</th>
                <td style="padding: 10px; border: 1px solid #ccc;">{delivery_address}</td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Estimated Order Weight:</th>
                <td style="padding: 10px; border: 1px solid #ccc;">{order_weight_estimate}</td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Order Status:</th>
                <td style="padding: 10px; border: 1px solid #ccc;">{order_status}</td>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ccc;">Payment Method:</th>
                <td style="padding: 10px; border: 1px solid #ccc;">{payment_method}</td>
            </tr>
        </thead>
    </table>
</div>

<h3 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Items in this order</h3>

<!-- Table with Information -->
<div style="margin-bottom: 20px;">
    <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal; background-color: #ffffff;">
        <tbody>
            {order_items}
        </tbody>
        <tfoot>
            <tr>
                <td style="padding: 10px; border: none; text-align: left;">Order Notes:</th>
                <td colspan="3" style="padding: 10px; border: none; text-align: left;">{order_notes}</th>
            </tr>

            {coupon_section}

            <tr>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;">Subtotal:</th>
                <th style="padding: 10px; border: none;text-align: right;">{order_subtotal}</th>
            </tr>
            <tr>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;">Shipping:</th>
                <th style="padding: 10px; border: none;text-align: right;">{order_shipping}</th>
            </tr>
            <tr>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;">Savings:</th>
                <th style="padding: 10px; border: none;text-align: right;">{order_discount}</th>
            </tr>
            <tr>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;">Total:</th>
                <th style="padding: 10px; border: none;text-align: right;">{order_total}</th>
            </tr>

            <tr>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;"></th>
                <th style="padding: 10px; border: none;text-align: right;">
                    <a href="https://www.fishgelatine.co.za/v2/order_details?order_id={order_id}" target="_blank" style="font-size: 12px; font-weight: bold; color: #CEBD88; text-decoration: none; padding: 10px 20px; display: inline-block; border-radius: 0; background-color: #28364B;">Manage Order</a>
                </th>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Footer Section -->
<table width="100%" style="background-color: #F1F0E8;color:#28364B;" cellpadding="20">
    <tr>
        <td align="center">
            <h4 style="font-size: 18px; font-weight: bold; color: #28364B; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Need help?</h4>
            <p style="font-size: 10px; color: #28364B;">
                <br>
                <a href="mailto:info@fishgelatine.co.za" style="color: #28364B; text-decoration: none; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">info@fishgelatine.co.za</a>
                <br>
                <br>
                <a style="color: #28364B;text-decoration: none;" href="https://www.fishgelatine.co.za/v2/contact">Help</a>
            </p>
        </td>
    </tr>

    <tr>
        <td align="center">
          <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="font-size:10px; display: inline-block; color: #28364B;"> <a href="https://www.fishgelatine.co.za/v2/privacypolicy" style="text-decoration: none; color: #28364B; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Privacy policy</a></li>
            <li style="font-size:10px; display: inline-block; color: white;"> <span style="padding-left:5px;padding-right: 5px;">&#8226;</span> <a href="https://www.fishgelatine.co.za/v2/return_policy" style="text-decoration: none; color: #28364B; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Iqaala Buyer Protection</a></li>
            <li style="font-size:10px; display: inline-block; color: white;"> <span style="padding-left:5px;padding-right: 5px;">&#8226;</span> <a href="https://www.fishgelatine.co.za/v2/recipes" style="text-decoration: none; color: #28364B; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">Recipe Corner</a></li>
          </ul>
      </td>
  </tr>

    <tr>
        <td align="center" style="padding: 20px;">
            <!-- Social Media Icons -->
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <a href="https://www.facebook.com/candybirdnuts" style="margin-right: 10px;"><img src="https://www.fishgelatine.co.za/v2/assets/img/emails/fb_icon.png" alt="Facebook" width="30"></a>
                    </td>
                    <td align="center">
                        <a href="https://www.instagram.com/candybirdnuts" target="_blank" style="margin-right: 10px;"><img src="https://www.fishgelatine.co.za/v2/assets/img/emails/ig_icon.png" alt="Instagram" width="30"></a>
                    </td>
                    <td align="center">
                        <a href="https://x.com/candybirdnuts" target="_blank"><img src="https://www.fishgelatine.co.za/v2/assets/img/emails/x_icon.png" alt="Twitter" width="30"></a>
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
                This email was sent to you because you registered to use our services at <a href="https://www.fishgelatine.co.za/v2" style="color: #28364B; text-decoration: none; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">www.fishgelatine.co.za</a>. We hope you find our services helpful and stay connected with us.
                <br>
                If you no longer wish to receive our emails, you can <a href="https://www.fishgelatine.co.za/v2/unsubscribe?email={user_email_unsubscribe}" style="color: #28364B; text-decoration: none; font-family:'Montserrat', sans-serif;font-optical-sizing: auto;font-weight: 400;font-style: normal;">unsubscribe here</a>.
            </p>
        </td>
    </tr>
</table>

</body>

</html>
