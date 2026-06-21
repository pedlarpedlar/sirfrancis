<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login?redirect=" . urlencode("agents"));
    exit();
}

include 'dbh.inc.php';
require_once __DIR__ . '/../find_agent_helpers.php';
require_once __DIR__ . '/website_settings_helpers.php';

sfAgentEnsureTable($conn);

$settings = cbWebsiteSettingsLoad($conn);
$googlePlacesApiKey = trim((string) ($settings['google_places_api_key'] ?? ''));
if ($googlePlacesApiKey === '') {
    $googlePlacesApiKey = trim((string) ($settings['google_maps_api_key'] ?? getenv('SIRFRANCIS_GOOGLE_MAPS_API_KEY')));
}
$regions = sfAgentRegionsBase();
$flash = null;

function sfAdminAgentFetch($conn, $id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }
    $result = $conn->query("SELECT * FROM sirfrancis_agents WHERE id = " . $id . " LIMIT 1");
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? 'save_agent');
        if ($action === 'delete_agent') {
            $id = (int) ($_POST['id'] ?? 0);
            $conn->query("DELETE FROM sirfrancis_agents WHERE id = " . $id);
            $flash = ['success' => true, 'message' => 'Agent deleted.'];
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $provinceSlug = trim((string) ($_POST['province_slug'] ?? ''));
            $city = trim((string) ($_POST['city'] ?? ''));
            $businessName = trim((string) ($_POST['business_name'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $googleMapsQuery = trim((string) ($_POST['google_maps_query'] ?? ''));
            $notes = trim((string) ($_POST['notes'] ?? ''));
            $lat = trim((string) ($_POST['lat'] ?? ''));
            $lng = trim((string) ($_POST['lng'] ?? ''));
            $isActive = !empty($_POST['is_active']) ? 1 : 0;
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);

            if (!isset($regions[$provinceSlug])) {
                throw new RuntimeException('Choose a valid province.');
            }
            if ($businessName === '') {
                throw new RuntimeException('Add the agent business name.');
            }
            if ($address === '') {
                throw new RuntimeException('Add the Google Maps address.');
            }
            if ($phone === '') {
                throw new RuntimeException('Add the agent phone number.');
            }
            if ($city === '') {
                $city = $regions[$provinceSlug]['label'];
            }
            if ($googleMapsQuery === '') {
                $googleMapsQuery = $address;
            }
            $latSql = is_numeric($lat) ? (float) $lat : null;
            $lngSql = is_numeric($lng) ? (float) $lng : null;

            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE sirfrancis_agents SET province_slug = ?, city = ?, business_name = ?, address = ?, phone = ?, google_maps_query = ?, lat = ?, lng = ?, notes = ?, is_active = ?, sort_order = ? WHERE id = ?");
                if (!$stmt) {
                    throw new RuntimeException($conn->error);
                }
                $stmt->bind_param('ssssssddsiii', $provinceSlug, $city, $businessName, $address, $phone, $googleMapsQuery, $latSql, $lngSql, $notes, $isActive, $sortOrder, $id);
                $stmt->execute();
                $stmt->close();
                $flash = ['success' => true, 'message' => 'Agent updated.'];
            } else {
                $stmt = $conn->prepare("INSERT INTO sirfrancis_agents (province_slug, city, business_name, address, phone, google_maps_query, lat, lng, notes, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new RuntimeException($conn->error);
                }
                $stmt->bind_param('ssssssddsii', $provinceSlug, $city, $businessName, $address, $phone, $googleMapsQuery, $latSql, $lngSql, $notes, $isActive, $sortOrder);
                $stmt->execute();
                $stmt->close();
                $flash = ['success' => true, 'message' => 'Agent saved.'];
            }
        }
    } catch (Throwable $e) {
        $flash = ['success' => false, 'message' => $e->getMessage()];
    }
}

$editing = sfAdminAgentFetch($conn, $_GET['edit'] ?? 0);
$agentsResult = $conn->query("SELECT * FROM sirfrancis_agents ORDER BY is_active DESC, province_slug ASC, sort_order ASC, business_name ASC");
$agents = [];
if ($agentsResult) {
    while ($row = $agentsResult->fetch_assoc()) {
        $agents[] = $row;
    }
}

include 'header.php';
include 'page_menues.php';
?>

<title>Find Agent Management - Sir Francis Admin</title>

<style>
  .agent-admin-wrap { padding:28px 0 70px; }
  .agent-admin-hero { background:var(--sf-navy); color:#fff; border-radius:0; padding:22px; margin-bottom:16px; }
  .agent-admin-hero h1 { color:var(--sf-gold); font-size:28px; margin:0 0 6px; }
  .agent-admin-panel { background:#fff; border:1px solid var(--sf-border); border-radius:0; padding:18px; }
  .agent-admin-grid { display:grid; gap:18px; grid-template-columns:minmax(320px, 440px) minmax(0, 1fr); }
  .agent-admin-table th { background:#172235; color:#CEBD88; font-size:12px; text-transform:uppercase; }
  .agent-admin-table td { vertical-align:top; }
  .agent-admin-actions { display:flex; flex-wrap:wrap; gap:8px; }
  .agent-admin-help { color:#6d6270; font-size:12px; line-height:1.45; margin-top:5px; }
  @media (max-width: 991px) { .agent-admin-grid { grid-template-columns:1fr; } }
</style>

<div class="container agent-admin-wrap">
  <div class="agent-admin-hero">
    <h1>Find an Agent</h1>
    <p class="mb-0">Add agent businesses, Google Maps addresses and phone numbers. Active agents appear as pins on the public Find an Agent page.</p>
  </div>

  <?php if ($flash): ?>
    <div class="alert <?= !empty($flash['success']) ? 'alert-success' : 'alert-danger' ?>"><?= sfAgentText($flash['message']) ?></div>
  <?php endif; ?>

  <div class="agent-admin-grid">
    <section class="agent-admin-panel">
      <h2><?= $editing ? 'Edit Agent' : 'Add Agent' ?></h2>
      <form method="post">
        <input type="hidden" name="action" value="save_agent">
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div class="form-group">
          <label>Province</label>
          <select class="form-control" name="province_slug" required>
            <?php foreach ($regions as $slug => $region): ?>
              <option value="<?= sfAgentText($slug) ?>" <?= (($editing['province_slug'] ?? 'kwazulu-natal') === $slug) ? 'selected' : '' ?>><?= sfAgentText($region['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>City / area</label>
          <input type="text" class="form-control" name="city" value="<?= sfAgentText($editing['city'] ?? '') ?>" placeholder="Durban">
        </div>
        <div class="form-group">
          <label>Agent business name</label>
          <input type="text" class="form-control" name="business_name" value="<?= sfAgentText($editing['business_name'] ?? '') ?>" required placeholder="Business name">
        </div>
        <div class="form-group">
          <label>Google Maps address</label>
          <input type="text" class="form-control" id="agent-address" name="address" value="<?= sfAgentText($editing['address'] ?? '') ?>" required placeholder="Start typing the business address">
          <div class="agent-admin-help">Choose the address from Google suggestions when available. This fills the map pin coordinates.</div>
        </div>
        <div class="form-group">
          <label>Phone number</label>
          <input type="text" class="form-control" name="phone" value="<?= sfAgentText($editing['phone'] ?? '') ?>" required placeholder="+27 ...">
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Latitude</label>
            <input type="text" class="form-control" id="agent-lat" name="lat" value="<?= sfAgentText($editing['lat'] ?? '') ?>">
          </div>
          <div class="form-group col-md-6">
            <label>Longitude</label>
            <input type="text" class="form-control" id="agent-lng" name="lng" value="<?= sfAgentText($editing['lng'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Map search query</label>
          <input type="text" class="form-control" id="agent-query" name="google_maps_query" value="<?= sfAgentText($editing['google_maps_query'] ?? '') ?>" placeholder="Defaults to the address">
        </div>
        <div class="form-group">
          <label>Internal notes</label>
          <textarea class="form-control" name="notes" rows="3"><?= sfAgentText($editing['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Sort order</label>
            <input type="number" class="form-control" name="sort_order" value="<?= sfAgentText($editing['sort_order'] ?? 0) ?>">
          </div>
          <div class="form-group col-md-6 d-flex align-items-end">
            <div class="form-check mb-2">
              <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= !isset($editing['is_active']) || (int) $editing['is_active'] === 1 ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_active">Active on website</label>
            </div>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Save Agent</button>
        <?php if ($editing): ?>
          <a class="btn btn-outline-secondary" href="agents">Cancel edit</a>
        <?php endif; ?>
      </form>
    </section>

    <section class="agent-admin-panel">
      <h2>Saved Agents</h2>
      <div class="table-responsive">
        <table class="table table-bordered agent-admin-table">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Region</th>
              <th>Address</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$agents): ?>
              <tr><td colspan="6">No agents saved yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($agents as $agent): ?>
              <tr>
                <td><strong><?= sfAgentText($agent['business_name']) ?></strong><br><small><?= sfAgentText($agent['city']) ?></small></td>
                <td><?= sfAgentText($regions[$agent['province_slug']]['label'] ?? $agent['province_slug']) ?></td>
                <td><?= sfAgentText($agent['address']) ?></td>
                <td><?= sfAgentText($agent['phone']) ?></td>
                <td><?= !empty($agent['is_active']) ? 'Active' : 'Hidden' ?></td>
                <td>
                  <div class="agent-admin-actions">
                    <a class="btn btn-sm btn-outline-primary" href="agents?edit=<?= (int) $agent['id'] ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this agent?');">
                      <input type="hidden" name="action" value="delete_agent">
                      <input type="hidden" name="id" value="<?= (int) $agent['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</div>

<script>
window.initSirFrancisAgentAdminPlaces = function() {
  var input = document.getElementById('agent-address');
  if (!input || !window.google || !google.maps || !google.maps.places) return;
  var autocomplete = new google.maps.places.Autocomplete(input, {
    componentRestrictions: { country: ['za'] },
    fields: ['formatted_address', 'geometry', 'name']
  });
  autocomplete.addListener('place_changed', function() {
    var place = autocomplete.getPlace();
    if (!place) return;
    var address = place.formatted_address || input.value;
    input.value = address;
    var query = document.getElementById('agent-query');
    if (query && !query.value) {
      query.value = address;
    }
    if (place.geometry && place.geometry.location) {
      document.getElementById('agent-lat').value = place.geometry.location.lat().toFixed(7);
      document.getElementById('agent-lng').value = place.geometry.location.lng().toFixed(7);
    }
  });
};
</script>
<?php if ($googlePlacesApiKey !== ''): ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= sfAgentText($googlePlacesApiKey) ?>&libraries=places&callback=initSirFrancisAgentAdminPlaces"></script>
<?php endif; ?>

<?php include __DIR__ . '/../footer.php'; ?>
