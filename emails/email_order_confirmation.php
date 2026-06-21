<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sir Francis Order Confirmation</title>
</head>
<body style="margin:0;padding:0;background:#f5f2ea;font-family:Arial,Helvetica,sans-serif;color:#2b2230;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f5f2ea;margin:0;padding:24px 12px;">
        <tr>
            <td align="center">
                <table width="680" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;max-width:680px;background:#ffffff;border-collapse:collapse;">
                    <tr>
                        <td style="background:#28364B;padding:22px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="left">
                                        <img src="https://sirfrancis.co.za/assets/img/logo/logo-gold.png" alt="Sir Francis" width="142" style="display:block;max-width:142px;border:0;">
                                    </td>
                                    <td align="right" style="color:#CEBD88;font-size:22px;font-weight:700;">Order confirmed</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 10px;font-size:16px;line-height:1.5;">Hi {recipient_name},</p>
                            <p style="margin:0;color:#51475a;font-size:14px;line-height:1.6;">Thank you, we have received order <strong>#{order_id}</strong>. You can view the order, payment status, and full breakdown at any time.</p>
                            {resend_notice}
                            <table cellpadding="0" cellspacing="0" role="presentation" style="margin-top:20px;">
                                <tr>
                                    <td style="background:#28364B;">
                                        <a href="{order_details_url}" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">View or pay order</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 22px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border:1px solid #e8e1d7;">
                                <tr>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e8e1d7;color:#6b6070;font-size:13px;">Order status</td>
                                    <td align="right" style="padding:12px 14px;border-bottom:1px solid #e8e1d7;font-size:13px;font-weight:700;">{order_status}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e8e1d7;color:#6b6070;font-size:13px;">Payment</td>
                                    <td align="right" style="padding:12px 14px;border-bottom:1px solid #e8e1d7;font-size:13px;font-weight:700;">{payment_method}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e8e1d7;color:#6b6070;font-size:13px;">Delivery</td>
                                    <td align="right" style="padding:12px 14px;border-bottom:1px solid #e8e1d7;font-size:13px;font-weight:700;">{delivery_summary}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;border-bottom:1px solid #e8e1d7;color:#6b6070;font-size:13px;">Estimated order weight</td>
                                    <td align="right" style="padding:12px 14px;border-bottom:1px solid #e8e1d7;font-size:13px;font-weight:700;">{order_weight_estimate}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;color:#6b6070;font-size:13px;vertical-align:top;">Deliver to</td>
                                    <td align="right" style="padding:12px 14px;font-size:13px;line-height:1.5;">{delivery_address}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 8px;">
                            <h2 style="margin:0 0 12px;font-size:18px;color:#2b2230;">Items in your order</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                                <tbody>
                                    {order_items}
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 28px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:8px 0;color:#555555;">Products subtotal</td>
                                    <td align="right" style="padding:8px 0;font-weight:700;">{order_subtotal}</td>
                                </tr>
                                {product_discount_row}
                                {coupon_row}
                                <tr>
                                    <td style="padding:8px 0;color:#555555;">Delivery ({delivery_summary})</td>
                                    <td align="right" style="padding:8px 0;font-weight:700;">{order_shipping}</td>
                                </tr>
                                {shipping_discount_row}
                                <tr>
                                    <td style="padding:12px 0;border-top:1px solid #e8e1d7;color:#555555;">Total savings</td>
                                    <td align="right" style="padding:12px 0;border-top:1px solid #e8e1d7;color:#1d7d38;font-weight:700;">{order_discount}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 0;border-top:2px solid #28364B;font-size:18px;font-weight:700;">Total</td>
                                    <td align="right" style="padding:14px 0;border-top:2px solid #28364B;font-size:18px;font-weight:700;color:#28364B;">{order_total}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#fbfaf7;border:1px solid #e8e1d7;">
                                <tr>
                                    <td style="padding:14px;color:#51475a;font-size:13px;line-height:1.6;">
                                        <strong style="color:#2b2230;">Order notes</strong><br>
                                        {order_notes}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background:#f5f2ea;padding:24px 18px;color:#28364B;font-size:12px;line-height:1.7;">
                            <p style="margin:0 0 10px;">Need help? <a href="mailto:info@sirfrancis.co.za" style="color:#28364B;text-decoration:none;font-weight:700;">info@sirfrancis.co.za</a></p>
                            <p style="margin:0 0 10px;">
                                <a href="https://sirfrancis.co.za/contact" style="color:#28364B;text-decoration:none;">Help</a> |
                                <a href="https://sirfrancis.co.za/privacypolicy" style="color:#28364B;text-decoration:none;">Privacy policy</a> |
                            </p>
                            <p style="margin:0;">Copyright &copy; {year} Sir Francis. All rights reserved.</p>
                            <p style="margin:10px 0 0;font-size:11px;">Please add <a href="mailto:info@sirfrancis.co.za" style="color:#28364B;text-decoration:none;">info@sirfrancis.co.za</a> and <a href="https://sirfrancis.co.za" style="color:#28364B;text-decoration:none;">sirfrancis.co.za</a> to your safe senders list so order updates reach you. Prefer fewer updates? <a href="https://sirfrancis.co.za/unsubscribe?email={user_email_unsubscribe}" style="color:#28364B;text-decoration:none;">Unsubscribe from marketing emails here</a>. Essential order, payment, account and security emails will still be sent when needed.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
