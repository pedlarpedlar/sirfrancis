<?php
// Start or resume the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "shipping";
    header("Location: admin_login?redirect=" . urlencode($redirect_url)); // Redirect to the login page
    exit(); // Stop further execution
}

// Fetch admin_id from the session
$admin_id = $_SESSION['admin_id'];

include 'dbh.inc.php';
include 'header.php';

if (isset($_POST['submit'])) {
    $id = $_POST['id'];
    $country = $_POST['country'];
    $province = $_POST['province'];
    $shipping_cost = $_POST['shipping_cost'];

    if ($id) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE shipping_zones SET country = ?, province = ?, shipping_cost = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $country, $province, $shipping_cost, $id);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO shipping_zones (country, province, shipping_cost) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $country, $province, $shipping_cost);
    }

    if ($stmt->execute()) {
        header("Location: shipping");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

if (isset($_POST['bulk_submit'])) {
    $country = trim((string) ($_POST['bulk_country'] ?? ''));
    $shipping_cost = (float) ($_POST['bulk_shipping_cost'] ?? 0);
    $provinces = preg_split('/[\r\n,]+/', (string) ($_POST['bulk_provinces'] ?? ''));
    $saved = 0;

    if ($country !== '' && !empty($provinces)) {
        $stmt = $conn->prepare("INSERT INTO shipping_zones (country, province, shipping_cost)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE shipping_cost = VALUES(shipping_cost)");
        if ($stmt) {
            foreach ($provinces as $province) {
                $province = trim($province);
                if ($province === '') {
                    continue;
                }
                $stmt->bind_param("ssd", $country, $province, $shipping_cost);
                if ($stmt->execute()) {
                    $saved++;
                }
            }
            $stmt->close();
        }
    }

    header("Location: shipping?saved=" . (int) $saved);
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM shipping_zones WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: shipping");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<title>Manage Shipping Zoness</title>


<?php
include 'page_menues.php';
?>

<div class="container mt-5">
<div class="p-4">
    <div class="container-fluid mt-5 mb-5 text-md-start">

<h2 class="mb-4">Manage Shipping Zones</h2>
<div class="alert alert-info">
    Cart, checkout, PayFast, and admin edit-order totals currently use the central delivery prices on
    <a href="manage_website_information">Website Settings</a>. This page is for country/province zone records used by older address forms and lookups.
</div>
<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success"><?= (int) $_GET['saved'] ?> province zone(s) saved.</div>
<?php endif; ?>
<div class="card mb-4">
    <div class="card-body">
        <h4>Add Many Provinces</h4>
        <form method="post" action="shipping.php">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="bulk_country">Country</label>
                    <input type="text" name="bulk_country" id="bulk_country" class="form-control" placeholder="South Africa" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="bulk_shipping_cost">Shipping Cost</label>
                    <input type="number" step="0.01" name="bulk_shipping_cost" id="bulk_shipping_cost" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label for="bulk_provinces">Provinces / States</label>
                <textarea name="bulk_provinces" id="bulk_provinces" class="form-control" rows="5" placeholder="Eastern Cape&#10;Western Cape&#10;Gauteng" required></textarea>
                <small class="form-text text-muted">One province per line, or comma-separated.</small>
            </div>
            <button type="submit" name="bulk_submit" class="btn btn-primary">Save all provinces</button>
        </form>
    </div>
</div>
<form method="post" action="shipping.php">
    <input type="hidden" name="id" id="id">
    <div class="form-group">
        <label for="country">Country:</label>
        <input type="text" name="country" id="country" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="province">Province:</label>
        <input type="text" name="province" id="province" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="shipping_cost">Shipping Cost:</label>
        <input type="text" name="shipping_cost" id="shipping_cost" class="form-control" required>
    </div>
    <button type="submit" name="submit" class="btn btn-primary">Save</button>
</form>


<h2>Existing Shipping Zones</h2>
<table class="table">
    <thead>
        <tr>
            <th>Country</th>
            <th>Province</th>
            <th>Shipping Cost</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php 

        $sql = "SELECT * FROM shipping_zones";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['country']) . "</td>
                        <td>" . htmlspecialchars($row['province']) . "</td>
                        <td>R" . htmlspecialchars($row['shipping_cost']) . "</td>
                        <td>
                            <a href=\"shipping?id=" . $row['id'] . "\">Edit</a>
                            <a href=\"shipping?delete=" . $row['id'] . "\" onclick=\"return confirm('Are you sure you want to delete this shipping zone?');\">Delete</a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No shipping zones found.</td></tr>";
        }
        ?>

    </tbody>
</table>

   </div>
</div>

</div>

<!-- Include jQuery library -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<?php
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM shipping_zones WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo "<script>
            document.getElementById('id').value = '" . $row['id'] . "';
            document.getElementById('country').value = '" . $row['country'] . "';
            document.getElementById('province').value = '" . $row['province'] . "';
            document.getElementById('shipping_cost').value = '" . $row['shipping_cost'] . "';
          </script>";

    $stmt->close();
}
?>

<?php
include '../footer.php';
?>
