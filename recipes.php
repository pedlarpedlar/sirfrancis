<?php
include 'session_logins.php';
include 'header.php';

$page_url_canonical = "https://www.candybird.co.za/recipes";
$title_og = 'Recipes - CandyBird';
$page_url_og = $page_url_canonical;
$description_og = 'Browse CandyBird recipes for nuts, dried fruit, snacks, desserts, breakfasts, savoury dishes and gifting ideas.';
$description_meta = $description_og;

include 'page_menues.php';
include 'recipe_posts.php';

function cbRecipeText($value) {
    $value = str_replace(['CandyBirdâ„¢', 'â€™', 'Â°F', 'Â°C'], ['CandyBird', "'", 'F', 'C'], (string) $value);
    return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
}

function cbRecipeCleanHtml($value) {
    return str_replace(['CandyBirdâ„¢', 'â€™', 'Â°F', 'Â°C'], ['CandyBird', "'", 'F', 'C'], (string) $value);
}

function cbRecipeExcerpt($value, $limit = 145) {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags(cbRecipeCleanHtml($value))));
    if (strlen($text) <= $limit) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars(substr($text, 0, $limit - 3) . '...', ENT_QUOTES, 'UTF-8');
}

function cbRecipeImage($image, $id) {
    $thumbPath = __DIR__ . '/assets/img/blog-post/' . basename((string) $image);
    if ($image && is_file($thumbPath)) {
        return 'assets/img/blog-post/' . rawurlencode(basename((string) $image));
    }

    $largePath = __DIR__ . '/assets/img/blog-post/large-blog/' . basename((string) $image);
    if ($image && is_file($largePath)) {
        return 'assets/img/blog-post/large-blog/' . rawurlencode(basename((string) $image));
    }

    $fallbackNumber = (($id - 1) % 5) + 1;
    return 'assets/img/blog-post/' . $fallbackNumber . '.png';
}

$selectedCategory = trim((string) ($_GET['category'] ?? ''));
$categoryCounts = [];
$recipes = [];

foreach ($blogPosts as $post) {
    $category = trim((string) ($post['category'] ?? 'Uncategorised'));
    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;

    if ($selectedCategory !== '' && strcasecmp($selectedCategory, $category) !== 0) {
        continue;
    }

    $recipes[] = $post;
}

ksort($categoryCounts);

$featuredRecipes = array_slice($recipes, 0, 3);
$totalRecipeCount = count($blogPosts);
$visibleRecipeCount = count($recipes);
?>

<title>Recipes - <?=$website_company_name?></title>

<style>
    .recipes-page { background: #fbfaf7; color: #251d18; }
    .recipes-hero { background: #fff7ed; border-bottom: 1px solid #eadfd2; padding: 46px 0 34px; }
    .recipes-hero h1 { color: #251d18; font-size: clamp(2.25rem, 5vw, 4rem); line-height: 1.05; margin: 0 0 12px; }
    .recipes-hero p { color: #5d514b; max-width: 820px; font-size: 1.08rem; line-height: 1.7; }
    .recipes-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
    .recipes-actions a { border-radius: 6px; padding: 12px 16px; font-weight: 800; }
    .recipes-actions .primary { background: #2a1b1b; color: #fff; }
    .recipes-actions .secondary { background: #FCB42F; color: #251d18; }
    .recipes-wrap { padding: 34px 0 68px; }
    .recipes-toolbar { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; padding: 16px; box-shadow: 0 12px 34px rgba(71,44,22,.06); margin-bottom: 20px; }
    .recipes-toolbar-row { display: grid; grid-template-columns: minmax(220px, 1fr) auto; gap: 12px; align-items: center; }
    .recipes-search { width: 100%; border: 1px solid #e5d6c7; border-radius: 6px; padding: 12px 13px; background: #fff; }
    .recipes-count { color: #6b5f58; font-weight: 700; white-space: nowrap; }
    .recipe-cats { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
    .recipe-cat { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #e5d6c7; color: #3a2a23; background: #fffaf2; padding: 8px 10px; border-radius: 999px; font-weight: 800; font-size: .9rem; }
    .recipe-cat.active { background: #2a1b1b; color: #fff; border-color: #2a1b1b; }
    .featured-grid { display: grid; grid-template-columns: 1.25fr 1fr 1fr; gap: 16px; margin-bottom: 20px; }
    .featured-card, .recipe-card, .recipes-info { background: #fff; border: 1px solid #eee1d4; border-radius: 8px; overflow: hidden; box-shadow: 0 12px 34px rgba(71,44,22,.06); }
    .featured-card img, .recipe-card img { width: 100%; height: 190px; object-fit: cover; display: block; background: #efe5dc; }
    .featured-card:first-child img { height: 260px; }
    .recipe-body { padding: 17px; }
    .recipe-kicker { color: #c96f38; font-size: .78rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
    .recipe-body h2, .recipe-body h3 { color: #251d18; margin: 7px 0 8px; font-size: 1.15rem; line-height: 1.25; }
    .featured-card:first-child .recipe-body h2 { font-size: 1.45rem; }
    .recipe-body p { color: #5d514b; line-height: 1.65; margin-bottom: 12px; }
    .recipe-meta { display: flex; flex-wrap: wrap; gap: 8px; color: #6b5f58; font-size: .9rem; margin-bottom: 12px; }
    .recipe-meta span { background: #fbfaf7; border: 1px solid #eee1d4; border-radius: 999px; padding: 5px 8px; }
    .recipe-link { color: #5b1178; font-weight: 900; }
    .recipes-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .recipes-info { padding: clamp(20px, 4vw, 30px); margin-top: 22px; }
    .recipes-info h2 { color: #251d18; font-size: 1.3rem; margin-bottom: 10px; }
    .recipes-info p, .recipes-info li { color: #5d514b; line-height: 1.75; }
    .recipes-empty { display: none; background: #fff; border: 1px solid #eee1d4; border-radius: 8px; padding: 24px; color: #5d514b; }
    @media (max-width: 991px) { .featured-grid, .recipes-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .featured-card:first-child { grid-column: 1 / -1; } }
    @media (max-width: 575px) { .featured-grid, .recipes-grid, .recipes-toolbar-row { grid-template-columns: 1fr; } .recipes-count { white-space: normal; } .recipes-hero { padding: 36px 0 30px; } }
</style>

<main class="recipes-page">
    <section class="recipes-hero">
        <div class="container">
            <h1>Recipes</h1>
            <p>Ideas for using nuts, dried fruit, snacks and pantry favourites in everyday food, treats, gifting and family tables. Browse by category, search by ingredient, or print a recipe to keep in the kitchen.</p>
            <div class="recipes-actions">
                <a class="primary" href="products">Shop ingredients</a>
                <a class="secondary" href="contact">Send us a recipe</a>
            </div>
        </div>
    </section>

    <section class="recipes-wrap">
        <div class="container">
            <div class="recipes-toolbar">
                <div class="recipes-toolbar-row">
                    <input type="search" class="recipes-search" id="recipeSearch" placeholder="Search recipes, ingredients or categories">
                    <div class="recipes-count"><?= number_format($visibleRecipeCount) ?> recipe<?= $visibleRecipeCount === 1 ? '' : 's' ?><?= $selectedCategory ? ' in ' . cbRecipeText($selectedCategory) : '' ?></div>
                </div>
                <div class="recipe-cats">
                    <a class="recipe-cat <?= $selectedCategory === '' ? 'active' : '' ?>" href="recipes">All <span><?= number_format($totalRecipeCount) ?></span></a>
                    <?php foreach ($categoryCounts as $categoryName => $count): ?>
                        <a class="recipe-cat <?= strcasecmp($selectedCategory, $categoryName) === 0 ? 'active' : '' ?>" href="recipes?category=<?= urlencode($categoryName) ?>">
                            <?= cbRecipeText($categoryName) ?> <span><?= number_format($count) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($featuredRecipes)): ?>
                <div class="featured-grid">
                    <?php foreach ($featuredRecipes as $recipe): ?>
                        <?php
                            $recipeId = (int) ($recipe['id'] ?? 0);
                            $recipeTitle = cbRecipeText($recipe['title'] ?? 'Recipe');
                            $recipeCategory = cbRecipeText($recipe['category'] ?? 'Recipe');
                            $recipeImage = cbRecipeImage($recipe['img'] ?? '', $recipeId);
                        ?>
                        <article class="featured-card recipe-item" data-search="<?= strtolower($recipeTitle . ' ' . $recipeCategory . ' ' . cbRecipeText($recipe['intro'] ?? '')) ?>">
                            <a href="recipe?id=<?= $recipeId ?>"><img src="<?= htmlspecialchars($recipeImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $recipeTitle ?>"></a>
                            <div class="recipe-body">
                                <div class="recipe-kicker"><?= $recipeCategory ?></div>
                                <h2><a href="recipe?id=<?= $recipeId ?>"><?= $recipeTitle ?></a></h2>
                                <div class="recipe-meta">
                                    <span><?= cbRecipeText($recipe['cook_time'] ?? 'Time varies') ?></span>
                                    <span><?= cbRecipeText($recipe['servings'] ?? 'Servings vary') ?> servings</span>
                                </div>
                                <p><?= cbRecipeExcerpt($recipe['intro'] ?? '') ?></p>
                                <a class="recipe-link" href="recipe?id=<?= $recipeId ?>">Open recipe</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="recipes-empty" id="recipesEmpty">No recipes matched your search. Try a broader ingredient or choose another category.</div>

            <div class="recipes-grid" id="recipesGrid">
                <?php foreach ($recipes as $recipe): ?>
                    <?php
                        $recipeId = (int) ($recipe['id'] ?? 0);
                        $recipeTitle = cbRecipeText($recipe['title'] ?? 'Recipe');
                        $recipeCategory = cbRecipeText($recipe['category'] ?? 'Recipe');
                        $recipeImage = cbRecipeImage($recipe['img'] ?? '', $recipeId);
                        $searchText = strtolower($recipeTitle . ' ' . $recipeCategory . ' ' . cbRecipeText($recipe['intro'] ?? '') . ' ' . cbRecipeText($recipe['ingredients'] ?? ''));
                    ?>
                    <article class="recipe-card recipe-item" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                        <a href="recipe?id=<?= $recipeId ?>"><img src="<?= htmlspecialchars($recipeImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $recipeTitle ?>" loading="lazy"></a>
                        <div class="recipe-body">
                            <div class="recipe-kicker"><?= $recipeCategory ?></div>
                            <h3><a href="recipe?id=<?= $recipeId ?>"><?= $recipeTitle ?></a></h3>
                            <div class="recipe-meta">
                                <span><?= cbRecipeText($recipe['cook_time'] ?? 'Time varies') ?></span>
                                <span><?= cbRecipeText($recipe['servings'] ?? 'Servings vary') ?> servings</span>
                            </div>
                            <p><?= cbRecipeExcerpt($recipe['intro'] ?? '') ?></p>
                            <a class="recipe-link" href="recipe?id=<?= $recipeId ?>">View ingredients and method</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="recipes-info">
                <h2>Share a CandyBird kitchen idea</h2>
                <p>Customers are welcome to send family recipes, snack ideas, lunchbox combinations, gifting ideas or pantry tips using CandyBird ingredients. Recipes may be reviewed before publishing, and copyrighted material should only be shared with permission.</p>
                <ul>
                    <li>Email recipes to <a href="mailto:recipes@candybird.co.za">recipes@candybird.co.za</a>.</li>
                    <li>Include your name, recipe title, ingredients, method, serving size and a photo if you have one.</li>
                    <li>Browse ingredients from the <a href="products">online shop</a> or use the <a href="pricelist">pricelist</a> for planning bigger kitchen orders.</li>
                </ul>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var search = document.getElementById('recipeSearch');
    var items = Array.prototype.slice.call(document.querySelectorAll('.recipe-item'));
    var empty = document.getElementById('recipesEmpty');

    if (!search) {
        return;
    }

    search.addEventListener('input', function() {
        var term = search.value.trim().toLowerCase();
        var visible = 0;
        items.forEach(function(item) {
            var match = !term || (item.getAttribute('data-search') || '').indexOf(term) !== -1;
            item.style.display = match ? '' : 'none';
            if (match) {
                visible++;
            }
        });
        if (empty) {
            empty.style.display = visible ? 'none' : 'block';
        }
    });
});
</script>

<?php include 'footer.php'; ?>
