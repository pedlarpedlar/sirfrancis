<?php
// Start or resume the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "manage_users";
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

include 'dbh.inc.php';

include 'header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">


<title>Manage Users</title>


<?php
include 'page_menues.php';
?>


<div class="container">
    <h2>User Information Table</h2>
    <table id="userTable" class="display nowrap" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Last Login</th>
                <th>Order Count</th>
                <th>Cart</th>
                <th>Wishlist</th>
                <th>Compare</th>
                <th>Review Count</th>
                <th>Comment Count</th>
                <th>Is Subscribed</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Profile Picture</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>


<?php
include '../footer.php';
?>