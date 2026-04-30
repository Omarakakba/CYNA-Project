<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/pagination.php';
session_start();

$db         = getDB();
$categories = $db->query('SELECT c.*, COUNT(p.id) AS nb FROM category c LEFT JOIN product p ON p.category_id = c.id GROUP BY c.id ORDER BY c.name')->fetchAll();

// --- Filtres
$selected_cats = array_filter(array_map('intval', (array)($_GET['cats'] ?? [])));
$price_min     = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float)$_GET['price_min'] : 0;
$price_max     = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : 9999;
$sort          = in_array($_GET['sort'] ?? '', ['price_asc', 'price_desc', 'name_asc']) ? $_GET['sort'] : 'name_asc';
$per_page      = 9;
$current_page  = max(1, (int)($_GET['page'] ?? 1));

$order_sql = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    default      => 'p.name ASC',
};

$where  = ['p.price >= ?', 'p.price <= ?'];
$params = [$price_min, $price_max];

if (!empty($selected_cats)) {
    $placeholders = implode(',', array_fill(0, count($selected_cats), '?'));
    $where[]  = "p.category_id IN ($placeholders)";
    $params   = array_merge($params, $selected_cats);
}

$where_sql = implode(' AND ', $where);

// Compte total pour pagination
$count_stmt = $db->prepare("SELECT COUNT(*) FROM product p JOIN category c ON p.category_id = c.id WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

// URL de base pour la pagination (sans 'page')
$get_without_page = array_diff_key($_GET, ['page' => 1]);
$base_url = '/cyna/catalogue.php' . ($get_without_page ? '?' . http_build_query($get_without_page) : '');
$pagination = paginate($total, $per_page, $current_page, $base_url);

$sql  = "SELECT p.*, c.name AS category_name FROM product p JOIN category c ON p.category_id = c.id WHERE $where_sql ORDER BY $order_sql LIMIT $per_page OFFSET {$pagination['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Prix min / max global pour le slider
$range = $db->query('SELECT MIN(price) AS mn, MAX(price) AS mx FROM product')->fetch();
$global_min = (int)floor($range['mn'] ?? 0);
$global_max = (int)ceil($range['mx'] ?? 500);

$page_title = 'Catalogue';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Catalogue des solutions</h1>
        <p>Abonnements SaaS de cybersécurité pour protéger votre entreprise à chaque niveau</p>
    </div>
</div>

<div class="page-content">
<form method="GET" action="/cyna/catalogue.php" id="filter-form">
<div class="catalogue-layout">

    <!-- ===== PANNEAU FILTRES ===== -->
    <aside class="filter-panel">
        <div class="filter-panel-title">
            Filtres
            <a href="/cyna/catalogue.php">Réinitialiser</a>
        </div>

        <div class="filter-group">
            <div class="filter-group-label">Catégorie</div>
            <?php foreach ($categories as $cat): ?>
            <label class="filter-checkbox">
                <input type="checkbox" name="cats[]" value="<?= (int)$cat['id'] ?>"
                    <?= in_array((int)$cat['id'], $selected_cats) ? 'checked' : '' ?>>
                <span><?= escape($cat['name']) ?></span>
                <span class="count"><?= (int)$cat['nb'] ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="filter-group">
            <div class="filter-group-label">Prix mensuel (€)</div>
            <div class="price-range-wrap">
                <div class="price-range-inputs">
                    <input type="number" name="price_min" id="price_min"
                           min="<?= $global_min ?>" max="<?= $global_max ?>"
                           value="<?= $price_min > 0 ? $price_min : '' ?>"
                           placeholder="Min">
                    <span class="price-range-sep">—</span>
                    <input type="number" name="price_max" id="price_max_input"
                           min="<?= $global_min ?>" max="<?= $global_max ?>"
                           value="<?= $price_max < 9999 ? $price_max : '' ?>"
                           placeholder="Max">
                </div>
                <input type="range" id="price_slider"
                       min="<?= $global_min ?>" max="<?= $global_max ?>"
                       value="<?= min($price_max < 9999 ? $price_max : $global_max, $global_max) ?>">
                <div class="price-display">
                    <span><?= $global_min ?> €</span>
                    <span><?= $global_max ?> €</span>
                </div>
            </div>
        </div>

        <div class="filter-group">
            <div class="filter-group-label">Tri</div>
            <select name="sort" class="sort-select" style="width:100%;">
                <option value="name_asc"   <?= $sort === 'name_asc'   ? 'selected' : '' ?>>Nom A → Z</option>
                <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Prix croissant</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
            </select>
        </div>

        <button type="submit" class="filter-apply-btn">
            <i class="fa-solid fa-magnifying-glass"></i> Appliquer
        </button>
    </aside>

    <!-- ===== RÉSULTATS ===== -->
    <div class="catalogue-results">

        <div class="catalogue-topbar">
            <div class="catalogue-count">
                <strong><?= $total ?></strong> solution<?= $total > 1 ? 's' : '' ?> — page <?= $pagination['current'] ?> / <?= $pagination['pages'] ?>
            </div>
        </div>

        <?php if (!empty($selected_cats) || $price_min > 0 || $price_max < 9999): ?>
        <div class="active-filters">
            <?php foreach ($selected_cats as $cid):
                $cname = '';
                foreach ($categories as $c) { if ($c['id'] == $cid) { $cname = $c['name']; break; } }
            ?>
            <span class="active-filter-tag">
                <?= escape($cname) ?>
                <a href="<?= '/cyna/catalogue.php?' . http_build_query(array_merge($_GET, ['cats' => array_values(array_filter($selected_cats, fn($x) => $x != $cid))])) ?>"><i class="fa-solid fa-xmark"></i></a>
            </span>
            <?php endforeach; ?>
            <?php if ($price_min > 0 || $price_max < 9999): ?>
            <span class="active-filter-tag">
                <?= $price_min > 0 ? $price_min . ' €' : '0 €' ?> — <?= $price_max < 9999 ? $price_max . ' €' : '∞' ?>
                <a href="<?= '/cyna/catalogue.php?' . http_build_query(array_diff_key($_GET, ['price_min'=>1,'price_max'=>1])) ?>"><i class="fa-solid fa-xmark"></i></a>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
            <h3>Aucun produit trouvé</h3>
            <p>Modifiez vos filtres pour obtenir des résultats.</p>
            <a href="/cyna/catalogue.php" class="btn btn-primary" style="margin-top:1.25rem;">Voir tout le catalogue</a>
        </div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= escape($product['image_url']) ?>" alt="<?= escape($product['name']) ?>"
                         style="width:100%;height:120px;object-fit:cover;border-radius:8px 8px 0 0;margin:-1.5rem -1.5rem 1rem;width:calc(100% + 3rem);"
                         onerror="this.style.display='none'">
                <?php endif; ?>
                <div class="product-badge"><?= escape($product['category_name']) ?></div>
                <?php if (!$product['is_available']): ?>
                    <span class="availability-badge unavailable" style="font-size:0.7rem;padding:0.15rem 0.5rem;">
                        <i class="fa-solid fa-clock"></i> Indisponible
                    </span>
                <?php endif; ?>
                <h2><?= escape($product['name']) ?></h2>
                <p><?= escape($product['description']) ?></p>
                <div class="product-footer">
                    <div class="price"><?= number_format($product['price'], 2) ?> € <span>/ mois</span></div>
                    <a href="/cyna/produit.php?id=<?= (int)$product['id'] ?>" class="btn-card">
                        Détails <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?= $pagination['html'] ?>
        <?php endif; ?>

    </div>
</div>
</form>
</div>

<script>
(function () {
    const slider   = document.getElementById('price_slider');
    const maxInput = document.getElementById('price_max_input');
    if (slider && maxInput) {
        slider.addEventListener('input', () => { maxInput.value = slider.value; });
        maxInput.addEventListener('input', () => { if (maxInput.value) slider.value = maxInput.value; });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
