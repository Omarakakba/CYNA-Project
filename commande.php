<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/vendor/autoload.php';
session_start();
requireLogin();

$db = getDB();

// Panier vide → retour panier
if (empty($_SESSION['cart'])) {
    header('Location: /cyna/panier.php'); exit;
}

// Charger les produits du panier
$ids      = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$products = $db->query("SELECT * FROM product WHERE id IN ($ids)")->fetchAll();

$cart_items = [];
$total_ht   = 0.0;
foreach ($products as $p) {
    $entry    = $_SESSION['cart'][$p['id']] ?? ['qty' => 1, 'duration' => 'monthly'];
    if (!is_array($entry)) $entry = ['qty' => (int)$entry, 'duration' => 'monthly'];
    $qty        = (int)($entry['qty'] ?? 1);
    $duration   = $entry['duration'] ?? 'monthly';
    $unit_price = $duration === 'annual' ? round($p['price'] * 10, 2) : $p['price'];
    $cart_items[] = array_merge($p, ['qty' => $qty, 'duration' => $duration, 'unit_price' => $unit_price]);
    $total_ht    += $unit_price * $qty;
}
$tva       = $total_ht * 0.20;
$total_ttc = $total_ht + $tva;

$error = '';

// Traitement de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $nom     = trim($_POST['nom'] ?? '');
        $prenom  = trim($_POST['prenom'] ?? '');
        $societe = trim($_POST['societe'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');

        if (empty($nom) || empty($prenom) || empty($adresse)) {
            $error = 'Veuillez remplir tous les champs obligatoires.';
        } else {
            // 1. Créer la commande en BDD avec statut "pending"
            $stmt = $db->prepare('INSERT INTO `order` (user_id, status, total) VALUES (?, "pending", ?)');
            $stmt->execute([$_SESSION['user_id'], $total_ttc]);
            $order_id = (int)$db->lastInsertId();

            // 2. Insérer les lignes de commande
            $stmt_item = $db->prepare('INSERT INTO order_item (order_id, product_id, quantity, price, duration) VALUES (?, ?, ?, ?, ?)');
            foreach ($cart_items as $item) {
                $stmt_item->execute([$order_id, $item['id'], $item['qty'], $item['unit_price'], $item['duration']]);
            }

            // 3. Créer l'entrée paiement (en attente)
            $db->prepare('INSERT INTO payment (order_id, status, amount) VALUES (?, "pending", ?)')->execute([$order_id, $total_ttc]);

            // 4. Construire les lignes pour Stripe Checkout
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

            $line_items = [];
            foreach ($cart_items as $item) {
                $label = $item['duration'] === 'annual'
                    ? $item['name'] . ' (Annuel — 2 mois offerts)'
                    : $item['name'] . ' (Mensuel)';
                $line_items[] = [
                    'price_data' => [
                        'currency'     => 'eur',
                        'product_data' => ['name' => $label],
                        'unit_amount'  => (int)round($item['unit_price'] * 100), // en centimes
                    ],
                    'quantity' => $item['qty'],
                ];
            }

            $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                  . '://' . $_SERVER['HTTP_HOST'];

            // 5. Créer la session Stripe Checkout
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items'           => $line_items,
                'mode'                 => 'payment',
                'success_url'          => $base . '/cyna/confirmation.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'           => $base . '/cyna/panier.php?cancelled=1',
                'customer_email'       => $_SESSION['user_email'] ?? null,
                'metadata'             => [
                    'order_id'  => $order_id,
                    'prenom'    => $prenom,
                    'nom'       => $nom,
                    'societe'   => $societe,
                    'adresse'   => $adresse,
                ],
            ]);

            // 6. Rediriger vers Stripe
            header('Location: ' . $session->url); exit;
        }
    }
}

$page_title = 'Finaliser la commande';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <div class="breadcrumb">
            <a href="/cyna/panier.php">Panier</a> &rsaquo; Commande
        </div>
        <h1>Finaliser la commande</h1>
    </div>
</div>

<div class="page-content">

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div class="checkout-layout">

            <!-- Formulaire de facturation -->
            <div>
                <div class="checkout-section">
                    <h3><i class="fa-solid fa-user"></i> Informations de facturation</h3>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label>Prénom <span style="color:var(--red)">*</span></label>
                            <input type="text" name="prenom" placeholder="Jean"
                                   value="<?= escape($_POST['prenom'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nom <span style="color:var(--red)">*</span></label>
                            <input type="text" name="nom" placeholder="Dupont"
                                   value="<?= escape($_POST['nom'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Société</label>
                        <input type="text" name="societe" placeholder="Mon Entreprise SAS"
                               value="<?= escape($_POST['societe'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Adresse de facturation <span style="color:var(--red)">*</span></label>
                        <input type="text" name="adresse" placeholder="12 rue de la Paix, 75001 Paris"
                               value="<?= escape($_POST['adresse'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email de confirmation</label>
                        <input type="email" value="<?= escape($_SESSION['user_email'] ?? '') ?>" disabled
                               style="background:var(--surface-2); color:var(--text-muted);">
                    </div>
                </div>

                <div class="checkout-section">
                    <h3><i class="fa-solid fa-credit-card"></i> Paiement sécurisé via Stripe</h3>
                    <div class="alert alert-info" style="margin-bottom:1rem;">
                        <i class="fa-solid fa-circle-info"></i>
                        En cliquant sur <strong>"Payer avec Stripe"</strong>, vous serez redirigé vers la plateforme
                        de paiement sécurisée <strong>Stripe</strong>. Vos données bancaires ne transitent
                        jamais par nos serveurs.
                    </div>
                    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg"
                             alt="Stripe" style="height:28px;filter:brightness(0) invert(1);opacity:0.7;">
                        <span style="font-size:0.78rem;color:var(--text-muted);">
                            Carte de test : <code style="background:var(--surface-2);padding:2px 6px;border-radius:4px;">4242 4242 4242 4242</code>
                            — exp : <code style="background:var(--surface-2);padding:2px 6px;border-radius:4px;">12/34</code>
                            — CVC : <code style="background:var(--surface-2);padding:2px 6px;border-radius:4px;">123</code>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Récapitulatif -->
            <div class="order-recap">
                <h3>Votre commande</h3>
                <?php foreach ($cart_items as $item): ?>
                <div class="recap-item">
                    <span>
                        <?= escape($item['name']) ?>
                        <small style="display:block;font-size:0.72rem;color:var(--text-muted);">
                            <?= $item['duration'] === 'annual' ? 'Annuel (×10)' : 'Mensuel' ?>
                            <?php if ($item['qty'] > 1): ?> × <?= $item['qty'] ?><?php endif; ?>
                        </small>
                    </span>
                    <span><?= number_format($item['unit_price'] * $item['qty'], 2) ?> €</span>
                </div>
                <?php endforeach; ?>
                <div class="recap-item" style="margin-top:0.5rem;">
                    <span style="color:var(--text-muted);">Sous-total HT</span>
                    <span><?= number_format($total_ht, 2) ?> €</span>
                </div>
                <div class="recap-item">
                    <span style="color:var(--text-muted);">TVA 20%</span>
                    <span><?= number_format($tva, 2) ?> €</span>
                </div>
                <div class="recap-item recap-total" style="margin-top:0.5rem; padding-top:0.75rem; border-top:2px solid var(--border);">
                    <span>Total TTC</span>
                    <span><?= number_format($total_ttc, 2) ?> €</span>
                </div>

                <button type="submit" class="btn btn-primary btn-full" style="margin-top:1.5rem;">
                    <i class="fa-brands fa-stripe-s"></i> Payer <?= number_format($total_ttc, 2) ?> € avec Stripe
                </button>

                <div class="payment-security">
                    <i class="fa-solid fa-shield-halved"></i>
                    Paiement chiffré SSL — certifié PCI DSS
                </div>
            </div>

        </div>
    </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
