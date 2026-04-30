<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
session_start();

$db  = getDB();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare('SELECT p.*, c.name AS category_name FROM product p JOIN category c ON p.category_id = c.id WHERE p.id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { http_response_code(404); die('Produit introuvable.'); }

$stmt2 = $db->prepare('SELECT p.*, c.name AS category_name FROM product p JOIN category c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY p.is_available DESC, RAND() LIMIT 6');
$stmt2->execute([$product['category_id'], $id]);
$related = $stmt2->fetchAll();

$page_title = $product['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <div class="breadcrumb">
            <a href="/cyna/catalogue.php">Catalogue</a> &rsaquo; <?= escape($product['category_name']) ?>
        </div>
        <h1><?= escape($product['name']) ?></h1>
    </div>
</div>

<div class="page-content">
    <div class="product-detail">

        <div>
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= escape($product['image_url']) ?>"
                     alt="<?= escape($product['name']) ?>"
                     style="width:100%;max-height:280px;object-fit:cover;border-radius:var(--radius);margin-bottom:1.5rem;border:1px solid var(--border);"
                     onerror="this.style.display='none'">
            <?php endif; ?>

            <div class="product-badge" style="margin-bottom:1.25rem;"><?= escape($product['category_name']) ?></div>

            <?php if ($product['is_available']): ?>
                <span class="availability-badge available" style="margin-bottom:1rem;display:inline-flex;">
                    <i class="fa-solid fa-circle-check"></i> Disponible immédiatement
                </span>
            <?php else: ?>
                <span class="availability-badge unavailable" style="margin-bottom:1rem;display:inline-flex;">
                    <i class="fa-solid fa-clock"></i> Service momentanément indisponible
                </span>
            <?php endif; ?>

            <h2 style="font-size:1.5rem; font-weight:800; color:var(--primary); margin-bottom:0.85rem;">
                <?= escape($product['name']) ?>
            </h2>
            <p style="color:var(--text-muted); line-height:1.8; margin-bottom:<?= empty($product['long_description']) ? '2rem' : '1rem' ?>;">
                <?= escape($product['description']) ?>
            </p>

            <?php if (!empty($product['long_description'])): ?>
                <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1.25rem;margin-bottom:2rem;font-size:0.9rem;color:var(--text-muted);line-height:1.8;white-space:pre-line;">
                    <?= escape($product['long_description']) ?>
                </div>
            <?php endif; ?>

            <h3 style="font-size:0.9rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.75rem;">
                Inclus dans l'abonnement
            </h3>
            <div class="product-features">
                <div class="product-feature-item">
                    <i class="fa-solid fa-check"></i> Déploiement en moins de 5 minutes
                </div>
                <div class="product-feature-item">
                    <i class="fa-solid fa-check"></i> Support technique 24/7 inclus
                </div>
                <div class="product-feature-item">
                    <i class="fa-solid fa-check"></i> Mises à jour automatiques
                </div>
                <div class="product-feature-item">
                    <i class="fa-solid fa-check"></i> Conformité RGPD &amp; NIS2
                </div>
                <div class="product-feature-item">
                    <i class="fa-solid fa-check"></i> Sans engagement — résiliable à tout moment
                </div>
                <div class="product-feature-item">
                    <i class="fa-solid fa-check"></i> Tableau de bord de supervision inclus
                </div>
            </div>
        </div>

        <div class="product-pricing-card">
            <div class="pricing-label">À partir de</div>
            <div class="pricing-amount">
                <?= number_format($product['price'], 2) ?> €
                <span>/ mois</span>
            </div>
            <div class="pricing-note">Par utilisateur &bull; Facturation mensuelle</div>
            <hr class="pricing-divider">

            <?php if (!$product['is_available']): ?>
                <button class="btn btn-primary btn-full" disabled style="opacity:.45;cursor:not-allowed;">
                    <i class="fa-solid fa-ban"></i> Service indisponible
                </button>
            <?php else: ?>
                <form method="POST" action="/cyna/panier.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    <input type="hidden" name="action" value="add">
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fa-solid fa-bolt"></i> S'abonner maintenant
                    </button>
                </form>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <p style="font-size:0.78rem;color:var(--text-muted);text-align:center;margin-top:0.5rem;">
                    <i class="fa-solid fa-circle-info"></i> Connexion requise uniquement au moment de payer
                </p>
                <?php endif; ?>
            <?php endif; ?>

            <a href="/cyna/catalogue.php" class="btn btn-secondary btn-full" style="margin-top:0.75rem;">
                <i class="fa-solid fa-arrow-left"></i> Retour au catalogue
            </a>

            <div class="pricing-guarantee">
                <i class="fa-solid fa-lock"></i> Paiement sécurisé Stripe<br>
                <i class="fa-solid fa-rotate-left"></i> Essai 14 jours sans CB
            </div>
        </div>

    </div>

    <?php if (!empty($related)): ?>
    <div style="margin-top:4rem;">
        <h3 style="font-size:1.1rem; font-weight:700; color:var(--primary); margin-bottom:1.25rem;">
            Solutions similaires
        </h3>
        <div class="products-grid">
            <?php foreach ($related as $r): ?>
            <div class="product-card">
                <div class="product-badge"><?= escape($r['category_name']) ?></div>
                <?php if ($r['is_available']): ?>
                    <span class="availability-badge available" style="font-size:0.7rem;padding:0.2rem 0.6rem;margin-bottom:0.5rem;display:inline-flex;">
                        <i class="fa-solid fa-circle-check"></i> Disponible
                    </span>
                <?php else: ?>
                    <span class="availability-badge unavailable" style="font-size:0.7rem;padding:0.2rem 0.6rem;margin-bottom:0.5rem;display:inline-flex;">
                        <i class="fa-solid fa-clock"></i> Indisponible
                    </span>
                <?php endif; ?>
                <h2><?= escape($r['name']) ?></h2>
                <p><?= escape($r['description']) ?></p>
                <div class="product-footer">
                    <div class="price"><?= number_format($r['price'], 2) ?> € <span>/ mois</span></div>
                    <a href="/cyna/produit.php?id=<?= (int)$r['id'] ?>" class="btn-card">
                        Détails <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
