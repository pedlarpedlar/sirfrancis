<?php
$use_corporate_nav = true;
$load_shopping_nav = false;
include 'session_logins.php';
require_once __DIR__ . '/find_agent_helpers.php';

$sf_agent_regions = function_exists('sfFindAgentRegions') ? sfFindAgentRegions($conn ?? null) : [];
$sf_google_maps_api_key = '';
if (isset($conn) && $conn instanceof mysqli) {
    $sf_maps_column_check = $conn->query("SHOW COLUMNS FROM admin_website_settings LIKE 'google_maps_api_key'");
    if ($sf_maps_column_check && $sf_maps_column_check->num_rows > 0) {
        $sf_maps_result = $conn->query("SELECT google_maps_api_key FROM admin_website_settings LIMIT 1");
        if ($sf_maps_result && ($sf_maps_row = $sf_maps_result->fetch_assoc())) {
            $sf_google_maps_api_key = trim((string) ($sf_maps_row['google_maps_api_key'] ?? ''));
        }
    }
}
if ($sf_google_maps_api_key === '') {
    $sf_google_maps_api_key = trim((string) getenv('SIRFRANCIS_GOOGLE_MAPS_API_KEY'));
}

$page_url_canonical = 'https://sirfrancis.co.za/find-agent';
$page_url_og = $page_url_canonical;
$title_og = 'Find an Agent - Sir Francis';
$description_meta = 'Find a Sir Francis agent by South African region, or suggest a new agent in your area.';
$description_og = $description_meta;
$image_url_og = 'https://sirfrancis.co.za/assets/img/og/find-agent-social.png';

include 'header.php';
?>

<title>Find an Agent - Sir Francis</title>
<link rel="canonical" href="<?=htmlspecialchars($page_url_canonical, ENT_QUOTES, 'UTF-8')?>">
<meta name="description" content="<?=htmlspecialchars($description_meta, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:title" content="<?=htmlspecialchars($title_og, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:description" content="<?=htmlspecialchars($description_og, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:image" content="<?=htmlspecialchars($image_url_og, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:image:width" content="1731">
<meta property="og:image:height" content="909">
<meta property="og:url" content="<?=htmlspecialchars($page_url_og, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?=htmlspecialchars($title_og, ENT_QUOTES, 'UTF-8')?>">
<meta name="twitter:description" content="<?=htmlspecialchars($description_og, ENT_QUOTES, 'UTF-8')?>">
<meta name="twitter:image" content="<?=htmlspecialchars($image_url_og, ENT_QUOTES, 'UTF-8')?>">

<?php include 'page_menues.php'; ?>

<style>
  .sf-agent-page {
    background: #f8f5ee;
    color: #172235;
  }

  .sf-agent-page .sf-agent-hero {
    background: #172235 !important;
    color: #fffaf0 !important;
    padding: 54px 0 34px;
  }

  .sf-agent-page .sf-agent-hero > .container {
    background: #172235 !important;
    color: #fffaf0 !important;
  }

  .sf-agent-page .sf-agent-hero h1 {
    color: #fffaf0 !important;
    font-family: "Playfair Display", Georgia, serif;
    font-size: clamp(2.35rem, 5vw, 4.4rem);
    line-height: 1.05;
    margin: 0 0 12px;
  }

  .sf-agent-page .sf-agent-hero p {
    color: #fffaf0 !important;
    font-size: 1.05rem;
    font-weight: 600;
    line-height: 1.7;
    max-width: 780px;
  }

  .sf-agent-page .sf-agent-hero .sf-agent-kicker {
    color: #CEBD88 !important;
  }

  .sf-agent-shell {
    padding: 36px 0 68px;
  }

  .sf-agent-layout {
    display: grid;
    gap: 22px;
    grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
    align-items: start;
  }

  .sf-agent-panel,
  .sf-agent-result,
  .sf-agent-suggest {
    background: #fff;
    border: 1px solid #e3d6bd;
    border-radius: 0;
    box-shadow: 0 16px 38px rgba(23, 34, 53, .08);
  }

  .sf-agent-panel {
    padding: clamp(18px, 3vw, 30px);
  }

  .sf-agent-panel h2,
  .sf-agent-result h2,
  .sf-agent-suggest h2 {
    color: #172235;
    font-family: "Playfair Display", Georgia, serif;
    font-size: 1.55rem;
    line-height: 1.2;
    margin: 0 0 10px;
  }

  .sf-agent-panel p,
  .sf-agent-result p,
  .sf-agent-suggest p {
    color: #574f45;
    line-height: 1.65;
  }

  .sf-agent-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 18px 0 20px;
  }

  .sf-agent-btn,
  .sf-agent-region-list button {
    background: #172235;
    border: 3px double #CEBD88;
    border-radius: 0;
    color: #CEBD88;
    cursor: pointer;
    font-weight: 900;
    line-height: 1.2;
    padding: 11px 14px;
  }

  .sf-agent-btn.secondary {
    background: #fff;
    color: #172235;
  }

  .sf-agent-btn:hover,
  .sf-agent-btn:focus,
  .sf-agent-region-list button:hover,
  .sf-agent-region-list button:focus,
  .sf-agent-region-list button.is-active {
    background: #CEBD88;
    color: #172235;
    outline: 0;
  }

  .sf-agent-map-wrap {
    display: grid;
    gap: 20px;
    grid-template-columns: minmax(260px, 1fr) minmax(220px, .55fr);
    margin-top: 18px;
  }

  .sf-agent-map-tools {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 14px 0 10px;
  }

  .sf-agent-map {
    background: #f7f1df;
    border: 1px solid #d8c895;
    min-height: 420px;
    padding: 14px;
    position: relative;
  }

  .sf-agent-google-map {
    display: none;
    min-height: 420px;
    width: 100%;
  }

  .sf-agent-map.is-google-ready {
    padding: 0;
  }

  .sf-agent-map.is-google-ready .sf-agent-static-map {
    background: rgba(248, 245, 238, .95);
    border: 1px solid #CEBD88;
    bottom: 12px;
    box-shadow: 0 10px 26px rgba(23, 34, 53, .18);
    max-width: 270px;
    padding: 8px;
    position: absolute;
    right: 12px;
    width: 38%;
    z-index: 2;
  }

  .sf-agent-map.is-boundary-ready .sf-agent-static-map {
    display: none;
  }

  .sf-agent-map.is-google-ready .sf-agent-google-map {
    display: block;
  }

  .sf-agent-map svg {
    display: block;
    height: auto;
    width: 100%;
  }

  .sf-region {
    cursor: pointer;
    fill: #fbfaf6;
    stroke: #172235;
    stroke-width: 2;
    transition: fill .2s ease, transform .2s ease;
  }

  .sf-region:hover,
  .sf-region:focus,
  .sf-region.is-active {
    fill: #CEBD88;
    outline: 0;
  }

  .sf-region-label {
    fill: #172235;
    font-size: 15px;
    font-weight: 800;
    pointer-events: none;
  }

  .sf-agent-region-list {
    display: grid;
    gap: 8px;
  }

  .sf-agent-region-list button {
    background: #fff;
    color: #172235;
    padding: 9px 10px;
    text-align: left;
  }

  .sf-agent-result {
    padding: 22px;
    position: sticky;
    top: 96px;
  }

  .sf-agent-kicker {
    color: #8f7a45;
    display: block;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .06em;
    margin-bottom: 7px;
    text-transform: uppercase;
  }

  .sf-agent-card {
    border-top: 1px solid #eadfca;
    margin-top: 16px;
    padding-top: 16px;
  }

  .sf-agent-card h3 {
    color: #172235;
    font-size: 1.12rem;
    line-height: 1.25;
    margin: 0 0 8px;
  }

  .sf-agent-meta {
    color: #5f5549;
    line-height: 1.6;
    margin: 0 0 12px;
  }

  .sf-agent-city-list {
    border-top: 1px solid #eadfca;
    display: grid;
    gap: 8px;
    margin-top: 14px;
    padding-top: 14px;
  }

  .sf-agent-city-list strong {
    color: #172235;
    display: block;
    font-size: 13px;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .sf-agent-city-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .sf-agent-city-button {
    background: #fff;
    border: 3px double #CEBD88;
    border-radius: 0;
    color: #172235;
    cursor: pointer;
    font-size: 13px;
    font-weight: 900;
    padding: 8px 10px;
  }

  .sf-agent-city-button:hover,
  .sf-agent-city-button:focus,
  .sf-agent-city-button.is-active {
    background: #CEBD88;
    color: #172235;
    outline: 0;
  }

  .sf-agent-suggest {
    margin-top: 22px;
    padding: 22px;
  }

  .sf-agent-notice {
    background: #fff9e8;
    border: 1px solid #dfc779;
    color: #514222;
    display: none;
    font-size: 13px;
    line-height: 1.45;
    margin: 12px 0 0;
    padding: 10px 12px;
  }

  .sf-agent-notice.is-visible {
    display: block;
  }

  .sf-agent-modal {
    align-items: center;
    background: rgba(23, 34, 53, .74);
    bottom: 0;
    display: none;
    justify-content: center;
    left: 0;
    padding: 18px;
    position: fixed;
    right: 0;
    top: 0;
    z-index: 9999;
  }

  .sf-agent-modal.is-visible {
    display: flex;
  }

  .sf-agent-modal-dialog {
    background: #fff;
    border: 3px double #CEBD88;
    color: #172235;
    max-height: 86vh;
    max-width: 720px;
    overflow: auto;
    padding: 22px;
    position: relative;
    width: min(100%, 720px);
  }

  .sf-agent-modal-close {
    background: #172235;
    border: 3px double #CEBD88;
    color: #CEBD88;
    cursor: pointer;
    font-size: 22px;
    font-weight: 900;
    height: 38px;
    line-height: 1;
    position: absolute;
    right: 14px;
    top: 14px;
    width: 38px;
  }

  .sf-agent-modal h2 {
    color: #172235;
    font-family: "Playfair Display", Georgia, serif;
    font-size: 1.65rem;
    margin: 0 46px 6px 0;
  }

  .sf-agent-modal-list {
    display: grid;
    gap: 12px;
    margin-top: 16px;
  }

  .sf-agent-modal-card {
    border: 1px solid #e3d6bd;
    padding: 14px;
  }

  .sf-agent-modal-card h3 {
    color: #172235;
    font-size: 1.05rem;
    margin: 0 0 6px;
  }

  .sf-agent-modal-card p {
    color: #574f45;
    line-height: 1.55;
    margin: 0 0 8px;
  }

  @media (max-width: 991px) {
    .sf-agent-layout,
    .sf-agent-map-wrap {
      grid-template-columns: 1fr;
    }

    .sf-agent-result {
      position: static;
    }

    .sf-agent-map.is-google-ready .sf-agent-static-map {
      bottom: 10px;
      max-width: 210px;
      right: 10px;
      width: 48%;
    }
  }
</style>

<main class="sf-agent-page">
  <section class="sf-agent-hero" style="background:#172235;color:#fffaf0;">
    <div class="container" style="background:#172235;color:#fffaf0;">
      <span class="sf-agent-kicker">Regional supply support</span>
      <h1>Find an agent</h1>
      <p>Connect with a Sir Francis agent in your region for product guidance, trade supply support and local service.</p>
      <div class="sf-agent-actions">
        <a class="sf-agent-btn secondary" href="#suggest-agent">Suggest an agent</a>
      </div>
    </div>
  </section>

  <section class="sf-agent-shell">
    <div class="container sf-agent-layout">
      <div class="sf-agent-panel">
        <span class="sf-agent-kicker">Regional agent network</span>
        <h2>Select your region</h2>
        <p>Click a region to see the current Sir Francis agent details. Regions without a local agent will route you to the nearest available support point and invite you to suggest a supplier.</p>
        <div class="sf-agent-map-tools">
          <button type="button" class="sf-agent-btn" id="sf-agent-location-btn">Use my location</button>
          <button type="button" class="sf-agent-btn secondary" id="sf-agent-view-current-btn">View selected agents</button>
        </div>
        <div class="sf-agent-notice" id="sf-agent-location-note" aria-live="polite"></div>

        <div class="sf-agent-map-wrap">
          <div class="sf-agent-map" aria-label="South African regions">
            <div class="sf-agent-google-map" id="sf-agent-google-map" aria-label="Google map of Sir Francis agents"></div>
            <svg class="sf-agent-static-map" viewBox="0 0 620 520" role="img" aria-labelledby="sf-agent-map-title">
              <title id="sf-agent-map-title">Clickable map of South African regions</title>
              <path class="sf-region" tabindex="0" data-region="western-cape" d="M102 362 L222 326 L306 376 L292 468 L178 488 L90 446 Z"></path>
              <path class="sf-region" tabindex="0" data-region="northern-cape" d="M86 124 L264 72 L374 166 L306 376 L222 326 L102 362 L54 252 Z"></path>
              <path class="sf-region" tabindex="0" data-region="eastern-cape" d="M306 376 L398 324 L506 362 L492 430 L374 474 L292 468 Z"></path>
              <path class="sf-region" tabindex="0" data-region="free-state" d="M374 166 L466 180 L496 282 L398 324 L306 376 Z"></path>
              <path class="sf-region" tabindex="0" data-region="kwazulu-natal" d="M496 282 L568 306 L548 392 L492 430 L506 362 L398 324 Z"></path>
              <path class="sf-region" tabindex="0" data-region="north-west" d="M264 72 L390 58 L466 180 L374 166 Z"></path>
              <path class="sf-region" tabindex="0" data-region="gauteng" d="M390 58 L462 72 L486 134 L466 180 Z"></path>
              <path class="sf-region" tabindex="0" data-region="mpumalanga" d="M462 72 L552 82 L570 182 L496 216 L466 180 L486 134 Z"></path>
              <path class="sf-region" tabindex="0" data-region="limpopo" d="M390 58 L444 18 L554 32 L552 82 L462 72 Z"></path>
              <text class="sf-region-label" x="154" y="418">Western Cape</text>
              <text class="sf-region-label" x="154" y="214">Northern Cape</text>
              <text class="sf-region-label" x="360" y="408">Eastern Cape</text>
              <text class="sf-region-label" x="382" y="252">Free State</text>
              <text class="sf-region-label" x="486" y="350">KZN</text>
              <text class="sf-region-label" x="306" y="118">North West</text>
              <text class="sf-region-label" x="432" y="118">Gauteng</text>
              <text class="sf-region-label" x="486" y="158">Mpumalanga</text>
              <text class="sf-region-label" x="442" y="54">Limpopo</text>
            </svg>
          </div>

          <div class="sf-agent-region-list" aria-label="Choose region">
            <button type="button" data-region="kwazulu-natal">KwaZulu-Natal</button>
            <button type="button" data-region="gauteng">Gauteng</button>
            <button type="button" data-region="western-cape">Western Cape</button>
            <button type="button" data-region="eastern-cape">Eastern Cape</button>
            <button type="button" data-region="free-state">Free State</button>
            <button type="button" data-region="north-west">North West</button>
            <button type="button" data-region="mpumalanga">Mpumalanga</button>
            <button type="button" data-region="limpopo">Limpopo</button>
            <button type="button" data-region="northern-cape">Northern Cape</button>
          </div>
        </div>

        <div class="sf-agent-suggest" id="suggest-agent">
          <span class="sf-agent-kicker">No agent in your area?</span>
          <h2>Suggest an agent</h2>
          <p>If there is no Sir Francis agent close to you, suggest a trusted local business or apply to become a supplier yourself.</p>
          <div class="sf-agent-actions">
            <a class="sf-agent-btn" href="contact?subject=Suggest%20an%20agent">Suggest an agent</a>
            <a class="sf-agent-btn secondary" href="contact?subject=Become%20a%20Sir%20Francis%20supplier">Become a supplier</a>
          </div>
        </div>
      </div>

      <aside class="sf-agent-result" aria-live="polite">
        <span class="sf-agent-kicker" id="sf-agent-region-label">Default region</span>
        <h2 id="sf-agent-title">Durban support point</h2>
        <p id="sf-agent-summary">Durban is currently the default Sir Francis agent region for South African enquiries.</p>
        <div class="sf-agent-card">
          <h3 id="sf-agent-name">Sir Francis Durban</h3>
          <p class="sf-agent-meta" id="sf-agent-details">KwaZulu-Natal regional support for retail, wholesale, private labelling and procurement enquiries.</p>
          <div class="sf-agent-actions">
            <a class="sf-agent-btn" id="sf-agent-contact" href="contact?subject=Agent%20enquiry%20-%20KwaZulu-Natal">Contact this agent</a>
            <a class="sf-agent-btn secondary" id="sf-agent-map-link" href="https://www.google.com/maps/search/?api=1&amp;query=Durban%2C%20South%20Africa" target="_blank" rel="noopener noreferrer">Open map</a>
          </div>
          <div class="sf-agent-city-list" id="sf-agent-city-list"></div>
        </div>
      </aside>
    </div>
  </section>
</main>

<div class="sf-agent-modal" id="sf-agent-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="sf-agent-modal-title">
  <div class="sf-agent-modal-dialog">
    <button type="button" class="sf-agent-modal-close" id="sf-agent-modal-close" aria-label="Close agent details">&times;</button>
    <span class="sf-agent-kicker" id="sf-agent-modal-kicker">Available agents</span>
    <h2 id="sf-agent-modal-title">Agents nearby</h2>
    <p class="sf-agent-meta" id="sf-agent-modal-summary"></p>
    <div class="sf-agent-modal-list" id="sf-agent-modal-list"></div>
  </div>
</div>

<script>
(function() {
  var agents = <?=json_encode($sf_agent_regions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>;
  var googleMap = null;
  var agentMarkers = [];
  var activeRegion = 'kwazulu-natal';
  var regionFeatures = {};
  var provinceGeoJsonUrl = 'assets/data/south-africa-provinces.geojson';
  var provinceNameMap = {
    'Eastern Cape': 'eastern-cape',
    'Free State': 'free-state',
    'Gauteng': 'gauteng',
    'KwaZulu-Natal': 'kwazulu-natal',
    'Limpopo': 'limpopo',
    'Mpumalanga': 'mpumalanga',
    'North West': 'north-west',
    'Northern Cape': 'northern-cape',
    'Nothern Cape': 'northern-cape',
    'Western Cape': 'western-cape'
  };

  function provinceFromCoordinates(lat, lng) {
    if (lat <= -26.8 && lng <= 24.8) return 'western-cape';
    if (lat <= -30 && lng > 24.8) return 'eastern-cape';
    if (lat <= -26.5 && lng > 28.5) return 'kwazulu-natal';
    if (lat > -26.7 && lat < -25 && lng > 27.2 && lng < 29.5) return 'gauteng';
    if (lat > -25.8 && lng > 28.2 && lng < 32) return 'mpumalanga';
    if (lat > -25 && lng > 26.8) return 'limpopo';
    if (lat > -28.8 && lng < 26.8) return 'north-west';
    if (lat <= -26.5 && lat > -30.5 && lng >= 24.5 && lng <= 29.4) return 'free-state';
    return 'northern-cape';
  }

  function setActiveRegion(region, selectedCityName) {
    var agent = agents[region] || agents['kwazulu-natal'];
    if (!agent) return;
    activeRegion = region;
    document.querySelectorAll('[data-region]').forEach(function(item) {
      item.classList.toggle('is-active', item.getAttribute('data-region') === region);
    });
    document.getElementById('sf-agent-region-label').textContent = agent.direct ? 'Available region' : agent.label;
    document.getElementById('sf-agent-title').textContent = agent.title;
    document.getElementById('sf-agent-summary').textContent = agent.direct
      ? 'This region has active Sir Francis agent details available.'
      : 'We do not have a listed agent in this region yet, but we can still route your enquiry.';
    document.getElementById('sf-agent-name').textContent = agent.name;
    document.getElementById('sf-agent-details').textContent = agent.details;
    document.getElementById('sf-agent-contact').href = 'contact?subject=' + encodeURIComponent('Agent enquiry - ' + agent.label);
    document.getElementById('sf-agent-map-link').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(agent.query);
    renderCityAgents(agent, selectedCityName);
    refreshProvinceStyles();
    focusMapOnRegion(region);
  }

  function renderCityAgents(agent, selectedCityName) {
    var cityList = document.getElementById('sf-agent-city-list');
    var cities = Array.isArray(agent.city_agents) ? agent.city_agents : [];
    if (!cityList) return;

    if (!cities.length) {
      cityList.innerHTML = '<strong>City view</strong><p class="sf-agent-meta">No city agents are listed for this region yet.</p>';
      return;
    }

    cityList.innerHTML = '<strong>City view</strong><div class="sf-agent-city-buttons"></div>';
    var buttonWrap = cityList.querySelector('.sf-agent-city-buttons');
    var selectedCity = cities[0];
    cities.forEach(function(cityAgent, index) {
      var isSelected = selectedCityName && cityAgent.city === selectedCityName;
      if (isSelected) selectedCity = cityAgent;
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'sf-agent-city-button' + ((isSelected || (!selectedCityName && index === 0)) ? ' is-active' : '');
      button.textContent = cityAgent.city || cityAgent.name || 'City agent';
      button.addEventListener('click', function() {
        buttonWrap.querySelectorAll('.sf-agent-city-button').forEach(function(item) {
          item.classList.remove('is-active');
        });
        button.classList.add('is-active');
        setCityAgent(agent, cityAgent);
        focusMapOnCity(cityAgent);
        openAgentsModal(activeRegion, cityAgent.city);
      });
      buttonWrap.appendChild(button);
    });
    setCityAgent(agent, selectedCity);
  }

  function setCityAgent(regionAgent, cityAgent) {
    document.getElementById('sf-agent-name').textContent = cityAgent.name || regionAgent.name;
    document.getElementById('sf-agent-details').textContent = cityAgent.details || regionAgent.details;
    var contactButton = document.getElementById('sf-agent-contact');
    var phoneHref = agentPhoneLink(cityAgent.phone);
    contactButton.href = phoneHref ? ('tel:' + encodeURIComponent(phoneHref)) : ('contact?subject=' + encodeURIComponent(cityAgent.contact_subject || ('Agent enquiry - ' + regionAgent.label)));
    contactButton.textContent = phoneHref ? 'Call this agent' : 'Contact Sir Francis';
    document.getElementById('sf-agent-map-link').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(cityAgent.query || regionAgent.query);
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function(character) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
    });
  }

  function agentPhoneLink(phone) {
    return String(phone || '').replace(/[^\d+]/g, '');
  }

  function openAgentsModal(region, selectedCityName) {
    var agent = agents[region] || agents['kwazulu-natal'];
    var modal = document.getElementById('sf-agent-modal');
    var list = document.getElementById('sf-agent-modal-list');
    if (!agent || !modal || !list) return;

    var cities = Array.isArray(agent.city_agents) ? agent.city_agents : [];
    document.getElementById('sf-agent-modal-kicker').textContent = cities.length ? 'Agents found' : 'No local agent listed';
    document.getElementById('sf-agent-modal-title').textContent = agent.label + ' agents';
    document.getElementById('sf-agent-modal-summary').textContent = cities.length
      ? 'Choose an agent below to phone, open their map location, or send an enquiry.'
      : 'There is no active agent listed for this region yet. You can still contact Sir Francis or suggest a supplier.';

    if (!cities.length) {
      list.innerHTML = '<div class="sf-agent-modal-card"><h3>Sir Francis support</h3><p>We can route your enquiry while a local agent is being appointed.</p><div class="sf-agent-actions"><a class="sf-agent-btn" href="contact?subject=' + encodeURIComponent('Agent enquiry - ' + agent.label) + '">Contact Sir Francis</a><a class="sf-agent-btn secondary" href="#suggest-agent">Suggest an agent</a></div></div>';
    } else {
      list.innerHTML = cities.map(function(cityAgent) {
        var phoneHref = agentPhoneLink(cityAgent.phone);
        var isSelected = selectedCityName && selectedCityName === cityAgent.city;
        return '<div class="sf-agent-modal-card' + (isSelected ? ' is-active' : '') + '">'
          + '<h3>' + escapeHtml(cityAgent.name || cityAgent.city || 'Agent') + '</h3>'
          + '<p><strong>' + escapeHtml(cityAgent.city || agent.label) + '</strong></p>'
          + '<p>' + escapeHtml(cityAgent.address || cityAgent.details || '') + '</p>'
          + (cityAgent.phone ? '<p><strong>Phone:</strong> ' + escapeHtml(cityAgent.phone) + '</p>' : '')
          + '<div class="sf-agent-actions">'
          + (phoneHref ? '<a class="sf-agent-btn" href="tel:' + encodeURIComponent(phoneHref) + '">Call agent</a>' : '')
          + '<a class="sf-agent-btn secondary" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(cityAgent.query || cityAgent.address || '') + '">Open map</a>'
          + '<a class="sf-agent-btn secondary" href="contact?subject=' + encodeURIComponent(cityAgent.contact_subject || ('Agent enquiry - ' + agent.label)) + '">Send enquiry</a>'
          + '</div></div>';
      }).join('');
    }

    modal.classList.add('is-visible');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeAgentsModal() {
    var modal = document.getElementById('sf-agent-modal');
    if (!modal) return;
    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
  }

  function hasCoordinates(cityAgent) {
    return cityAgent && isFinite(Number(cityAgent.lat)) && isFinite(Number(cityAgent.lng));
  }

  function focusMapOnCity(cityAgent) {
    if (!googleMap || !hasCoordinates(cityAgent)) return;
    googleMap.setCenter({ lat: Number(cityAgent.lat), lng: Number(cityAgent.lng) });
    googleMap.setZoom(14);
  }

  function fitMapToRegionBoundary(region) {
    if (!googleMap || !window.google || !google.maps || !regionFeatures[region]) return false;
    var bounds = new google.maps.LatLngBounds();
    regionFeatures[region].getGeometry().forEachLatLng(function(latLng) {
      bounds.extend(latLng);
    });
    if (bounds.isEmpty && bounds.isEmpty()) return false;
    googleMap.fitBounds(bounds);
    google.maps.event.addListenerOnce(googleMap, 'idle', function() {
      if (googleMap.getZoom() > 8) {
        googleMap.setZoom(8);
      }
    });
    return true;
  }

  function focusMapOnRegion(region) {
    if (!googleMap || !window.google || !google.maps) return;
    var agent = agents[region];
    var cities = agent && Array.isArray(agent.city_agents) ? agent.city_agents.filter(hasCoordinates) : [];
    if (!cities.length) {
      fitMapToRegionBoundary(region);
      return;
    }
    if (cities.length === 1) {
      focusMapOnCity(cities[0]);
      return;
    }
    var bounds = new google.maps.LatLngBounds();
    cities.forEach(function(cityAgent) {
      bounds.extend({ lat: Number(cityAgent.lat), lng: Number(cityAgent.lng) });
    });
    googleMap.fitBounds(bounds);
    google.maps.event.addListenerOnce(googleMap, 'idle', function() {
      if (googleMap.getZoom() > 10) {
        googleMap.setZoom(10);
      }
    });
  }

  window.initSirFrancisAgentMap = function() {
    var mapNode = document.getElementById('sf-agent-google-map');
    if (!mapNode || !window.google || !google.maps) return;
    googleMap = new google.maps.Map(mapNode, {
      center: { lat: -29.8587, lng: 31.0218 },
      zoom: 6,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
      clickableIcons: false
    });
    mapNode.parentElement.classList.add('is-google-ready');
    initProvinceBoundaries(mapNode);
    Object.keys(agents).forEach(function(region) {
      var agent = agents[region];
      var cities = Array.isArray(agent.city_agents) ? agent.city_agents : [];
      cities.forEach(function(cityAgent) {
        if (!hasCoordinates(cityAgent)) return;
        var marker = new google.maps.Marker({
          map: googleMap,
          position: { lat: Number(cityAgent.lat), lng: Number(cityAgent.lng) },
          title: cityAgent.city || agent.label
        });
        marker.addListener('click', function() {
          setActiveRegion(region, cityAgent.city);
          focusMapOnCity(cityAgent);
          openAgentsModal(region, cityAgent.city);
        });
        agentMarkers.push(marker);
      });
    });
    focusMapOnRegion(activeRegion);
  };

  function featureRegion(feature) {
    var provinceName = feature.getProperty('shapeName') || feature.getProperty('name');
    return provinceNameMap[provinceName] || '';
  }

  function refreshProvinceStyles() {
    if (!googleMap || !googleMap.data) return;
    googleMap.data.setStyle(function(feature) {
      var region = featureRegion(feature);
      var isActive = region === activeRegion;
      return {
        fillColor: isActive ? '#CEBD88' : '#172235',
        fillOpacity: isActive ? 0.28 : 0.12,
        strokeColor: isActive ? '#CEBD88' : '#172235',
        strokeOpacity: 0.9,
        strokeWeight: isActive ? 2.4 : 1.4,
        clickable: true,
        zIndex: isActive ? 2 : 1
      };
    });
  }

  function initProvinceBoundaries(mapNode) {
    if (!googleMap || !googleMap.data) return;
    googleMap.data.loadGeoJson(provinceGeoJsonUrl, null, function(features) {
      features.forEach(function(feature) {
        var region = featureRegion(feature);
        if (region) regionFeatures[region] = feature;
      });
      mapNode.parentElement.classList.add('is-boundary-ready');
      refreshProvinceStyles();
      focusMapOnRegion(activeRegion);
    });
    googleMap.data.addListener('click', function(event) {
      var region = featureRegion(event.feature);
      if (region) {
        setActiveRegion(region);
        openAgentsModal(region);
      }
    });
    googleMap.data.addListener('mouseover', function(event) {
      googleMap.data.overrideStyle(event.feature, {
        fillOpacity: 0.34,
        strokeColor: '#CEBD88',
        strokeWeight: 2.4
      });
    });
    googleMap.data.addListener('mouseout', function(event) {
      googleMap.data.revertStyle(event.feature);
    });
  }

  document.querySelectorAll('[data-region]').forEach(function(item) {
    item.addEventListener('click', function() {
      setActiveRegion(item.getAttribute('data-region'));
    });
    item.addEventListener('keydown', function(event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        setActiveRegion(item.getAttribute('data-region'));
      }
    });
  });

  var locationButton = document.getElementById('sf-agent-location-btn');
  var locationNote = document.getElementById('sf-agent-location-note');
  var viewCurrentButton = document.getElementById('sf-agent-view-current-btn');
  var modalClose = document.getElementById('sf-agent-modal-close');
  var modal = document.getElementById('sf-agent-modal');
  if (viewCurrentButton) {
    viewCurrentButton.addEventListener('click', function() {
      openAgentsModal(activeRegion);
    });
  }
  if (modalClose) {
    modalClose.addEventListener('click', closeAgentsModal);
  }
  if (modal) {
    modal.addEventListener('click', function(event) {
      if (event.target === modal) closeAgentsModal();
    });
  }
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') closeAgentsModal();
  });
  if (locationButton && navigator.geolocation) {
    locationButton.addEventListener('click', function() {
      locationButton.disabled = true;
      locationButton.textContent = 'Checking location...';
      locationNote.textContent = 'Your browser will ask permission to share your approximate location.';
      locationNote.classList.add('is-visible');
      navigator.geolocation.getCurrentPosition(function(position) {
        var region = provinceFromCoordinates(position.coords.latitude, position.coords.longitude);
        setActiveRegion(region);
        openAgentsModal(region);
        locationButton.disabled = false;
        locationButton.textContent = 'Use my location';
        locationNote.textContent = 'Closest region selected. If this looks wrong, choose your region manually on the map.';
      }, function() {
        locationButton.disabled = false;
        locationButton.textContent = 'Use my location';
        locationNote.textContent = 'Location access was not available. Please choose your region on the map.';
        locationNote.classList.add('is-visible');
      }, { enableHighAccuracy: false, timeout: 9000, maximumAge: 600000 });
    });
  } else if (locationButton) {
    locationButton.addEventListener('click', function() {
      locationNote.textContent = 'Your browser does not support location lookup. Please choose your region on the map.';
      locationNote.classList.add('is-visible');
    });
  }

  setActiveRegion('kwazulu-natal');
})();
</script>

<?php if ($sf_google_maps_api_key !== ''): ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?=htmlspecialchars($sf_google_maps_api_key, ENT_QUOTES, 'UTF-8')?>&callback=initSirFrancisAgentMap&loading=async"></script>
<?php endif; ?>

<?php include 'footer.php'; ?>
