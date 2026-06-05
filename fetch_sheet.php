<?php
include 'session_logins.php';
$productPageCategory = trim((string) ($_GET['category'] ?? ''));
$productPageCategoryKey = strtolower($productPageCategory);
$isGiftingCategoryPage = strcasecmp($productPageCategory, 'Gifting') === 0 || !empty($_GET['gifting_intro']);
$isResellerCategoryPage = in_array($productPageCategoryKey, ['for resellers', 'resellers & wholesale', 'resellers', 'reseller'], true);
$page_url_canonical = "https://www.candybird.co.za/products";
$title_og = 'Quality Nuts, Nut Packs, Dried Fruit & Gifting Online | CandyBird';
$page_url_og = "https://www.candybird.co.za/products";
$description_meta = 'Shop CandyBird for quality nuts, nut packs, dried fruit, sweets, health mixes and unique gifting online. Port Elizabeth based with secure checkout, collection and delivery across South Africa.';
$description_og = $description_meta;
$image_url_og = 'https://www.candybird.co.za/assets/img/pricelist.png';
if ($isGiftingCategoryPage) {
    $page_url_canonical = "https://www.candybird.co.za/gifting";
    $title_og = 'Gifting, Hampers & Treat Packs Online | CandyBird';
    $page_url_og = "https://www.candybird.co.za/gifting";
    $description_meta = 'Shop CandyBird gifting packs, hampers and treat boxes for family, clients, staff and special occasions. Order online for collection or delivery across South Africa.';
    $description_og = $description_meta;
    $image_url_og = 'https://www.candybird.co.za/assets/img/gifting.png';
} elseif ($isResellerCategoryPage) {
    $page_url_canonical = function_exists('getCandybirdCategoryUrl') ? getCandybirdCategoryUrl($productPageCategory, true) : 'https://www.candybird.co.za/products?category=' . rawurlencode($productPageCategory);
    $title_og = 'Reseller & Wholesale Packs Online | CandyBird';
    $page_url_og = $page_url_canonical;
    $description_meta = 'Shop CandyBird reseller and wholesale-friendly packs for stores, gifting businesses, food service and repeat bulk buyers. Order online or request support for larger quantities.';
    $description_og = $description_meta;
    $image_url_og = 'https://www.candybird.co.za/assets/img/reseller.jpeg';
    $image_type_og = 'image/jpeg';
}
include 'header.php';
$showSubscribeOffer = empty($_SESSION['user_id']) && empty($_GET['category']) && empty($_GET['search']);
?>

<title>Quality Nuts, Nut Packs, Dried Fruit & Gifting Online | CandyBird</title>

<?php
include 'page_menues.php';
?>

<style>
  .product-thumbnail {
    overflow: hidden;
  }

  .product-thumbnail .first-img {
    aspect-ratio: 1 / 1;
    background: #f7f2ea;
    display: block;
    height: auto;
    object-fit: cover;
    width: 100%;
  }

  .clearance-corner-flag {
    border-right: 108px solid transparent;
    border-top: 108px solid #d5001f;
    height: 0;
    left: 0;
    position: absolute;
    top: 0;
    width: 0;
    z-index: 6;
  }

  .clearance-corner-flag span {
    color: #fff;
    display: block;
    font-size: 9px;
    font-weight: 900;
    left: 4px;
    letter-spacing: 0;
    line-height: 1.12;
    position: absolute;
    text-align: center;
    text-transform: uppercase;
    top: -87px;
    transform: rotate(-45deg);
    width: 82px;
  }

  .sold-out-badge {
    background: #111;
    color: #fff;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .04em;
    padding: 5px 8px;
    position: absolute;
    right: 8px;
    text-transform: uppercase;
    top: 8px;
    z-index: 7;
  }

  .sold-out-button {
    background: #6c757d !important;
    border-color: #6c757d !important;
    cursor: not-allowed;
    opacity: .8;
  }

  .mobile-category-toggle {
    align-items: center;
    background: #171717;
    border: 0;
    border-radius: 6px;
    color: #fff;
    display: none;
    font-weight: 700;
    gap: 8px;
    justify-content: center;
    margin-bottom: 16px;
    padding: 12px 16px;
    width: 100%;
  }

  .gifting-category-intro {
    background: #fbfaf7;
    border-bottom: 1px solid #eadfd2;
    padding: 30px 0 24px;
  }

  .gifting-category-panel {
    align-items: center;
    background: #fff;
    border: 1px solid #eee1d4;
    border-radius: 8px;
    display: grid;
    gap: 22px;
    grid-template-columns: minmax(0, 1.15fr) minmax(240px, .85fr);
    padding: clamp(18px, 3vw, 28px);
  }

  .gifting-category-panel h1 {
    color: #251d18;
    font-size: clamp(1.8rem, 4vw, 3rem);
    line-height: 1.08;
    margin: 0 0 10px;
  }

  .gifting-category-panel p {
    color: #5d514b;
    line-height: 1.7;
    margin-bottom: 14px;
    max-width: 850px;
  }

  .category-social-image,
  .products-page-visual img {
    aspect-ratio: 1.9 / 1;
    border-radius: 8px;
    display: block;
    height: auto;
    object-fit: cover;
    width: 100%;
  }

  .products-page-visual {
    background: #fbfaf7;
    border-bottom: 1px solid #eadfd2;
    padding: 24px 0;
  }

  .products-page-visual-panel {
    align-items: center;
    background: #fff;
    border: 1px solid #eee1d4;
    border-radius: 8px;
    display: grid;
    gap: 20px;
    grid-template-columns: minmax(0, 1fr) minmax(240px, 380px);
    padding: clamp(16px, 2.5vw, 24px);
  }

  .products-page-visual h1 {
    color: #251d18;
    font-size: clamp(1.7rem, 3vw, 2.5rem);
    margin: 0 0 8px;
  }

  .products-page-visual p {
    color: #5d514b;
    line-height: 1.65;
    margin: 0;
  }

  .gifting-category-highlights {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-top: 16px;
  }

  .gifting-category-highlights span {
    background: #fff7ed;
    border: 1px solid #eadfd2;
    border-radius: 8px;
    color: #4b3528;
    display: block;
    font-weight: 700;
    padding: 10px 12px;
  }

  @media (max-width: 991.98px) {
    .products-category-sidebar {
      display: none;
    }

    .mobile-category-sidebar {
      display: none;
      margin-bottom: 18px;
    }

    .mobile-category-sidebar.is-open {
      display: block;
    }

    .mobile-category-toggle {
      display: flex;
    }
  }

  @media (max-width: 767px) {
    .gifting-category-panel,
    .products-page-visual-panel {
      grid-template-columns: 1fr;
    }

    .gifting-category-highlights {
      grid-template-columns: 1fr;
    }
  }
</style>


<?php
function generateProductsBreadcrumbsFromSheet($products, $selectedCategory = null, $searchTerm = null) {
    $breadcrumbs = [];

    // Home
    $breadcrumbs[] = '<li class="breadcrumb-item"><a href="https://www.candybird.co.za">Home</a></li>';
    // All Products
    $breadcrumbs[] = '<li class="breadcrumb-item"><a href="products">All Products</a></li>';

    if ($selectedCategory) {
        // Find the category hierarchy for the selected category
        $categoryPath = null;

        foreach ($products as $p) {
            // Check all category columns
            if ($p['parent_category'] === $selectedCategory) {
                $categoryPath = [$p['parent_category']];
                break;
            } elseif ($p['child_category_1'] === $selectedCategory) {
                $categoryPath = [$p['parent_category'], $p['child_category_1']];
                break;
            } elseif ($p['child_category_2'] === $selectedCategory) {
                $categoryPath = [$p['parent_category'], $p['child_category_1'], $p['child_category_2']];
                break;
            }
        }

        // Add category path to breadcrumbs
        if ($categoryPath) {
            $pathAccum = [];
            foreach ($categoryPath as $catName) {
                $pathAccum[] = $catName;
                $breadcrumbs[] = '<li class="breadcrumb-item"><a href="' . htmlspecialchars(function_exists('getCandybirdCategoryUrl') ? getCandybirdCategoryUrl($catName) : ('products?category=' . urlencode($catName)), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($catName) . '</a></li>';
            }
        } else {
            // If category not found, still display it
            $breadcrumbs[] = '<li class="breadcrumb-item"><span>' . htmlspecialchars($selectedCategory) . '</span></li>';
        }
    }

    // Build HTML
    $breadcrumbHtml = '<nav class="breadcrumb-section theme1 bg-lighten2 pt-50 pb-50">';
    $breadcrumbHtml .= '<div class="container">';
    $breadcrumbHtml .= '<div class="row">';
    $breadcrumbHtml .= '<div class="col-12">';
    $breadcrumbHtml .= '<ol class="breadcrumb bg-transparent m-0 p-0 align-items-center justify-content-center">';
    $breadcrumbHtml .= implode('', $breadcrumbs);
    $breadcrumbHtml .= '</ol>';
    $breadcrumbHtml .= '</div>';
    $breadcrumbHtml .= '</div>';

    if (!empty($searchTerm)) {
        $breadcrumbHtml .= '<div class="container mt-3"><h5 class="text-primary">You searched for <strong>"' . htmlspecialchars($searchTerm) . '"</strong>:</h5></div>';
    }

    $breadcrumbHtml .= '</div></nav>';

    echo $breadcrumbHtml;
}

$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$searchTerm = isset($_GET['search']) ? $_GET['search'] : null;

generateProductsBreadcrumbsFromSheet([], $selectedCategory, $searchTerm);

?>

<?php if (isset($_GET['gifting_intro']) || strcasecmp((string) $selectedCategory, 'Gifting') === 0): ?>
<section class="gifting-category-intro">
  <div class="container">
    <div class="gifting-category-panel">
      <div>
        <h1>Gifting</h1>
        <p>Beautiful edible gifts for Eid, Ramadan, weddings, staff appreciation, client drops and thoughtful family occasions. Choose from ready-to-shop gift trays, snack boxes and premium treats, or contact us for a curated gift plan when you need something more specific.</p>
        <div class="gifting-category-highlights">
          <span>Ready-to-shop gift trays and treat boxes</span>
          <span>Custom notes, ribbons and curated selections</span>
          <span>Corporate, event and family gifting support</span>
        </div>
      </div>
      <img class="category-social-image" src="https://www.candybird.co.za/assets/img/gifting.png" alt="CandyBird gifting packs and hampers" loading="lazy">
    </div>
  </div>
</section>
<?php elseif (in_array(strtolower(trim((string) $selectedCategory)), ['for resellers', 'resellers & wholesale', 'resellers', 'reseller'], true)): ?>
<section class="gifting-category-intro">
  <div class="container">
    <div class="gifting-category-panel">
      <div>
        <h1>Reseller & Wholesale Packs</h1>
        <p>Browse reseller-friendly CandyBird packs for stores, gifting businesses, food service and larger repeat buyers. These products are useful when you need clear sizes, dependable pricing and a quick way to build a basket for resale or bulk use.</p>
        <div class="gifting-category-highlights">
          <span>Useful pack sizes for resale and repeat buying</span>
          <span>Clear online pricing with cart and checkout support</span>
          <span>Wholesale list available for larger bulk planning</span>
        </div>
      </div>
      <img class="category-social-image" src="https://www.candybird.co.za/assets/img/reseller.jpeg" alt="CandyBird reseller and wholesale packs" loading="lazy">
    </div>
  </div>
</section>
<?php elseif (empty($selectedCategory) && empty($searchTerm)): ?>
<section class="products-page-visual">
  <div class="container">
    <div class="products-page-visual-panel">
      <div>
        <h1>Online Shop</h1>
        <p>Browse CandyBird nuts, dried fruit, sweets, gifting, specials and pantry favourites. Each product and size is listed separately so prices, stock and shipping stay clear.</p>
      </div>
      <img src="https://www.candybird.co.za/assets/img/pricelist.png" alt="CandyBird online product range" loading="lazy">
    </div>
  </div>
</section>
<?php endif; ?>

<!-- product tab start -->
<div id="sentinel-parent" class="product-tab bg-white pt-0 pb-50">
  <div class="container">
    <div class="row">
      
      <!-- Sidebar -->
      <div class="col-lg-3 mb-30 order-2 order-lg-first products-category-sidebar">
        <aside class="left-sidebar theme1">
          <div class="sidbar-widget pt-0">
          </div>
          <div id="category-sidebar"></div>
          <!-- You can add additional filters like Price/Size/Properties here if needed -->
        </aside>
      </div>
      
      <!-- Products -->
      <div class="col-lg-9 mb-30 order-1 order-lg-last">
        <button type="button" class="mobile-category-toggle" id="mobile-category-toggle" aria-expanded="false">
          <i class="fa fa-filter"></i> Browse categories
        </button>
        <div id="mobile-category-sidebar" class="mobile-category-sidebar d-lg-none"></div>
        
        <div class="grid-nav-wraper bg-lighten2 mb-30">
          <div class="row align-items-center">
            
            <div class="col-12 col-md-6 mb-3 mb-md-0">
              <nav class="shop-grid-nav">
                <ul class="nav nav-pills align-items-center" id="pills-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active grid-view-products" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">
                      <i class="fa fa-th"></i>
                    </a>
                  </li>
                  <li class="nav-item mr-0">
                    <a class="nav-link list-view-products" id="pills-profile-tab" data-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="false">
                      <i class="fa fa-list"></i>
                    </a>
                  </li>
                </ul>
              </nav>
            </div>
            
            <div class="col-12 col-md-6 position-relative">
              <div class="shop-grid-button d-flex align-items-center">
                <span class="sort-by">Sort by:</span>
                <button class="d-flex justify-content-between" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span id="selectedSort" style="font-size: inherit;">Relevance</span> <span class="ion-android-arrow-dropdown"></span>
                </button>
                <div class="dropdown-menu shop-grid-menu" aria-labelledby="dropdownMenuButton">
                  <a class="dropdown-item sort-option" data-sort="relevance" href="#">Relevance</a>
                  <a class="dropdown-item sort-option" data-sort="name_asc" href="#">Name, A to Z</a>
                  <a class="dropdown-item sort-option" data-sort="name_desc" href="#">Name, Z to A</a>
                  <a class="dropdown-item sort-option" data-sort="price_low_high" href="#">Price, low to high</a>
                  <a class="dropdown-item sort-option" data-sort="price_high_low" href="#">Price, high to low</a>
                </div>
              </div>
            </div>
            
          </div>
        </div>
        <!-- product-tab-nav end -->
        
        <div class="tab-content" id="pills-tabContent">
          <div id="products-loading" class="col-12 text-center py-5">
            <div class="spinner-border text-dark" role="status" aria-hidden="true"></div>
            <p class="mt-3 mb-0">Loading products...</p>
          </div>
          <div id="products-empty" class="col-12 text-center py-5 d-none">
            <p class="mb-0">No products found.</p>
          </div>
          <!-- Grid view -->
          <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
            <div class="row grid-view theme1" id="filtered_products_grid_view">
              <!-- Products will be appended here via JS -->
            </div>
          </div>
          
          <!-- List view -->
          <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
            <div class="row grid-view-list theme1" id="filtered_products_list_view">
              <!-- Products will be appended here via JS -->
            </div>
          </div>
        </div>
        
      </div>
    </div>
    
    <!-- Pagination -->
    <?= isset($pagination_section) ? $pagination_section : '' ?>
    
  </div>
</div>
<!-- product tab end -->

<!-- JS for fetching products and categories -->
<script>
window.addEventListener('load', function () {
if (!window.jQuery) return;
const $ = window.jQuery;

function cleanCategory(value) {
  if (!value) return null;

  const v = value.toString().trim();
  if (!v || v.toLowerCase() === 'n/a' || v.toLowerCase() === 'null') {
    return null;
  }

  return v;
}

function extractCategories(products) {
  const categories = {};

  products.forEach(p => {
    const parent = cleanCategory(p.parent_category);
    if (!parent) return;

    const child1 = cleanCategory(p.child_category_1);
    const child2 = cleanCategory(p.child_category_2);

    if (!categories[parent]) {
      categories[parent] = {};
    }

    if (child1 && child1 !== parent) {
      if (!categories[parent][child1]) {
        categories[parent][child1] = new Set();
      }
    }

    if (child1 && child2 && child2 !== parent && child2 !== child1) {
      categories[parent][child1].add(child2);
    }
  });

  return categories;
}



const SERVER_SELECTED_CATEGORY = <?=json_encode($selectedCategory ?: '')?>;
const activeCategory = new URLSearchParams(window.location.search).get('category') || SERVER_SELECTED_CATEGORY;
const CATEGORY_DISPLAY_ORDER = <?=json_encode(function_exists('getCandybirdCategoryDisplayOrder') ? getCandybirdCategoryDisplayOrder() : [])?>;
const CATEGORY_DISPLAY_MAP = <?=json_encode(function_exists('getCandybirdCategoryDisplayMap') ? getCandybirdCategoryDisplayMap() : [])?>;
const CATEGORY_DISPLAY_POSITIONS = <?=json_encode(function_exists('getCandybirdCategoryDisplayMap') ? array_map(static function($item) { return (int) ($item['position'] ?? 9999); }, getCandybirdCategoryDisplayMap()) : [])?>;

function isCategoryVisible(category) {
  return !CATEGORY_DISPLAY_MAP[category] || CATEGORY_DISPLAY_MAP[category].visible !== false;
}

function getCategoryLabel(category) {
  return (CATEGORY_DISPLAY_MAP[category] && CATEGORY_DISPLAY_MAP[category].label) ? CATEGORY_DISPLAY_MAP[category].label : category;
}

function slugifyCategory(value) {
  return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function getCategoryPath(category) {
  const labelSlug = slugifyCategory(getCategoryLabel(category));
  const sourceSlug = slugifyCategory(category);
  return labelSlug || sourceSlug || `products?category=${encodeURIComponent(category)}`;
}

function sortCategoryNames(names) {
  return names.sort((a, b) => {
    const slugA = slugifyCategory(a);
    const slugB = slugifyCategory(b);
    const orderA = CATEGORY_DISPLAY_ORDER.findIndex(name => name === a || slugifyCategory(name) === slugA || slugifyCategory(getCategoryLabel(name)) === slugA);
    const orderB = CATEGORY_DISPLAY_ORDER.findIndex(name => name === b || slugifyCategory(name) === slugB || slugifyCategory(getCategoryLabel(name)) === slugB);
    const posA = CATEGORY_DISPLAY_POSITIONS[a] !== undefined ? CATEGORY_DISPLAY_POSITIONS[a] : (orderA === -1 ? 9999 : orderA);
    const posB = CATEGORY_DISPLAY_POSITIONS[b] !== undefined ? CATEGORY_DISPLAY_POSITIONS[b] : (orderB === -1 ? 9999 : orderB);
    if (posA !== 9999 || posB !== 9999) {
      return posA - posB;
    }
    return a.localeCompare(b);
  });
}

function renderCategoriesSidebar(products) {
  const categories = extractCategories(products);
  const currentPage = window.location.pathname;
  const categoryUrl = category => getCategoryPath(category);

  let html = `
    <div class="search-filter">
      <div class="sidbar-widget pt-0">
        <h4 class="title">Categories</h4>
      </div>
      <ul id="offcanvas-menu2" class="blog-ctry-menu">
  `;

  sortCategoryNames(Object.keys(categories).filter(isCategoryVisible)).forEach(parent => {
    const children = categories[parent];
    const childEntries = sortCategoryNames(Object.keys(children).filter(isCategoryVisible)).map(child => [child, children[child]]);
    const parentLabel = getCategoryLabel(parent);

    html += `
      <li class="has-sub open">
        <a href="${categoryUrl(parent)}"
           class="category-link parent-category${activeCategory === parent ? ' active' : ''}">
          ${parentLabel}
        </a>
    `;

    if (childEntries.length) {
      html += `<ul class="category-sub-menu" style="display:block">`;

      childEntries.forEach(([child, grandchildren]) => {
        const grandchildList = sortCategoryNames([...grandchildren].filter(isCategoryVisible));
        const childActive = activeCategory === child || grandchildList.includes(activeCategory);
        const openChild = grandchildList.includes(activeCategory);

        html += `
          <li class="${grandchildList.length ? 'has-sub' : ''}${openChild ? ' open' : ''}">
            <a href="${categoryUrl(child)}"
               class="category-link${childActive ? ' active' : ''}">
              ${getCategoryLabel(child)}
            </a>
        `;

        if (grandchildList.length) {
          html += `<button type="button" class="category-toggle" aria-label="Show subcategories">+</button>`;
          html += `<ul class="category-sub-menu category-grandchildren"${openChild ? ' style="display:block"' : ''}>`;

          grandchildList.forEach(grandchild => {
            const grandchildActive = activeCategory === grandchild ? ' active' : '';

            html += `
              <li>
                <a href="${categoryUrl(grandchild)}"
                   class="category-link${grandchildActive}">
                  ${getCategoryLabel(grandchild)}
                </a>
              </li>
            `;
          });

          html += `</ul>`;
        }

        html += `</li>`;
      });

      html += `</ul>`;
    }

    html += `</li>`;
  });

  html += `</ul></div>`;

  $('#category-sidebar').html(html);
  $('#mobile-category-sidebar').html(html);
}

function normalizeSearchText(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/&amp;/g, '&')
    .replace(/[^a-z0-9]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function getSearchTokens(query) {
  const tokens = normalizeSearchText(query).split(' ').filter(Boolean);
  const expanded = [];
  tokens.forEach(token => {
    expanded.push(token);
    if (token.length > 3 && token.endsWith('s')) {
      expanded.push(token.slice(0, -1));
    }
  });
  return [...new Set(expanded)];
}

function productSearchText(product) {
  return normalizeSearchText([
    product.id,
    product.name,
    product.title,
    product.size,
    product.weight,
    product.parent_category,
    product.child_category_1,
    product.child_category_2,
    product.category,
    product.category_name,
    product.description,
    product.short_description,
    product.flavour,
    product.flavor,
    product.label,
    product.tags
  ].filter(Boolean).join(' '));
}

function getProductSearchScore(product, query) {
  const tokens = getSearchTokens(query);
  if (!tokens.length) return 1;

  const haystack = productSearchText(product);
  const name = normalizeSearchText(product.name || product.title || '');
  let score = 0;

  tokens.forEach(token => {
    if (name === token) score += 80;
    else if (name.includes(token)) score += 45;
    else if (haystack.includes(token)) score += 20;
  });

  return score;
}

function isDigitalProduct(product) {
  const size = String(product.size || product.weight || '').trim().toLowerCase();
  const type = String(product.product_type || product.type || product.delivery_type || '').trim().toLowerCase();
  const text = [product.name, product.title, product.parent_category, product.child_category_1, product.child_category_2].join(' ').toLowerCase();
  return size === '0' || size === '0g' || size === '0 g' || size === '0kg' || size === '0 kg' ||
    type.includes('digital') || type.includes('ebook') || type.includes('e-book') || type.includes('voucher') ||
    text.includes('voucher') || text.includes('ebook') || text.includes('e-book');
}

function displayProductSize(product) {
  return isDigitalProduct(product) ? '' : String(product.size || product.weight || '').trim();
}

function displayProductTitle(product) {
  const name = product.name || product.title || '';
  const size = displayProductSize(product);
  return `${name}${size ? ' ' + size : ''}`;
}

function getProductPath(product) {
  if (product && product.slug) {
    return encodeURIComponent(product.slug);
  }

  const isClearance = product && String(product.is_clearance || '').toLowerCase() === 'yes';
  if (isClearance) {
    const name = String(product.name || product.title || '').replace(/\bclearance\b/ig, '');
    const text = [name, displayProductSize(product), 'clearance'].join(' ');
    const slug = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    if (slug) return encodeURIComponent(slug);
  }

  return product ? `product?id=${encodeURIComponent(product.id)}` : 'products';
}

$(document).on('click', '.category-toggle', function () {
  const $parentLi = $(this).closest('li');

  $parentLi.toggleClass('open');
  $parentLi.children('.category-grandchildren').slideToggle(200);
});

$(document).on('click', '#mobile-category-toggle', function () {
  const $sidebar = $('#mobile-category-sidebar');
  const isOpen = !$sidebar.hasClass('is-open');
  $sidebar.toggleClass('is-open', isOpen);
  $(this)
    .attr('aria-expanded', isOpen ? 'true' : 'false')
    .html(isOpen ? '<i class="fa fa-times"></i> Hide categories' : '<i class="fa fa-filter"></i> Browse categories');
});

document.addEventListener('click', function(event) {
  const button = event.target.closest('#mobile-category-toggle');
  if (!button) return;
  const sidebar = document.querySelector('#mobile-category-sidebar');
  if (!sidebar) return;
  event.preventDefault();
  event.stopPropagation();
  const isOpen = !sidebar.classList.contains('is-open');
  sidebar.classList.toggle('is-open', isOpen);
  button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  button.innerHTML = isOpen ? '<i class="fa fa-times"></i> Hide categories' : '<i class="fa fa-filter"></i> Browse categories';
}, true);

  /* --------------------------------
     PRICE HELPER
  -------------------------------- */
  function getPrice(p) {
    const price = parseFloat(p.price) || 0;
    const discounted = parseFloat(p.discounted_price || 0) || 0;
    const discountAmount = parseFloat(p.discount || p.discount_amount || 0) || 0;
    const discountRate = parseFloat(p.discount_rate || 0) || 0;
    const isClearance = String(p.is_clearance || '').toLowerCase() === 'yes';
    if (isClearance && discounted > 0) return discounted;
    if (isClearance && discountAmount > 0) return Math.max(0, price - discountAmount);
    if (!isProductSpecialActive(p)) return price;
    if (discounted > 0 && discounted < price) return discounted;
    if (discountAmount > 0) return Math.max(0, price - discountAmount);
    if (discountRate > 0) return Math.max(0, price - (price * discountRate / 100));
    return price;
  }

  function getStockNumber(product) {
    const fields = ['qty_available', 'stock_qty', 'qty_in_stock', 'quantity_available', 'available_qty', 'inventory', 'stock'];
    for (let i = 0; i < fields.length; i++) {
      if (!Object.prototype.hasOwnProperty.call(product || {}, fields[i])) {
        continue;
      }
      const value = product[fields[i]];
      if (value !== undefined && value !== null && String(value).trim() !== '' && !isNaN(parseFloat(value))) {
        return Math.max(0, Math.floor(parseFloat(value)));
      }
    }
    return null;
  }

  function isProductSoldOut(product) {
    const stockNumber = getStockNumber(product);
    return stockNumber !== null && stockNumber <= 0;
  }

  function parseSpecialDate(value, endOfDay) {
    value = String(value || '').trim();
    if (!value) return null;
    var hasTime = /\d{1,2}:\d{2}/.test(value);
    var match = value.match(/^(\d{1,2})[-/](\d{1,2})[-/](\d{4})(?:\s+(\d{1,2}):(\d{2}))?$/);
    if (match) {
      var date = new Date(+match[3], +match[2] - 1, +match[1], +(match[4] || 0), +(match[5] || 0), 0);
      if (endOfDay && !hasTime) date.setHours(23, 59, 59, 999);
      return date;
    }
    var parsed = new Date(value);
    if (isNaN(parsed.getTime())) return null;
    if (endOfDay && !hasTime) parsed.setHours(23, 59, 59, 999);
    return parsed;
  }

  function isProductSpecialActive(product) {
    var from = parseSpecialDate(product.discount_valid_from || product.special_valid_from || product.sale_valid_from, false);
    var until = parseSpecialDate(product.discount_valid_until || product.special_valid_until || product.sale_valid_until, true);
    var now = new Date();
    if (from && now < from) return false;
    if (until && now > until) return false;
    return true;
  }

  function getSalePercent(product) {
    const price = parseFloat(product.price) || 0;
    const discountedPrice = getPrice(product);
    if (price <= 0 || discountedPrice >= price) return 0;
    return Math.round(((price - discountedPrice) / price) * 100);
  }

  function productMatchesTag(product, tag) {
    const text = String([product.label, product.tags, product.tag].join(' ')).toLowerCase();
    return text.indexOf(tag) !== -1;
  }

  function getProductImages(product) {
    const imageValue = product.img_url || product.image_url || product.image_urls || product.image || '';
    const images = String(imageValue).split(',').map(img => img.trim()).filter(Boolean);
    return images.length ? images : ['assets/img/product/1.png'];
  }

  function shuffleProducts(items) {
    return items
      .map(item => ({ item, sort: Math.random() }))
      .sort((a, b) => a.sort - b.sort)
      .map(row => row.item);
  }

  /* --------------------------------
     SORT
  -------------------------------- */
  function applySort() {
    renderedCount = 0;
    $('#filtered_products_grid_view, #filtered_products_list_view').empty();
    $('#products-empty').addClass('d-none');

    productsToShow = [...categoryFiltered].sort((a, b) => {
      const aSoldOut = isProductSoldOut(a);
      const bSoldOut = isProductSoldOut(b);
      if (aSoldOut !== bSoldOut) {
        return aSoldOut ? 1 : -1;
      }

      switch (currentSort) {
        case 'name_asc': return a.name.localeCompare(b.name);
        case 'name_desc': return b.name.localeCompare(a.name);
        case 'price_low_high': return getPrice(a) - getPrice(b);
        case 'price_high_low': return getPrice(b) - getPrice(a);
        case 'relevance':
        default: return (a._randomSort || 0) - (b._randomSort || 0);
      }
    });

    renderNextBatch();

    if (!productsToShow.length) {
      $('#products-empty').removeClass('d-none');
    }
  }

  /* --------------------------------
     RATING
  -------------------------------- */
    function convertToStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
      stars += `<span class="${i <= rating ? 'ion-ios-star' : 'ion-ios-star de-selected'}"></span>`;
    }
    return stars;
  }

    /* --------------------------------
     RENDER PRODUCTS
  -------------------------------- */

  function renderNextBatch() {
    const batch = productsToShow.slice(renderedCount, renderedCount + PRODUCTS_PER_LOAD);

    batch.forEach(item => {

      const productId = item.id;
      const title = item.name;
      const weight = displayProductSize(item);

      const price = parseFloat(item.price) || 0;
      const discountedPrice = getPrice(item);

      const discountRate = getSalePercent(item);
      const discountAmount = parseFloat(item.discount || item.discount_amount || 0) || 0;
      const hasDiscount = discountRate > 0 || discountAmount > 0 || discountedPrice < price;
      const description = item.html_description || item.description || '';
      const label = item.label || '';
      const isClearance = String(item.is_clearance || '').toLowerCase() === 'yes';
      const clearanceAttr = isClearance && item.clearance_id ? ` data-clearance-id="${item.clearance_id}"` : '';
      const stockNumber = getStockNumber(item);
      const isSoldOut = stockNumber !== null && stockNumber <= 0;
      const rating = isClearance ? 0 : parseInt(item.rating || 0);
      const imageUrl = getProductImages(item)[0];

      const limitedDescription =
        description.length > 300
          ? description.replace(/(<([^>]+)>)/gi, '').substring(0, 300) + '...'
          : description.replace(/(<([^>]+)>)/gi, '');

      /* ===========================
         GRID VIEW
      ============================ */
      $('#filtered_products_grid_view').append(`
        <div class="col-sm-6 col-md-4 mb-30">
          <div class="card product-card">
            <div class="card-body">
              <div class="product-thumbnail position-relative">

                ${isClearance ? `<span class="clearance-corner-flag"><span>Clearance<br>to go</span></span>` : (label ? `<span class="badge badge-danger top-left">${label}</span>` : '')}
                ${isSoldOut ? `<span class="sold-out-badge">Sold out</span>` : (discountRate > 0 ? `<span class="badge badge-success top-right">${discountRate}% off</span>` : (!isClearance && productMatchesTag(item, 'hot') ? `<span class="badge badge-warning top-right">Hot</span>` : ''))}

                <a href="${getProductPath(item)}">
                  <img class="first-img"
                       src="${imageUrl}"
                       onerror="this.onerror=null;this.src='assets/img/product/1.png';"
                       alt="${title}">
                </a>

                <ul class="actions d-flex justify-content-center">
                  ${isClearance ? '' : `<li><a class="action add-to-wishlist" data-product-id="${productId}" href="#"><span class="icon-heart"></span></a></li>
                  <li><a class="action add-to-compare" data-product-id="${productId}" href="#"><span class="icon-shuffle"></span></a></li>`}
                  <li><a class="action open-quick-view" data-product-id="${productId}" href="#" data-toggle="modal" data-target="#quick-view"><span class="icon-magnifier"></span></a></li>
                </ul>
              </div>

              <div class="product-desc py-0 px-0">
                <h3 class="title">
                    <a href="${getProductPath(item)}">${title} ${weight}</a>
                </h3>

                ${isClearance ? `` : `<div class="star-rating">${convertToStars(rating)}</div>`}

                <div class="d-flex align-items-center justify-content-between">
                  <span class="product-price">
                    ${
                      hasDiscount
                          ? `<del class="del">R${price.toFixed(2)}</del>
                             <span class="onsale">R${discountedPrice.toFixed(2)}</span>`
                          : `R${price.toFixed(2)}`
                    }
                  </span>

                  ${isSoldOut
                    ? ``
                    : `<button class="pro-btn add-to-cart" data-toggle="modal" data-target="#add-to-cart" data-product-id="${productId}"${clearanceAttr}><i class="icon-basket"></i></button>`}
                </div>
              </div>
            </div>
          </div>
        </div>
      `);

      /* ===========================
         LIST VIEW
      ============================ */
      $('#filtered_products_list_view').append(`
        <div class="col-12 mb-30">
          <div class="card product-card">
            <div class="card-body">
              <div class="media flex-column flex-md-row">

                <div class="product-thumbnail position-relative">
                  ${isClearance ? `<span class="clearance-corner-flag"><span>Clearance<br>to go</span></span>` : (label ? `<span class="badge badge-danger top-left">${label}</span>` : '')}
                  ${isSoldOut ? `<span class="sold-out-badge">Sold out</span>` : (discountRate > 0 ? `<span class="badge badge-success top-right">${discountRate}% off</span>` : (!isClearance && productMatchesTag(item, 'hot') ? `<span class="badge badge-warning top-right">Hot</span>` : ''))}

                  <a href="${getProductPath(item)}">
                    <img class="first-img" src="${imageUrl}" onerror="this.onerror=null;this.src='assets/img/product/1.png';" alt="${title}">
                  </a>
                </div>

                <div class="media-body pl-md-4">
                  <h3 class="title"><a href="${getProductPath(item)}">${title}</a></h3>
                  ${isClearance ? `` : `<div class="star-rating mb-10">${convertToStars(rating)}</div>`}

                  <span class="product-price">
                    ${
                      hasDiscount
                          ? `<del class="del">R${price.toFixed(2)}</del>
                             <span class="onsale">R${discountedPrice.toFixed(2)}</span>`
                          : `R${price.toFixed(2)}`
                    }
                  </span>

                  <ul class="product-list-des"><li>${limitedDescription}</li></ul>

                  ${isSoldOut
                    ? `<span class="sold-out-button d-inline-block" aria-disabled="true">Sold out</span>`
                    : `<button class="btn btn-dark btn--xl add-to-cart" data-toggle="modal" data-target="#add-to-cart" data-product-id="${productId}"${clearanceAttr}>Add to cart</button>`}
                </div>

              </div>
            </div>
          </div>
        </div>
      `);

    });

    renderedCount += batch.length;
  }

  /* --------------------------------
     Setting the data globally for modals, cart, etc.
  -------------------------------- */

let ALL_PRODUCTS = [];
let currentSort = '';
let categoryFiltered = [];
let activeSearchTerm = '';

let PRODUCTS_PER_LOAD = 10;
let renderedCount = 0;
let productsToShow = [];

$.getJSON("fetch_sheet_data.php", function (data) {

  ALL_PRODUCTS = shuffleProducts(data).map(function(product, index) {
    product._randomSort = index + Math.random();
    return product;
  }); // Setting the data globally for modals, cart, etc.
  window.CANDYBIRD_PRODUCTS = ALL_PRODUCTS;


  console.log(data);
  renderCategoriesSidebar(ALL_PRODUCTS);

  currentSort = new URLSearchParams(window.location.search).get('sort') || 'relevance';
  $('#selectedSort').text({
    relevance: 'Relevance',
    name_asc: 'Name, A to Z',
    name_desc: 'Name, Z to A',
    price_low_high: 'Price, low to high',
    price_high_low: 'Price, high to low'
  }[currentSort] || 'Relevance');
  
  const selectedCategory = new URLSearchParams(window.location.search).get('category') || SERVER_SELECTED_CATEGORY;
  activeSearchTerm = new URLSearchParams(window.location.search).get('search') || '';
  const normalizedSelectedCategory = cleanCategory(selectedCategory);

  /* --------------------------------
     CATEGORY FILTER (URL)
  -------------------------------- */
  categoryFiltered = ALL_PRODUCTS.filter(p => {
    if (!normalizedSelectedCategory) return true;

    return [
      cleanCategory(p.parent_category),
      cleanCategory(p.child_category_1),
      cleanCategory(p.child_category_2)
    ].includes(normalizedSelectedCategory);
  });

  if (normalizedSelectedCategory && !categoryFiltered.length) {
    const categories = extractCategories(data);
    const matchingParent = Object.entries(categories).find(([parent, children]) => {
      if (parent === normalizedSelectedCategory) return true;

      return Object.entries(children).some(([child, grandchildren]) => {
        return child === normalizedSelectedCategory || [...grandchildren].includes(normalizedSelectedCategory);
      });
    });

    if (matchingParent) {
      const [parent, children] = matchingParent;
      categoryFiltered = data.filter(p => {
        const parentCategory = cleanCategory(p.parent_category);
        const child1 = cleanCategory(p.child_category_1);
        const child2 = cleanCategory(p.child_category_2);

        if (parent === normalizedSelectedCategory) {
          return parentCategory === parent;
        }

        return Object.entries(children).some(([child, grandchildren]) => {
          if (child !== normalizedSelectedCategory && ![...grandchildren].includes(normalizedSelectedCategory)) {
            return false;
          }

          return child1 === child || child2 === normalizedSelectedCategory;
        });
      });
    } else {
      categoryFiltered = data;
    }
  }

  if (activeSearchTerm) {
    const searched = categoryFiltered
      .map(product => ({ product, score: getProductSearchScore(product, activeSearchTerm) }))
      .filter(item => item.score > 0 && !isProductSoldOut(item.product))
      .sort((a, b) => b.score - a.score || String(a.product.name || '').localeCompare(String(b.product.name || '')))
      .map(item => item.product);

    categoryFiltered = searched;
    $('#products-empty p').text('No products found for "' + activeSearchTerm + '". Try a simpler word like pistachio, almond, chocolate, raw, salted, 500g, or 1kg.');
  }

  /* --------------------------------
     INFINITE SCROLL
  -------------------------------- */
  const sentinel = document.createElement('div');
  sentinel.id = 'scroll-sentinel';
  document.getElementById('sentinel-parent').appendChild(sentinel);

  const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) renderNextBatch();
  }, { rootMargin: '100px' });

  observer.observe(sentinel);

  /* --------------------------------
     SORT
  -------------------------------- */
  $('.sort-option').on('click', function (e) {
    e.preventDefault();
    currentSort = $(this).data('sort');
    $('#selectedSort').text($(this).text());
    applySort();
  });

  /* --------------------------------
     INITIAL LOAD
  -------------------------------- */
  applySort();
})
.fail(function () {
  $('#products-empty p').text('Products could not be loaded. Please refresh the page.');
  $('#products-empty').removeClass('d-none');
})
.always(function () {
  $('#products-loading').addClass('d-none');
});


function populateQuickView(group, activeProduct) {

  /* ---------------------------
     ELEMENT TARGETS
  ---------------------------- */
  const $imagesWrap = $('.quick-view-image-wrapper');
  const $head = $('.modal-product-info .product-head');
  const $body = $('.modal-product-info .product-body');
  const $sizes = $('.modal-product-info .product-size');
  const $footer = $('.modal-product-info .product-footer');

  /* ---------------------------
     CLEAR OLD CONTENT
  ---------------------------- */
  $imagesWrap.empty();
  $head.empty();
  $body.empty();
  $sizes.empty();
  $footer.empty();

  /* ---------------------------
     IMAGES
  ---------------------------- */
  const images = getProductImages(activeProduct);

  $imagesWrap.append(`
    <div class="product-sync-init mb-20">
      ${images.map(img => `
        <div class="single-product">
          <img src="${img.trim()}" onerror="this.onerror=null;this.src='assets/img/product/1.png';" alt="${activeProduct.name}">
        </div>
      `).join('')}
    </div>
  `);

  /* ---------------------------
     HEADER (TITLE + RATING)
  ---------------------------- */
  $head.append(`
    <h2 class="title">${activeProduct.name}</h2>
    <div class="star-rating">
      ${convertToStars(parseInt(activeProduct.rating || 0))}
    </div>
  `);

  /* ---------------------------
     DESCRIPTION
  ---------------------------- */
  $body.append(`
    <p>${activeProduct.html_description || ''}</p>
  `);

  /* ---------------------------
     SIZE OPTIONS
  ---------------------------- */
  group.forEach((p, i) => {
    const price = getPrice(p);
    const buttonLabel = displayProductSize(p) || p.name;

    $sizes.append(`
      <button
        class="size-btn ${p.id === activeProduct.id ? 'active' : ''}"
        data-id="${p.id}"
        data-price="${price}"
        data-images="${(p.img_url || p.image_url || p.image_urls || p.image || '')}">
        ${buttonLabel}
      </button>
    `);
  });

  /* ---------------------------
     FOOTER (PRICE + CART)
  ---------------------------- */
  updateQuickViewFooter(activeProduct);

}

function updateQuickViewFooter(product) {
  const price = getPrice(product);
  const stockNumber = getStockNumber(product);
  const isSoldOut = stockNumber !== null && stockNumber <= 0;

  $('.modal-product-info .product-footer').html(`
    <div class="product-price mb-20">
      R${price.toFixed(2)}
    </div>

    ${isSoldOut
      ? `<span class="sold-out-button d-inline-block" aria-disabled="true">Sold out</span>`
      : `<button class="btn btn-dark btn--xl add-to-cart" data-product-id="${product.id}"${String(product.is_clearance || '').toLowerCase() === 'yes' && product.clearance_id ? ` data-clearance-id="${product.clearance_id}"` : ''}>Add to cart</button>`}
  `);
}

$(document).on('click', '.size-btn', function () {

  $('.size-btn').removeClass('active');
  $(this).addClass('active');

  const productId = $(this).data('id');
  const product = ALL_PRODUCTS.find(p => p.id == productId);

  if (!product) return;

  /* Update images */
  const images = getProductImages(product);
  const $imagesWrap = $('.quick-view-image-wrapper');

  $imagesWrap.html(`
    <div class="product-sync-init mb-20">
      ${images.map(img => `
        <div class="single-product">
          <img src="${img.trim()}" onerror="this.onerror=null;this.src='assets/img/product/1.png';" alt="${product.name}">
        </div>
      `).join('')}
    </div>
  `);

  /* Update footer price */
  updateQuickViewFooter(product);
});



$(document).on('click', '.open-quick-view', function (e) {
    e.preventDefault();
    const productId = $(this).attr('data-product-id');
    loadProductDetails(productId);
});


function loadProductDetails(productId) {
    // Find the product in the ALL_PRODUCTS array
    const product = ALL_PRODUCTS.find(p => p.id == productId);
    if (!product) {
        console.error("Product not found in ALL_PRODUCTS for ID:", productId);
        return;
    }

    const productData = {
        id: product.id,
        title: displayProductTitle(product),
        price: parseFloat(product.price) || 0,
        discount_amount: parseFloat(product.discount || 0),
        discount_rate: parseInt(product.discount || 0),
        description: product.html_description || '',
        image_urls: getProductImages(product),
        rating: parseInt(product.rating || 0),
        weight: displayProductSize(product),
        other_weights: displayProductSize(product),
        other_products_in_group: product.id,
        parent_category: product.parent_category,
        child_category_1: product.child_category_1,
        child_category_2: product.child_category_2,
        is_clearance: product.is_clearance || '',
        clearance_id: product.clearance_id || '',
        stock_qty: product.stock_qty || product.qty_in_stock || product.stock || product.qty_available || product.quantity_available || product.available_qty || product.inventory || ''
    };


    // Call the existing updateModal function
    updateModal(productData);

    // Initialize slick sliders if images exist
    if (productData.image_urls.length > 0) {
        $("#quick-view .product-sync-init").slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            infinite: true,
            draggable: false,
            arrows: false,
            dots: false,
            fade: true,
            asNavFor: ".product-sync-nav"
        });
        $("#quick-view .product-sync-nav").slick({
            dots: false,
            arrows: false,
            infinite: true,
            prevArrow: '<button class="slick-prev"><i class="fas fa-arrow-left"></i></button>',
            nextArrow: '<button class="slick-next"><i class="fas fa-arrow-right"></i></button>',
            slidesToShow: 4,
            slidesToScroll: 1,
            asNavFor: ".product-sync-init",
            focusOnSelect: true,
            draggable: false
        });
    }
}

function updateModal(productData) {
    // Generate product images HTML
    var product_url_og = 'https://www.candybird.co.za/assets/img/favicon.png';
    var productImagesHtml = '';
    var productThumbnailImagesHtml = '';

    var imageUrls = productData.image_urls;

    // Set default image URL
    var defaultImageUrl = 'assets/img/product/1.png';

    // Check if imageUrls array is empty
    if (imageUrls.length === 0) {
        imageUrls.push(defaultImageUrl);
    }

    imageUrls.forEach((imageUrl, index) => {
        productImagesHtml += `
              <div class="single-product">
                <div class="product-thumb">
                  <img
                    src="${imageUrl}"
                    onerror="this.onerror=null;this.src='assets/img/product/1.png';"
                    alt="product-thumb"
                  />
                </div>
              </div>
        `;
        productThumbnailImagesHtml += `
           <div class="single-product">
                <div class="product-thumb">
                  <a href="javascript:void(0)">
                    <img
                      src="${imageUrl}"
                      onerror="this.onerror=null;this.src='assets/img/product/1.png';"
                      alt="product-thumb"
                  /></a>
                </div>
              </div>
        `;

        product_url_og = "https://www.candybird.co.za/" + encodeURIComponent(imageUrl);
    });



    $('#quick-view .modal-body .quick-view-image-wrapper').html(`
          <div class="product-sync-init mb-20">
            ${productImagesHtml}
          </div>

          <div class="product-sync-nav">
            ${productThumbnailImagesHtml}
          </div>
    `);


    // initializeSlick();

    // Convert price to a number and then format it
    var formattedPrice = parseFloat(productData.price).toFixed(2);
    var price1 = parseFloat(productData.price).toFixed(2);
    var discount_amount = parseFloat(productData.discount_amount).toFixed(2);
    var discountedprice1 = (price1 - discount_amount).toFixed(2);

    
    var productTitle = productData.title;
    var productId = productData.id;
    var productCategory = productData.category_name;
    var quickStockNumber = getStockNumber(productData);
    var quickSoldOut = quickStockNumber !== null && quickStockNumber <= 0;

    // Dynamically collect all category levels
    const categories = [
        productData.parent_category,
        productData.child_category_1,
        productData.child_category_2
    ].filter(Boolean); // remove empty/null

    // Generate breadcrumb HTML
    let categoryLinksHtml = categories.map(cat => {
        return `<a href="products?category=${encodeURIComponent(cat)}">${cat}</a>`;
    }).join(' > ');

    // Append product title at the end
    categoryLinksHtml += ` > <span>${productData.title}</span>`;




    

    // Assume productData.rating is a number between 0 and 5
    var rating = parseFloat(productData.rating);

    // Function to convert rating to HTML stars
    function convertToStars(rating) {
        var fullStars = Math.floor(rating);
        var halfStar = rating % 1 !== 0;

        var starsHTML = '';

        for (var i = 0; i < fullStars; i++) {
            starsHTML += '<span class="star-on"><i class="fas fa-star"></i></span> ';
        }

        if (halfStar) {
            starsHTML += '<span class="star-on"><i class="fas fa-star-half-alt"></i></span> ';
        }

        // Add remaining empty stars if needed
        for (var j = fullStars + (halfStar ? 1 : 0); j < 5; j++) {
            starsHTML += '<span class="star-on de-selected"><i class="fas fa-star"></i></span> ';
        }

        return starsHTML;
    }
    // Update modal fields with the received product data
    // For example, you can use jQuery to set values like:
    $('#quick-view h2.title').text(productData.title);

    $('#quick-view .product-head').html(`
        <h2 class="title">
            ${productTitle}
        </h2>
        <h4 class="sub-title">${categoryLinksHtml}</h4>
        <div class="star-content mb-20">
            ${convertToStars(rating)}
        </div>
    `);

    $('#quick-view .product-footer').html(`
        ${quickSoldOut
          ? `<div class="my-4"><span class="sold-out-button d-inline-block" aria-disabled="true">Sold out</span></div>`
          : `<div class="product-count style d-flex flex-column flex-sm-row my-4">
              <div class="count d-flex">
                <input type="number" min="1" max="999" step="1" value="1" class="add-to-cart-quantity" />
                <div class="button-group">
                  <button class="count-btn increment">
                    <i class="fas fa-chevron-up"></i>
                  </button>
                  <button class="count-btn decrement">
                    <i class="fas fa-chevron-down"></i>
                  </button>
                </div>
              </div>
              <div>
                <button class="btn btn-dark btn--xl mt-5 mt-sm-0 add-to-cart" data-product-id="${productId}"${String(productData.is_clearance || '').toLowerCase() === 'yes' && productData.clearance_id ? ` data-clearance-id="${productData.clearance_id}"` : ''} data-toggle="modal" data-target="#add-to-cart"><span class="mr-2"><i class="ion-android-add"></i></span>Add to cart</button>
              </div>
            </div>`}
        <div class="addto-whish-list">
          <a href="#" class="add-to-wishlist" data-product-id="${productId}"><i class="icon-heart"></i> Add to wishlist</a>
          <a href="#" class="add-to-compare" data-product-id="${productId}"><i class="icon-shuffle"></i> Add to compare</a>
        </div>
        <div class="pro-social-links mt-10">
          <ul class="d-flex align-items-center">
            <li class="share">Share</li>
            <li>
              <a class="share-link-click" href="https://www.facebook.com/sharer/sharer.php?u=https://www.candybird.co.za/product?id=${productId}" target="_blank" rel="noopener noreferrer"><i class="ion-social-facebook"></i></a>
            </li>
            <li>
              <a class="share-link-click" href="https://twitter.com/intent/tweet?url=https://www.candybird.co.za/product?id=${productId}&text=Check out this amazing product!" target="_blank" rel="noopener noreferrer"><i class="ion-social-twitter"></i></a>
            </li>
            <li>
              <a target="_blank" class="share-link-click" href="https://www.pinterest.com/pin/create/button/"
               data-pin-do="buttonBookmark"
               data-pin-custom="true"
               data-pin-save="true"
               data-pin-url="https://www.candybird.co.za/product?id=${productId}"
               data-pin-media="${product_url_og}"
               >
               <i class="ion-social-pinterest"></i>
              </a>
            </li>
          </ul>
        </div>
    `);
                    
    // Add product description with a maximum limit of 300 characters
    var limitedDescription = productData.description.length > 300
        ? productData.description.substring(0, 300) + '...'
        : productData.description;

    $('#quick-view .product-body').html(`
        <span class="product-price">
          ${productData.discount_rate > 0 ? `<del class="del">R${parseFloat(productData.price).toFixed(2)}</del>` : ''}
          <span class="onsale">R${(parseFloat(productData.price) - parseFloat(productData.discount_amount)).toFixed(2)}</span>
        </span>
        </br>
        ${limitedDescription}
    `);
    
    if (productData.weight && productData.other_weights && productData.other_products_in_group) {
        var newWeights = productData.other_weights.split(',').map(weight => weight.trim());
        var newValues = productData.other_products_in_group.split(',').map(value => value.trim());

        if (newValues.length > 1) {
            var dimensionOptions = '';

            for (var i = 0; i < newValues.length; i++) {
                const selected = String(newValues[i]) === String(productData.id) ? ' selected' : '';
                dimensionOptions += `<option value="${newValues[i]}"${selected}>${newWeights[i]}</option>`;
            }

            $('#quick-view .product-size').html(`
                <h3 class="title" style="font-weight:900">SELECT PRODUCT SIZE:</h3>
                <select id="changeModalProduct">
                    ${dimensionOptions}
                </select>
            `);
        } else {
            $('#quick-view .product-size').html(`
                <h3 class="title" style="font-weight:900">SIZE:</h3>
                <span>${productData.weight}</span>
            `);
        }
    }
    
}

function clearModalContent() {
    // Clear or reset the content of your modal elements
    $('#quick-view h2.title').text('');
    $('#quick-view .product-head').html('');
    $('#quick-view .product-footer').html('');
    $('#quick-view .product-body').html('');
    $('#quick-view .product-size').html('');
}

// Handle size change
$(document).on('change', '#changeModalProduct', function () {
    const selectedId = $(this).val();
    const selectedProduct = ALL_PRODUCTS.find(p => p.id == selectedId);
    if (!selectedProduct) return;

    // Update title with size
    $('#quick-view h2.title').text(displayProductTitle(selectedProduct));

    // Get images or placeholder
    let images = getProductImages(selectedProduct);

    // Generate HTML for main slider & nav slider
    const mainHtml = images.map(img => `
        <div class="single-product">
            <div class="product-thumb"><img src="${img}" onerror="this.onerror=null;this.src='assets/img/product/1.png';" alt="product-thumb" /></div>
        </div>`).join('');

    const navHtml = images.map(img => `
        <div class="single-product">
            <div class="product-thumb">
                <a href="javascript:void(0)">
                    <img src="${img}" onerror="this.onerror=null;this.src='assets/img/product/1.png';" alt="product-thumb" />
                </a>
            </div>
        </div>`).join('');

    const wrapper = $('#quick-view .quick-view-image-wrapper');

    // Destroy slick if already initialized
    if (wrapper.find('.product-sync-init').hasClass('slick-initialized')) {
        wrapper.find('.product-sync-init').slick('unslick');
        wrapper.find('.product-sync-nav').slick('unslick');
    }

    // Update HTML
    wrapper.html(`
        <div class="product-sync-init mb-20">${mainHtml}</div>
        <div class="product-sync-nav">${navHtml}</div>
    `);

    // Initialize slick (works for 1 image too)
    wrapper.find('.product-sync-init').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: images.length > 1,
        draggable: false,
        arrows: false,
        dots: false,
        fade: images.length > 1,
        asNavFor: wrapper.find('.product-sync-nav')
    });

    wrapper.find('.product-sync-nav').slick({
        slidesToShow: Math.min(4, images.length),
        slidesToScroll: 1,
        infinite: images.length > 1,
        dots: false,
        arrows: true,
        prevArrow: '<button class="slick-prev"><i class="fas fa-arrow-left"></i></button>',
        nextArrow: '<button class="slick-next"><i class="fas fa-arrow-right"></i></button>',
        asNavFor: wrapper.find('.product-sync-init'),
        focusOnSelect: true,
        draggable: false
    });

    // Update price
    const price = parseFloat(selectedProduct.price || 0);
    const discount = parseFloat(selectedProduct.discount || 0);
    $('#quick-view .product-body .product-price').html(`
        ${discount > 0 ? `<del class="del">R${price.toFixed(2)}</del>` : ''}
        <span class="onsale">R${(price - discount).toFixed(2)}</span>
    `);

    // Update Add to Cart button
    $('#quick-view .add-to-cart')
      .attr('data-product-id', selectedProduct.id)
      .attr('data-clearance-id', String(selectedProduct.is_clearance || '').toLowerCase() === 'yes' ? (selectedProduct.clearance_id || '') : '');
});

});

</script>


<?php
include 'footer.php';
?>
