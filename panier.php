<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
// Pas de requireLogin() — le panier est accessible aux visiteurs non connectés

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: /cyna/panier.php'); exit;
    }
    $action     = $_POST['action']     ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($action === 'add' && $product_id > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$product_id]['qty']      = ($_SESSION['cart'][$product_id]['qty'] ?? 0) + 1;
        $_SESSION['cart'][$product_id]['duration'] = $_POST['duration'] ?? 'monthly';
    } elseif ($action === 'remove' && $product_id > 0) {
        unset($_SESSION['cart'][$product_id]);
    } elseif ($action === 'update' && $product_id > 0) {
        $qty      = max(1, (int)($_POST['qty'] ?? 1));
        $duration = in_array($_POST['duration'] ?? '', ['monthly','annual']) ? $_POST['duration'] : 'monthly';
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['qty']      = $qty;
            $_SESSION['cart'][$product_id]['duration'] = $duration;
        }
    }
    header('Location: /cyna/panier.php'); exit;
}

// Normalisation : migration depuis l'ancien format de panier (int simple)
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $val) {
        if (!is_array($val)) {
            $_SESSION['cart'][$pid] = ['qty' => (int)$val, 'duration' => 'monthly'];
        }
    }
}

$cart_items = [];
$total_ht   = 0.0;

if (!empty($_SESSION['cart'])) {
    $ids      = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $products = $db->query("SELECT * FROM product WHERE id IN ($ids)")->fetchAll();
    foreach ($products as $p) {
        $entry    = $_SESSION['cart'][$p['id']];
        $qty      = (int)($entry['qty'] ?? 1);
        $duration = $entry['duration'] ?? 'monthly';
        $factor   = ($duration === 'annual') ? 10 : 1; // annuel = 10 mois au lieu de 12 (remise 2 mois)
        $unit_price = $duration === 'annual' ? round($p['price'] * 10, 2) : $p['price'];
        $cart_items[] = array_merge($p, ['qty' => $qty, 'duration' => $duration, 'unit_price' => $unit_price]);
        $total_ht    += $unit_price * $qty;
    }
}

$tva       = $total_ht * 0.20;
$total_ttc = $total_ht + $tva;

$page_title = 'Mon panier';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Mon panier</h1>
        <p><?= count($cart_items) ?> article<?= count($cart_items) > 1 ? 's' : '' ?></p>
    </div>
</div>

<?php if (isset($_GET['cancelled'])): ?>
<div class="page-content" style="padding-bottom:0;">
    <div class="alert alert-error">
        <i class="fa-solid fa-xmark-circle"></i>
        Paiement annulé — votre panier a été conservé.
    </div>
</div>
<?php endif; ?>

<div class="page-content">
    <?php if (empty($cart_items)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <h3>Votre panier est vide</h3>
            <p>Découvrez nos solutions de cybersécurité.</p>
            <a href="/cyna/catalogue.php" class="btn btn-primary" style="margin-top:1.5rem;">
                Voir le catalogue
            </a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="product-badge"><?= escape($item['name']) ?></div>
                        <h3><?= escape($item['name']) ?></h3>
                        <p style="font-size:0.85rem;color:var(--text-muted);"><?= escape($item['description']) ?></p>
                    </div>
                    <div class="cart-item-controls">
                        <form method="POST" class="cart-update-form">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">

                            <!-- Durée abonnement -->
                            <div class="cart-duration-wrap">
                                <label class="cart-duration-opt">
                                    <input type="radio" name="duration" value="monthly"
                                           <?= $item['duration'] === 'monthly' ? 'checked' : '' ?>
                                           onchange="this.form.submit()">
                                    <span>
                                        Mensuel<br>
                                        <strong><?= number_format($item['price'], 2) ?> € / mois</strong>
                                    </span>
                                </label>
                                <label class="cart-duration-opt">
                                    <input type="radio" name="duration" value="annual"
                                           <?= $item['duration'] === 'annual' ? 'checked' : '' ?>
                                           onchange="this.form.submit()">
                                    <span>
                                        Annuel <span class="cart-discount-badge">−2 mois offerts</span><br>
                                        <strong><?= number_format($item['price'] * 10, 2) ?> € / an</strong>
                                    </span>
                                </label>
                            </div>

                            <!-- Quantité -->
                            <div class="cart-qty-row">
                                <span style="font-size:0.82rem;color:var(--text-muted);">Qté :</span>
                                <div class="qty-wrap">
                                    <button type="button" class="qty-btn" data-delta="-">−</button>
                                    <input type="number" name="qty" class="qty-input"
                                           value="<?= (int)$item['qty'] ?>" min="1" max="99"
                                           onchange="this.form.submit()">
                                    <button type="button" class="qty-btn" data-delta="+">+</button>
                                </div>
                            </div>
                        </form>

                        <div class="cart-item-price">
                            <div class="price">
                                <?= number_format($item['unit_price'] * $item['qty'], 2) ?> €
                                <span>/ <?= $item['duration'] === 'annual' ? 'an' : 'mois' ?></span>
                            </div>
                            <form method="POST" style="margin-top:0.5rem;">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fa-solid fa-trash"></i> Retirer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3>Récapitulatif</h3>
                <?php foreach ($cart_items as $item): ?>
                <div class="summary-line" style="font-size:0.82rem;">
                    <span><?= escape($item['name']) ?> ×<?= $item['qty'] ?></span>
                    <span><?= number_format($item['unit_price'] * $item['qty'], 2) ?> €</span>
                </div>
                <?php endforeach; ?>
                <hr style="border:none;border-top:1px solid var(--border);margin:0.75rem 0;">
                <div class="summary-line">
                    <span>Sous-total HT</span>
                    <span><?= number_format($total_ht, 2) ?> €</span>
                </div>
                <div class="summary-line">
                    <span>TVA (20%)</span>
                    <span><?= number_format($tva, 2) ?> €</span>
                </div>
                <div class="summary-line summary-total">
                    <span>Total TTC</span>
                    <span><?= number_format($total_ttc, 2) ?> €</span>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/cyna/commande.php" class="btn btn-primary btn-full" style="margin-top:1.5rem;">
                    Passer la commande <i class="fa-solid fa-arrow-right"></i>
                </a>
                <?php else: ?>
                <a href="/cyna/connexion.php?redirect=/cyna/commande.php" class="btn btn-primary btn-full" style="margin-top:1.5rem;">
                    <i class="fa-solid fa-right-to-bracket"></i> Se connecter pour commander
                </a>
                <p style="font-size:0.78rem;color:var(--text-muted);text-align:center;margin-top:0.5rem;">
                    Ou <a href="/cyna/inscription.php" style="color:var(--accent);">créer un compte</a> gratuitement
                </p>
                <?php endif; ?>
                <a href="/cyna/catalogue.php" class="btn btn-secondary btn-full" style="margin-top:0.75rem;">
                    Continuer mes achats
                </a>
                <div class="payment-security">
                    <i class="fa-solid fa-lock"></i>
                    Paiement sécurisé par Stripe — données chiffrées SSL
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
