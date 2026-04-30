<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Vérification que la commande appartient bien à cet utilisateur
$stmt = $db->prepare('
    SELECT o.*, u.email, u.first_name, u.last_name
    FROM `order` o
    JOIN user u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
');
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    $page_title = 'Commande introuvable';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="page-content"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><h3>Commande introuvable</h3><p>Cette commande n\'existe pas ou ne vous appartient pas.</p><a href="/cyna/espace-client.php" class="btn btn-primary" style="margin-top:1.5rem;">Retour à mon espace</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Lignes de commande
$stmt2 = $db->prepare('
    SELECT oi.*, p.name AS product_name, p.description AS product_description, c.name AS category_name
    FROM order_item oi
    JOIN product p ON oi.product_id = p.id
    JOIN category c ON p.category_id = c.id
    WHERE oi.order_id = ?
');
$stmt2->execute([$id]);
$items = $stmt2->fetchAll();

// Paiement
$stmt3 = $db->prepare('SELECT * FROM payment WHERE order_id = ?');
$stmt3->execute([$id]);
$payment = $stmt3->fetch();

$status_map = [
    'pending'   => ['En attente de paiement', 'status-pending',   'fa-clock'],
    'paid'      => ['Payée',                  'status-paid',      'fa-circle-check'],
    'shipped'   => ['Livrée / Active',         'status-shipped',   'fa-truck'],
    'cancelled' => ['Annulée',                 'status-cancelled', 'fa-xmark'],
];
[$status_label, $status_cls, $status_icon] = $status_map[$order['status']] ?? [$order['status'], '', 'fa-question'];

$total_ht  = $order['total'] / 1.20;
$tva       = $order['total'] - $total_ht;

$page_title = 'Commande #' . str_pad($id, 5, '0', STR_PAD_LEFT);
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <div class="breadcrumb">
            <a href="/cyna/espace-client.php">Espace client</a> &rsaquo; Commandes
        </div>
        <h1>Commande #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></h1>
        <p>Passée le <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
    </div>
</div>

<div class="page-content" style="max-width:900px;">

    <!-- Statut -->
    <div class="order-status-banner <?= $status_cls ?>">
        <i class="fa-solid <?= $status_icon ?>"></i>
        <div>
            <div class="order-status-title"><?= $status_label ?></div>
            <?php if ($order['status'] === 'paid'): ?>
                <div class="order-status-sub">Votre abonnement est actif. Accédez à votre tableau de bord.</div>
            <?php elseif ($order['status'] === 'pending'): ?>
                <div class="order-status-sub">En attente de confirmation du paiement.</div>
            <?php elseif ($order['status'] === 'cancelled'): ?>
                <div class="order-status-sub">Cette commande a été annulée.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="order-detail-grid">

        <!-- Articles commandés -->
        <div>
            <div class="admin-card" style="margin-bottom:1.25rem;">
                <div class="admin-card-header">
                    <div class="admin-card-title">
                        <i class="fa-solid fa-box"></i> Articles commandés
                    </div>
                    <span style="font-size:0.82rem;color:var(--text-muted);"><?= count($items) ?> article<?= count($items) > 1 ? 's' : '' ?></span>
                </div>
                <div class="admin-card-body">
                    <?php foreach ($items as $item): ?>
                    <div class="order-item-row">
                        <div class="order-item-info">
                            <span class="product-badge" style="font-size:0.7rem;padding:0.2rem 0.6rem;"><?= escape($item['category_name']) ?></span>
                            <div class="order-item-name"><?= escape($item['product_name']) ?></div>
                            <div class="order-item-desc"><?= escape($item['product_description']) ?></div>
                            <?php
                            $dur = $item['duration'] ?? 'monthly';
                            $durLabel = $dur === 'annual' ? 'Abonnement annuel' : 'Abonnement mensuel';
                            ?>
                            <span style="font-size:0.75rem;color:var(--accent);">
                                <i class="fa-solid fa-rotate"></i> <?= $durLabel ?>
                            </span>
                        </div>
                        <div class="order-item-price">
                            <div style="font-size:0.82rem;color:var(--text-muted);">
                                <?= number_format($item['price'], 2) ?> € × <?= (int)$item['quantity'] ?>
                            </div>
                            <div style="font-weight:700;color:var(--text);">
                                <?= number_format($item['price'] * $item['quantity'], 2) ?> €
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Résumé -->
        <div>
            <div class="admin-card" style="margin-bottom:1.25rem;">
                <div class="admin-card-header">
                    <div class="admin-card-title"><i class="fa-solid fa-receipt"></i> Récapitulatif</div>
                </div>
                <div class="admin-card-body">
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
                        <span><?= number_format($order['total'], 2) ?> €</span>
                    </div>
                </div>
            </div>

            <div class="admin-card" style="margin-bottom:1.25rem;">
                <div class="admin-card-header">
                    <div class="admin-card-title"><i class="fa-solid fa-credit-card"></i> Paiement</div>
                </div>
                <div class="admin-card-body">
                    <div style="font-size:0.88rem;color:var(--text-muted);line-height:1.8;">
                        <div>Méthode : <strong style="color:var(--text);">Stripe</strong></div>
                        <?php if ($payment): ?>
                            <div>Statut :
                                <span class="status-badge <?= $payment['status'] === 'paid' ? 'status-paid' : 'status-pending' ?>">
                                    <?= $payment['status'] === 'paid' ? 'Encaissé' : 'En attente' ?>
                                </span>
                            </div>
                            <?php if ($payment['paid_at']): ?>
                                <div>Encaissé le : <strong style="color:var(--text);"><?= date('d/m/Y H:i', strtotime($payment['paid_at'])) ?></strong></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-title"><i class="fa-solid fa-user"></i> Client</div>
                </div>
                <div class="admin-card-body">
                    <div style="font-size:0.88rem;color:var(--text-muted);line-height:1.8;">
                        <?php if ($order['first_name'] || $order['last_name']): ?>
                            <div><?= escape(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''))) ?></div>
                        <?php endif; ?>
                        <div><?= escape($order['email']) ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div style="display:flex;gap:1rem;margin-top:1rem;flex-wrap:wrap;">
        <a href="/cyna/espace-client.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour à mon espace
        </a>
        <?php if ($order['status'] === 'paid'): ?>
        <a href="/cyna/facture.php?id=<?= $id ?>" target="_blank" class="btn btn-primary">
            <i class="fa-solid fa-file-invoice"></i> Télécharger la facture PDF
        </a>
        <?php endif; ?>
        <a href="/cyna/catalogue.php" class="btn btn-secondary">
            <i class="fa-solid fa-shop"></i> Catalogue
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
