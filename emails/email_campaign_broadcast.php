<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{subject}</title>
</head>
<body style="margin:0;padding:0;background:#f5f2ea;font-family:Arial,Helvetica,sans-serif;color:#2b2230;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f5f2ea;margin:0;padding:24px 12px;">
        <tr>
            <td align="center">
                <table width="680" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;max-width:680px;background:#ffffff;border-collapse:collapse;">
                    <tr>
                        <td style="background:#5b1178;padding:22px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="left">
                                        <img src="https://www.candybird.co.za/assets/img/emails/footer-image1.png" alt="CandyBird" width="142" style="display:block;max-width:142px;border:0;">
                                    </td>
                                    <td align="right" style="color:#fcb42f;font-size:21px;font-weight:700;">{email_heading}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    {hero_image}
                    <tr>
                        <td style="padding:28px;color:#51475a;font-size:15px;line-height:1.7;">
                            {body}
                            {coupon_box}
                            {cta_button}
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background:#f5f2ea;padding:24px 18px;color:#5b1178;font-size:12px;line-height:1.7;">
                            <p style="margin:0 0 10px;">Need help? <a href="mailto:sales@candybird.co.za" style="color:#5b1178;text-decoration:none;font-weight:700;">sales@candybird.co.za</a></p>
                            <p style="margin:0 0 10px;">
                                <a href="https://www.candybird.co.za/products" style="color:#5b1178;text-decoration:none;">Shop</a> |
                                <a href="https://www.candybird.co.za/contact" style="color:#5b1178;text-decoration:none;">Help</a> |
                                <a href="https://www.candybird.co.za/privacypolicy" style="color:#5b1178;text-decoration:none;">Privacy policy</a>
                            </p>
                            <p style="margin:0;">Copyright &copy; {year} CandyBird. All rights reserved.</p>
                            <p style="margin:10px 0 0;font-size:11px;">You are receiving this because you subscribed to CandyBird emails. <a href="https://www.candybird.co.za/unsubscribe?email={user_email_unsubscribe}" style="color:#5b1178;text-decoration:none;">Unsubscribe</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
