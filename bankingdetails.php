<?php
include 'session_logins.php';
date_default_timezone_set('Africa/Johannesburg'); // Set to GMT+2

include 'header.php';

$page_url_canonical = "https://www.candybird.co.za/bankingdetails";
$title_og = 'Banking Details - CandyBird';
$page_url_og = "https://www.candybird.co.za/bankingdetails";
$description_og = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');
$description_meta = htmlspecialchars($limitedDescription, ENT_QUOTES, 'UTF-8');

include 'page_menues.php';

?>
    <style>
        .banking-details-card {
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .card-body {
            font-size: 1.2rem;
        }
        .payment-instructions {
            margin-bottom: 30px;
        }
        .payment-instructions h2 {
            font-size: 1.75rem;
            margin-bottom: 20px;
        }
        .payment-instructions p {
            font-size: 1.2rem;
            line-height: 1.6;
        }
    </style>

<div class="pt-30 pb-50">
  <div class="container">

        <!-- Payment Instructions Section -->
        <div class="payment-instructions">
            <h2>Payment Instructions</h2>
            <p>Please follow these instructions when making payments to ensure smooth processing:</p>
            <ul>
                <li>Ensure you use the correct bank account details provided below.</li>
                <li>Include your invoice number or order number in the payment reference.</li>
                <li>For any questions or concerns, please contact our support team at support@candybird.co.za.</li>
                <li>Payments are processed within 2-3 business days. Thank you for your patience.</li>
            </ul>
        </div>
        
        <!-- FNB Bank -->
        <div class="card banking-details-card">
            <div class="card-header">
                FNB Bank
            </div>
            <div class="card-body">
                <p>
                    <strong>Bank Name:</strong> FNB Bank<br>
                    <strong>Branch Code:</strong> 250655<br>
                    <strong>Account Name:</strong> CandyBird (PTY) Ltd<br>
                    <strong>Account Number:</strong> 62793190829<br>
                    <strong>Account Type:</strong> Current<br>
                    <strong>SWIFT CODE:</strong> FIRNZAJJ<br>
                </p>
            </div>
        </div>

        <!-- Nedbank -->
        <div class="card banking-details-card">
            <div class="card-header">
                Nedbank
            </div>
            <div class="card-body">
                <p>
                    <strong>Bank Name:</strong> Nedbank<br>
                    <strong>Branch Code:</strong> 198765<br>
                    <strong>Account Name:</strong> CandyBird (PTY) Ltd<br>
                    <strong>Account Number:</strong> 1206148063<br>
                    <strong>Account Type:</strong> Cheque<br>
                    <strong>SWIFT CODE:</strong> NEDSZAJJ<br>
                </p>
            </div>
        </div>

        <!-- Capitec Bank -->
        <div class="card banking-details-card">
            <div class="card-header">
                Capitec Bank
            </div>
            <div class="card-body">
                <p>
                    <strong>Bank Name:</strong> Capitec Bank<br>
                    <strong>Branch Code:</strong> 470010<br>
                    <strong>Account Name:</strong> CandyBird (PTY) Ltd<br>
                    <strong>Account Number:</strong> 1368715581<br>
                    <strong>Account Type:</strong> Savings<br>
                    <strong>SWIFT CODE:</strong> CABLZAJJ<br>
                </p>
            </div>
        </div>
    </div>
    
</div>

<?php

include 'footer.php';

?>