<?php
$use_corporate_nav = true;
$load_shopping_nav = false;
include 'session_logins.php';
require_once __DIR__ . '/find_agent_data.php';

$sf_agent_regions = function_exists('sfFindAgentRegions') ? sfFindAgentRegions() : [];

$page_url_canonical = 'https://sirfrancis.co.za/find-agent';
$page_url_og = $page_url_canonical;
$title_og = 'Find an Agent - Sir Francis';
$description_meta = 'Find a Sir Francis agent by South African region, or suggest a new agent in your area.';
$description_og = $description_meta;
$image_url_og = 'https://sirfrancis.co.za/assets/img/logo/logo.png';

include 'header.php';
?>

<title>Find an Agent - Sir Francis</title>
<link rel="canonical" href="<?=htmlspecialchars($page_url_canonical, ENT_QUOTES, 'UTF-8')?>">
<meta name="description" content="<?=htmlspecialchars($description_meta, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:title" content="<?=htmlspecialchars($title_og, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:description" content="<?=htmlspecialchars($description_og, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:image" content="<?=htmlspecialchars($image_url_og, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:url" content="<?=htmlspecialchars($page_url_og, ENT_QUOTES, 'UTF-8')?>">
<meta property="og:type" content="website">

<?php include 'page_menues.php'; ?>

<style>
  .sf-agent-page {
    background: #f8f5ee;
    color: #172235;
  }

  .sf-agent-hero {
    background:
      linear-gradient(90deg, rgba(10, 17, 29, .98), rgba(23, 34, 53, .94)),
      #111b2d;
    color: #fffaf0;
    padding: 54px 0 34px;
  }

  .sf-agent-hero h1 {
    color: #fffaf0;
    font-family: "Playfair Display", Georgia, serif;
    font-size: clamp(2.35rem, 5vw, 4.4rem);
    line-height: 1.05;
    margin: 0 0 12px;
    text-shadow: 0 2px 14px rgba(0, 0, 0, .45);
  }

  .sf-agent-hero p {
    color: #fff7e4;
    font-size: 1.05rem;
    font-weight: 600;
    line-height: 1.7;
    max-width: 780px;
    text-shadow: 0 1px 10px rgba(0, 0, 0, .34);
  }

  .sf-agent-hero .sf-agent-kicker {
    color: #CEBD88;
    text-shadow: 0 1px 8px rgba(0, 0, 0, .38);
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

  .sf-agent-map {
    background: #f7f1df;
    border: 1px solid #d8c895;
    min-height: 420px;
    padding: 14px;
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

  @media (max-width: 991px) {
    .sf-agent-layout,
    .sf-agent-map-wrap {
      grid-template-columns: 1fr;
    }

    .sf-agent-result {
      position: static;
    }
  }
</style>

<main class="sf-agent-page">
  <section class="sf-agent-hero">
    <div class="container">
      <span class="sf-agent-kicker">Regional supply support</span>
      <h1>Find an agent</h1>
      <p>Start with Durban as the default Sir Francis region, choose your province on the map, or allow location access so we can suggest the closest available region.</p>
      <div class="sf-agent-actions">
        <button type="button" class="sf-agent-btn" id="sf-agent-location-btn">Use my location</button>
        <a class="sf-agent-btn secondary" href="#suggest-agent">Suggest an agent</a>
      </div>
      <div class="sf-agent-notice" id="sf-agent-location-note" aria-live="polite"></div>
    </div>
  </section>

  <section class="sf-agent-shell">
    <div class="container sf-agent-layout">
      <div class="sf-agent-panel">
        <span class="sf-agent-kicker">Clickable region map</span>
        <h2>Select your region</h2>
        <p>Click a region to see the current Sir Francis agent details. Regions without a local agent will route you to the nearest available support point and invite you to suggest a supplier.</p>

        <div class="sf-agent-map-wrap">
          <div class="sf-agent-map" aria-label="South African regions">
            <svg viewBox="0 0 620 520" role="img" aria-labelledby="sf-agent-map-title">
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

<script>
(function() {
  var agents = <?=json_encode($sf_agent_regions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)?>;

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

  function setActiveRegion(region) {
    var agent = agents[region] || agents['kwazulu-natal'];
    if (!agent) return;
    document.querySelectorAll('[data-region]').forEach(function(item) {
      item.classList.toggle('is-active', item.getAttribute('data-region') === region);
    });
    document.getElementById('sf-agent-region-label').textContent = agent.direct ? 'Available region' : agent.label;
    document.getElementById('sf-agent-title').textContent = agent.title;
    document.getElementById('sf-agent-summary').textContent = agent.direct
      ? 'This region has the default Sir Francis support point available.'
      : 'We do not have a listed agent in this region yet, but we can still route your enquiry.';
    document.getElementById('sf-agent-name').textContent = agent.name;
    document.getElementById('sf-agent-details').textContent = agent.details;
    document.getElementById('sf-agent-contact').href = 'contact?subject=' + encodeURIComponent('Agent enquiry - ' + agent.label);
    document.getElementById('sf-agent-map-link').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(agent.query);
    renderCityAgents(agent);
  }

  function renderCityAgents(agent) {
    var cityList = document.getElementById('sf-agent-city-list');
    var cities = Array.isArray(agent.city_agents) ? agent.city_agents : [];
    if (!cityList) return;

    if (!cities.length) {
      cityList.innerHTML = '<strong>City view</strong><p class="sf-agent-meta">No city agents are listed for this region yet.</p>';
      return;
    }

    cityList.innerHTML = '<strong>City view</strong><div class="sf-agent-city-buttons"></div>';
    var buttonWrap = cityList.querySelector('.sf-agent-city-buttons');
    cities.forEach(function(cityAgent, index) {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'sf-agent-city-button' + (index === 0 ? ' is-active' : '');
      button.textContent = cityAgent.city || cityAgent.name || 'City agent';
      button.addEventListener('click', function() {
        buttonWrap.querySelectorAll('.sf-agent-city-button').forEach(function(item) {
          item.classList.remove('is-active');
        });
        button.classList.add('is-active');
        setCityAgent(agent, cityAgent);
      });
      buttonWrap.appendChild(button);
    });
    setCityAgent(agent, cities[0]);
  }

  function setCityAgent(regionAgent, cityAgent) {
    document.getElementById('sf-agent-name').textContent = cityAgent.name || regionAgent.name;
    document.getElementById('sf-agent-details').textContent = cityAgent.details || regionAgent.details;
    document.getElementById('sf-agent-contact').href = 'contact?subject=' + encodeURIComponent(cityAgent.contact_subject || ('Agent enquiry - ' + regionAgent.label));
    document.getElementById('sf-agent-map-link').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(cityAgent.query || regionAgent.query);
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
  if (locationButton && navigator.geolocation) {
    locationButton.addEventListener('click', function() {
      locationButton.disabled = true;
      locationButton.textContent = 'Checking location...';
      locationNote.textContent = 'Your browser will ask permission to share your approximate location.';
      locationNote.classList.add('is-visible');
      navigator.geolocation.getCurrentPosition(function(position) {
        var region = provinceFromCoordinates(position.coords.latitude, position.coords.longitude);
        setActiveRegion(region);
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

<?php include 'footer.php'; ?>
