<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/vendor/autoload.php';
session_start();
requireLogin();

$db = getDB();

// Stripe redirige ici avec ?session_id=cs_test_xxx
$session_id = $_GET['session_id'] ?? '';
if (empty($session_id)) {
    header('Location: /cyna/espace-client.php'); exit;
}

// Récupérer et vérifier la session Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    $stripe_session = \Stripe\Checkout\Session::retrieve($session_id);
} catch (\Stripe\Exception\ApiErrorException $e) {
    header('Location: /cyna/espace-client.php'); exit;
}

// Vérifier que le paiement est bien réussi
if ($stripe_session->payment_status !== 'paid') {
    header('Location: /cyna/panier.php?payment_failed=1'); exit;
}

$order_id = (int)($stripe_session->metadata->order_id ?? 0);
if (!$order_id) {
    header('Location: /cyna/espace-client.php'); exit;
}

// Vérifier que la commande appartient à l'utilisateur connecté
$stmt = $db->prepare('SELECT * FROM `order` WHERE id = ? AND user_id = ?');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) {
    header('Location: /cyna/espace-client.php'); exit;
}

// Mettre à jour le statut (seulement si pas déjà fait — protection double-clic)
if ($order['status'] === 'pending') {
    $db->prepare('UPDATE `order` SET status = "paid" WHERE id = ?')->execute([$order_id]);
    $db->prepare('UPDATE payment SET status = "paid", stripe_id = ?, paid_at = NOW() WHERE order_id = ?')
       ->execute([$session_id, $order_id]);
}

// Vider le panier
$_SESSION['cart'] = [];

// Charger les détails de la commande pour l'affichage
$stmt = $db->prepare('SELECT o.*, u.email FROM `order` o JOIN user u ON o.user_id = u.id WHERE o.id = ?');
$stmt->execute([$order_id]);
$order = $stmt->fetch();

$items_stmt = $db->prepare('SELECT oi.*, p.name FROM order_item oi JOIN product p ON oi.product_id = p.id WHERE oi.order_id = ?');
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

$page_title = 'Commande confirmée';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content" style="max-width:700px; margin:3rem auto; text-align:center;">

    <div style="width:72px;height:72px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;color:var(--green);font-size:1.8rem;">
        <i class="fa-solid fa-circle-check"></i>
    </div>

    <h1 style="font-size:1.75rem;font-weight:800;color:var(--primary);margin-bottom:0.5rem;">
        Paiement confirmé !
    </h1>
    <p style="color:var(--text-muted);margin-bottom:0.5rem;">
        Votre commande <strong>#<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></strong> a été payée avec succès via Stripe.
    </p>
    <p style="color:var(--text-muted);margin-bottom:2.5rem;">
        Un email de confirmation a été envoyé à <strong><?= escape($order['email']) ?></strong>.
    </p>

    <!-- Badge Stripe -->
    <div style="display:inline-flex;align-items:center;gap:0.5rem;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:0.5rem 1rem;margin-bottom:2rem;font-size:0.82rem;color:var(--text-muted);">
        <i class="fa-solid fa-lock" style="color:var(--green);"></i>
        Paiement traité par <strong style="color:var(--text);">Stripe</strong> — Référence : <code style="font-size:0.78rem;"><?= escape($session_id) ?></code>
    </div>

    <div class="table-wrapper" style="text-align:left;margin-bottom:2rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Solution</th>
                    <th>Durée</th>
                    <th>Qté</th>
                    <th>Prix</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= escape($item['name']) ?></td>
                    <td>
                        <?php if (($item['duration'] ?? 'monthly') === 'annual'): ?>
                            <span style="font-size:0.78rem;background:rgba(var(--primary-rgb),0.15);color:var(--primary);padding:2px 6px;border-radius:4px;">Annuel</span>
                        <?php else: ?>
                            <span style="font-size:0.78rem;color:var(--text-muted);">Mensuel</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td><?= number_format($item['price'], 2) ?> €</td>
                    <td><?= number_format($item['price'] * $item['quantity'], 2) ?> €</td>
                </tr>
                <?php endforeach; ?>
                <tr style="border-top:2px solid var(--border);">
                    <td colspan="4" style="text-align:right;font-weight:700;">Total TTC</td>
                    <td style="font-weight:700;"><?= number_format($order['total'], 2) ?> €</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="/cyna/espace-client.php" class="btn btn-primary">
            <i class="fa-solid fa-user"></i> Voir mes commandes
        </a>
        <a href="/cyna/catalogue.php" class="btn btn-secondary">
            Continuer mes achats
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
