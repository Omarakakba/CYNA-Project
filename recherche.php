<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
session_start();

$db = getDB();

$q         = trim($_GET['q']         ?? '');
$cat_ids   = array_map('intval', (array)($_GET['cats'] ?? []));
$price_min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float)$_GET['price_min'] : null;
$price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : null;
$avail     = isset($_GET['available']) ? (int)$_GET['available'] : null;
$sort_map  = ['name_asc' => 'p.name ASC', 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC', 'avail' => 'p.is_available DESC, p.name ASC'];
$sort_key  = array_key_exists($_GET['sort'] ?? '', $sort_map) ? $_GET['sort'] : 'name_asc';
$order_sql = $sort_map[$sort_key];

$categories = $db->query('SELECT * FROM category ORDER BY name')->fetchAll();

// Construction requête dynamique
$where  = ['1=1'];
$params = [];

if ($q !== '') {
    $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if (!empty($cat_ids)) {
    $in = implode(',', array_fill(0, count($cat_ids), '?'));
    $where[]  = "p.category_id IN ($in)";
    $params   = array_merge($params, $cat_ids);
}
if ($price_min !== null) {
    $where[]  = 'p.price >= ?';
    $params[] = $price_min;
}
if ($price_max !== null) {
    $where[]  = 'p.price <= ?';
    $params[] = $price_max;
}
if ($avail !== null) {
    $where[]  = 'p.is_available = ?';
    $params[] = $avail;
}

$sql  = 'SELECT p.*, c.name AS category_name FROM product p JOIN category c ON p.category_id = c.id WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order_sql;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

$page_title = $q !== '' ? 'Recherche : ' . $q : 'Recherche';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Recherche</h1>
        <?php if ($q !== ''): ?>
            <p><?= count($results) ?> résultat<?= count($results) > 1 ? 's' : '' ?> pour « <?= escape($q) ?> »</p>
        <?php else: ?>
            <p>Trouvez la solution adaptée à vos besoins</p>
        <?php endif; ?>
    </div>
</div>

<div class="page-content">
    <div class="catalogue-layout">

        <!-- Filtres latéraux -->
        <aside class="filter-panel">
            <form method="GET" action="/cyna/recherche.php" id="search-filter-form">

                <div class="filter-group">
                    <label class="filter-label">Mot-clé</label>
                    <div style="position:relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.85rem;"></i>
                        <input type="search" name="q" value="<?= escape($q) ?>"
                               placeholder="Rechercher…"
                               style="width:100%; padding:0.55rem 0.75rem 0.55rem 2.2rem; background:var(--surface); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.9rem; box-sizing:border-box;">
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Catégories</label>
                    <?php foreach ($categories as $cat): ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="cats[]" value="<?= $cat['id'] ?>"
                                   <?= in_array((int)$cat['id'], $cat_ids) ? 'checked' : '' ?>>
                            <?= escape($cat['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Prix / mois (€)</label>
                    <div class="price-range-wrap">
                        <input type="number" name="price_min" id="priceMin"
                               value="<?= $price_min !== null ? $price_min : '' ?>"
                               min="0" step="1" placeholder="Min">
                        <span>—</span>
                        <input type="number" name="price_max" id="priceMax"
                               value="<?= $price_max !== null ? $price_max : '' ?>"
                               min="0" step="1" placeholder="Max">
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Disponibilité</label>
                    <label class="filter-checkbox">
                        <input type="radio" name="available" value="" <?= $avail === null ? 'checked' : '' ?>>
                        Tous les produits
                    </label>
                    <label class="filter-checkbox">
                        <input type="radio" name="available" value="1" <?= $avail === 1 ? 'checked' : '' ?>>
                        Disponibles uniquement
                    </label>
                    <label class="filter-checkbox">
                        <input type="radio" name="available" value="0" <?= $avail === 0 ? 'checked' : '' ?>>
                        Indisponibles
                    </label>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Trier par</label>
                    <select name="sort" style="width:100%;padding:0.55rem 0.75rem;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:0.9rem;">
                        <option value="name_asc"   <?= $sort_key==='name_asc'   ? 'selected' : '' ?>>Nom A → Z</option>
                        <option value="price_asc"  <?= $sort_key==='price_asc'  ? 'selected' : '' ?>>Prix croissant</option>
                        <option value="price_desc" <?= $sort_key==='price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                        <option value="avail"      <?= $sort_key==='avail'      ? 'selected' : '' ?>>Disponibles en premier</option>
                    </select>
                </div>

                <button type="submit" class="filter-apply-btn">
                    <i class="fa-solid fa-magnifying-glass"></i> Rechercher
                </button>

                <?php if ($q !== '' || !empty($cat_ids) || $price_min !== null || $price_max !== null || $avail !== null): ?>
                    <a href="/cyna/recherche.php" class="filter-reset-btn">
                        <i class="fa-solid fa-xmark"></i> Réinitialiser
                    </a>
                <?php endif; ?>
            </form>
        </aside>

        <!-- Résultats -->
        <div class="catalogue-results">
            <?php if ($q === '' && empty($cat_ids) && $price_min === null && $price_max === null && $avail === null): ?>
                <div class="empty-state" style="padding:4rem 2rem; text-align:center;">
                    <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h3>Lancez votre recherche</h3>
                    <p>Utilisez le formulaire à gauche pour trouver une solution.</p>
                </div>

            <?php elseif (empty($results)): ?>
                <div class="empty-state" style="padding:4rem 2rem; text-align:center;">
                    <div class="empty-icon"><i class="fa-solid fa-box-open"></i></div>
                    <h3>Aucun résultat</h3>
                    <p>Aucune solution ne correspond à votre recherche. Essayez avec d'autres termes.</p>
                    <a href="/cyna/catalogue.php" class="btn btn-primary" style="margin-top:1.5rem;">
                        Voir tout le catalogue
                    </a>
                </div>

            <?php else: ?>
                <div class="search-results-header">
                    <span><?= count($results) ?> solution<?= count($results) > 1 ? 's' : '' ?> trouvée<?= count($results) > 1 ? 's' : '' ?></span>
                    <?php $sort_labels = ['name_asc'=>'Nom A→Z','price_asc'=>'Prix ↑','price_desc'=>'Prix ↓','avail'=>'Disponibles d\'abord']; ?>
                    <span style="font-size:0.8rem;color:var(--text-muted);">Tri : <?= $sort_labels[$sort_key] ?></span>
                </div>
                <div class="products-grid" style="margin-top:1rem;">
                    <?php foreach ($results as $p): ?>
                        <div class="product-card <?= !$p['is_available'] ? 'product-unavailable' : '' ?>">
                            <div class="product-badge"><?= escape($p['category_name']) ?></div>
                            <?php if (!$p['is_available']): ?>
                                <span class="availability-badge unavailable">
                                    <i class="fa-solid fa-clock"></i> Momentanément indisponible
                                </span>
                            <?php else: ?>
                                <span class="availability-badge available">
                                    <i class="fa-solid fa-circle-check"></i> Disponible immédiatement
                                </span>
                            <?php endif; ?>
                            <h2><?= escape($p['name']) ?></h2>
                            <p><?= escape($p['description']) ?></p>
                            <?php if ($q !== ''): ?>
                                <p style="font-size:0.75rem; color:var(--accent); margin-bottom:0.5rem;">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    Correspond à « <?= escape($q) ?> »
                                </p>
                            <?php endif; ?>
                            <div class="product-footer">
                                <div class="price"><?= number_format($p['price'], 2) ?> € <span>/ mois</span></div>
                                <a href="/cyna/produit.php?id=<?= (int)$p['id'] ?>" class="btn-card">
                                    Détails <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
