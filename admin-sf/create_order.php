<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    $redirect_url = "create_order";
    header("Location: admin_login?redirect=" . urlencode($redirect_url));
    exit();
}

include 'dbh.inc.php';

$message = '';
$success = false;
$old = $_POST;
$googleMapsApiKey = '';
$googlePlacesApiKey = '';

function cbCreateOrderText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($conn instanceof mysqli) {
    $mapsColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'google_maps_api_key'");
    if ($mapsColumnCheck && $mapsColumnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE admin_website_settings ADD COLUMN google_maps_api_key VARCHAR(255) NULL");
    }
    $placesColumnCheck = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'google_places_api_key'");
    if ($placesColumnCheck && $placesColumnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE admin_website_settings ADD COLUMN google_places_api_key VARCHAR(255) NULL");
    }
    $mapsResult = $conn->query("SELECT google_maps_api_key, google_places_api_key FROM admin_website_settings LIMIT 1");
    if ($mapsResult && ($mapsRow = $mapsResult->fetch_assoc())) {
        $googleMapsApiKey = trim((string) ($mapsRow['google_maps_api_key'] ?? ''));
        $googlePlacesApiKey = trim((string) ($mapsRow['google_places_api_key'] ?? ''));
    }
}
if ($googlePlacesApiKey === '') {
    $googlePlacesApiKey = $googleMapsApiKey;
}
if ($googleMapsApiKey === '') {
    $googleMapsApiKey = trim((string) getenv('SIRFRANCIS_GOOGLE_MAPS_API_KEY'));
}
if ($googlePlacesApiKey === '') {
    $googlePlacesApiKey = trim((string) getenv('SIRFRANCIS_GOOGLE_PLACES_API_KEY'));
}
if ($googlePlacesApiKey === '') {
    $googlePlacesApiKey = $googleMapsApiKey;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['billing_first_name'] ?? '');
    $lastName = trim($_POST['billing_last_name'] ?? '');
    $email = trim($_POST['billing_email_address'] ?? '');
    $phone = trim($_POST['billing_phone_number'] ?? '');
    $street1 = trim($_POST['billing_street_address_1'] ?? '');
    $street2 = trim($_POST['billing_street_address_2'] ?? '');
    $city = trim($_POST['billing_city'] ?? '');
    $province = trim($_POST['billing_province'] ?? '');
    $postCode = trim($_POST['billing_post_code'] ?? '');
    $country = trim($_POST['billing_country'] ?? 'South Africa');
    $notes = trim($_POST['order_notes'] ?? '');
    $paymentStatus = (int) ($_POST['payment_status'] ?? 0);

    if ($firstName === '' || $lastName === '' || $email === '') {
        $message = 'Add at least the customer name and email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Use a valid customer email address.';
    } else {
        $guestIdentifier = 'admin_' . bin2hex(random_bytes(8));
        $conn->begin_transaction();

        try {
            $addressSql = "INSERT INTO user_addresses
                (guest_identifier, billing_first_name, billing_last_name, billing_phone_number, billing_email_address, billing_street_address_1, billing_street_address_2, billing_city, billing_country, billing_province, billing_post_code,
                 shipping_first_name, shipping_last_name, shipping_phone_number, shipping_email_address, shipping_street_address_1, shipping_street_address_2, shipping_city, shipping_country, shipping_province, shipping_post_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $addressStmt = $conn->prepare($addressSql);
            if (!$addressStmt) {
                throw new Exception($conn->error);
            }
            $addressStmt->bind_param(
                'sssssssssssssssssssss',
                $guestIdentifier,
                $firstName,
                $lastName,
                $phone,
                $email,
                $street1,
                $street2,
                $city,
                $country,
                $province,
                $postCode,
                $firstName,
                $lastName,
                $phone,
                $email,
                $street1,
                $street2,
                $city,
                $country,
                $province,
                $postCode
            );
            $addressStmt->execute();
            $addressStmt->close();

            $shippingAddress = trim(implode("\n", array_filter([
                $firstName . ' ' . $lastName,
                $street1,
                $street2,
                trim($city . ' ' . $province . ' ' . $postCode),
                $country,
                $phone,
                $email
            ])));

            $orderSql = "INSERT INTO orders
                (user_id, guest_identifier, order_status, payment_status, subtotal_amount, discount_amount, shipping_amount, shipping_discount_amount, coupon_amount, grand_total_amount, payment_method, shipping_address, order_notes)
                VALUES (NULL, ?, 'Pending', ?, 0, 0, 0, 0, 0, 0, 2, ?, ?)";
            $orderStmt = $conn->prepare($orderSql);
            if (!$orderStmt) {
                throw new Exception($conn->error);
            }
            $orderStmt->bind_param('siss', $guestIdentifier, $paymentStatus, $shippingAddress, $notes);
            $orderStmt->execute();
            $orderId = $orderStmt->insert_id;
            $orderStmt->close();

            $conn->commit();
            header('Location: manage_order?order_id=' . urlencode($orderId) . '&created=1');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'The order could not be created: ' . $e->getMessage();
        }
    }
}

include 'header.php';
include 'page_menues.php';
?>

<title>Create Order</title>

<style>
    .create-order-shell { padding: 30px 0 60px; }
    .create-order-panel { background: #fff; border: 1px solid var(--sf-border); border-radius: 8px; padding: 22px; }
    .create-order-panel h1 { color: #28364B; }
    .form-help { color: #6d6270; font-size: 13px; }
    .manual-address-fields { display: none; }
    .manual-address-fields.is-open { display: block; }
    .admin-address-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
    .admin-address-toggle {
        background: #fff;
        border: 3px double #CEBD88;
        border-radius: 0;
        color: #28364B;
        font-weight: 800;
        padding: 7px 12px;
    }
    .admin-address-toggle:hover,
    .admin-address-toggle:focus { background: #28364B; color: #CEBD88; }
</style>

<div class="container create-order-shell">
    <div class="create-order-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3" style="gap: 12px;">
            <div>
                <h1>Create Order For Customer</h1>
                <p class="form-help mb-0">Create the customer shell first. You will be taken to the admin cart to add sheet products and quantities.</p>
            </div>
            <a href="manage_orders" class="btn btn-outline-dark">Back to orders</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= cbCreateOrderText($message) ?></div>
        <?php endif; ?>

        <form method="post" action="create_order">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>First name</label>
                    <input type="text" name="billing_first_name" class="form-control" value="<?= cbCreateOrderText($old['billing_first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Last name</label>
                    <input type="text" name="billing_last_name" class="form-control" value="<?= cbCreateOrderText($old['billing_last_name'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="billing_email_address" class="form-control" value="<?= cbCreateOrderText($old['billing_email_address'] ?? '') ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Phone</label>
                    <input type="text" name="billing_phone_number" class="form-control" value="<?= cbCreateOrderText($old['billing_phone_number'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Delivery address</label>
                <input
                    type="text"
                    name="billing_street_address_1"
                    id="billing_street_address_1"
                    class="form-control"
                    value="<?= cbCreateOrderText($old['billing_street_address_1'] ?? '') ?>"
                    placeholder="Start typing the customer address"
                    autocomplete="off"
                >
                <div class="admin-address-actions">
                    <button type="button" class="admin-address-toggle" id="manual-address-toggle">Enter address manually</button>
                    <span class="form-help">Google Maps will fill city, province, country and postal code when available.</span>
                </div>
            </div>
            <div class="manual-address-fields<?= !empty($old['billing_street_address_2']) || !empty($old['billing_city']) || !empty($old['billing_province']) || !empty($old['billing_post_code']) ? ' is-open' : '' ?>" id="manual-address-fields">
                <div class="form-group">
                    <label>Apartment, unit, company, locker details</label>
                    <input type="text" name="billing_street_address_2" id="billing_street_address_2" class="form-control" value="<?= cbCreateOrderText($old['billing_street_address_2'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>City</label>
                        <input type="text" name="billing_city" id="billing_city" class="form-control" value="<?= cbCreateOrderText($old['billing_city'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Province</label>
                        <input type="text" name="billing_province" id="billing_province" class="form-control" value="<?= cbCreateOrderText($old['billing_province'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Postal code</label>
                        <input type="text" name="billing_post_code" id="billing_post_code" class="form-control" value="<?= cbCreateOrderText($old['billing_post_code'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="billing_country" id="billing_country" class="form-control" value="<?= cbCreateOrderText($old['billing_country'] ?? 'South Africa') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Payment status</label>
                <select name="payment_status" class="form-control">
                    <option value="0">Unpaid / waiting</option>
                    <option value="1" <?= (isset($old['payment_status']) && (int) $old['payment_status'] === 1) ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>
            <div class="form-group">
                <label>Internal order notes</label>
                <textarea name="order_notes" class="form-control" rows="4"><?= cbCreateOrderText($old['order_notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Create order and add products</button>
        </form>
    </div>
</div>

<script>
(function() {
    var manualFields = document.getElementById('manual-address-fields');
    var manualToggle = document.getElementById('manual-address-toggle');

    function showManualFields() {
        if (!manualFields) return;
        manualFields.classList.add('is-open');
        if (manualToggle) manualToggle.textContent = 'Manual address shown';
    }

    window.initSirFrancisAdminOrderAddressAutocomplete = function() {
        if (!window.google || !google.maps || !google.maps.places) {
            showManualFields();
            return;
        }

        var input = document.getElementById('billing_street_address_1');
        if (!input || input.dataset.mapsReady === '1') return;
        input.dataset.mapsReady = '1';

        function setField(id, value, overwrite) {
            var field = document.getElementById(id);
            if (!field || (!overwrite && field.value)) return;
            field.value = value || field.value;
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }

        var autocomplete = new google.maps.places.Autocomplete(input, {
            fields: ['address_components', 'formatted_address'],
            types: ['address']
        });

        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            var fields = {
                street_number: '',
                route: '',
                locality: '',
                postal_town: '',
                administrative_area_level_1: '',
                sublocality: '',
                sublocality_level_1: '',
                neighborhood: '',
                country: '',
                postal_code: ''
            };

            (place.address_components || []).forEach(function(component) {
                (component.types || []).forEach(function(type) {
                    if (Object.prototype.hasOwnProperty.call(fields, type)) {
                        fields[type] = component.long_name;
                    }
                });
            });

            var street = ((fields.street_number + ' ' + fields.route).replace(/\s+/g, ' ')).trim();
            var suburb = fields.sublocality_level_1 || fields.sublocality || fields.neighborhood || '';
            if (street) setField('billing_street_address_1', street, true);
            if (suburb) setField('billing_street_address_2', suburb, false);
            setField('billing_city', fields.locality || fields.postal_town, false);
            setField('billing_province', fields.administrative_area_level_1, false);
            setField('billing_country', fields.country, false);
            setField('billing_post_code', fields.postal_code, false);
            showManualFields();
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        manualFields = document.getElementById('manual-address-fields');
        manualToggle = document.getElementById('manual-address-toggle');
        if (manualToggle) {
            manualToggle.addEventListener('click', showManualFields);
        }

        if (window.google && google.maps && google.maps.places) {
            window.initSirFrancisAdminOrderAddressAutocomplete();
        }

        <?php if ($googlePlacesApiKey === ''): ?>
        showManualFields();
        <?php endif; ?>
    });
})();
</script>
<?php if ($googlePlacesApiKey !== ''): ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?=cbCreateOrderText($googlePlacesApiKey)?>&libraries=places&callback=initSirFrancisAdminOrderAddressAutocomplete"></script>
<?php endif; ?>

<?php include '../footer.php'; ?>
