<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

$db = getDB();

// --- Action : résilier un abonnement ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403); exit('Token invalide.');
    }
    $oid = (int)$_POST['cancel_order'];
    // Vérifier que la commande appartient bien à l'utilisateur
    $stmt = $db->prepare('SELECT id FROM `order` WHERE id = ? AND user_id = ? AND status = "paid"');
    $stmt->execute([$oid, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $db->prepare('UPDATE `order` SET status = "cancelled" WHERE id = ?')->execute([$oid]);
    }
    header('Location: /cyna/abonnements.php?cancelled=1'); exit;
}

// --- Chargement des abonnements actifs (commandes payées) ---
$stmt = $db->prepare('
    SELECT o.id AS order_id, o.created_at, o.total,
           oi.product_id, oi.quantity, oi.price, oi.duration,
           p.name AS product_name, p.description, p.price AS current_price, p.is_available,
           c.name AS category_name
    FROM `order` o
    JOIN order_item oi ON oi.order_id = o.id
    JOIN product p ON p.id = oi.product_id
    JOIN category c ON c.id = p.category_id
    WHERE o.user_id = ? AND o.status = "paid"
    ORDER BY o.created_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$subscriptions = $stmt->fetchAll();

// --- Chargement des abonnements annulés (pour historique) ---
$stmt2 = $db->prepare('
    SELECT o.id AS order_id, o.created_at,
           oi.duration, p.name AS product_name, c.name AS category_name
    FROM `order` o
    JOIN order_item oi ON oi.order_id = o.id
    JOIN product p ON p.id = oi.product_id
    JOIN category c ON c.id = p.category_id
    WHERE o.user_id = ? AND o.status = "cancelled"
    ORDER BY o.created_at DESC
    LIMIT 5
');
$stmt2->execute([$_SESSION['user_id']]);
$cancelled = $stmt2->fetchAll();

$page_title = 'Mes abonnements';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <div class="breadcrumb">
            <a href="/cyna/espace-client.php">Espace client</a> &rsaquo; Abonnements
        </div>
        <h1>Mes abonnements</h1>
        <p>Gérez vos abonnements actifs — renouveler ou résilier</p>
    </div>
</div>

<div class="page-content">

    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-success" style="margin-bottom:1.5rem;">
            <i class="fa-solid fa-check-circle"></i> Abonnement résilié avec succès.
        </div>
    <?php endif; ?>

    <!-- Abonnements actifs -->
    <div class="dashboard-section-title" style="margin-bottom:1rem;">
        <i class="fa-solid fa-rotate"></i> Abonnements actifs
    </div>

    <?php if (empty($subscriptions)): ?>
        <div class="empty-state" style="padding:3rem 2rem; margin-bottom:2rem;">
            <div class="empty-icon"><i class="fa-solid fa-ban"></i></div>
            <h3>Aucun abonnement actif</h3>
            <p>Vous n'avez aucun abonnement en cours.</p>
            <a href="/cyna/catalogue.php" class="btn btn-primary" style="margin-top:1.25rem;">
                Découvrir nos solutions
            </a>
        </div>
    <?php else: ?>
        <div style="display:grid;gap:1rem;margin-bottom:3rem;">
            <?php foreach ($subscriptions as $sub):
                $durLabel = $sub['duration'] === 'annual' ? 'Annuel' : 'Mensuel';
                $durIcon  = $sub['duration'] === 'annual' ? 'fa-calendar' : 'fa-calendar-day';
                $since    = date('d/m/Y', strtotime($sub['created_at']));
            ?>
            <div class="admin-card">
                <div class="admin-card-header" style="justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                        <div style="width:44px;height:44px;background:var(--surface-2);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-shield-halved" style="color:var(--accent);font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:1rem;color:var(--text);"><?= escape($sub['product_name']) ?></div>
                            <div style="display:flex;gap:0.5rem;align-items:center;margin-top:0.3rem;flex-wrap:wrap;">
                                <span class="product-badge" style="font-size:0.7rem;"><?= escape($sub['category_name']) ?></span>
                                <span style="font-size:0.78rem;color:var(--accent);">
                                    <i class="fa-solid <?= $durIcon ?>"></i> <?= $durLabel ?>
                                </span>
                                <span class="status-badge status-paid">Actif</span>
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:1.1rem;font-weight:800;color:var(--primary);">
                            <?= number_format($sub['price'], 2) ?> €
                            <span style="font-size:0.78rem;font-weight:500;color:var(--text-muted);">
                                / <?= $sub['duration'] === 'annual' ? 'an' : 'mois' ?>
                            </span>
                        </div>
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem;">
                            Depuis le <?= $since ?>
                        </div>
                    </div>
                </div>

                <div class="admin-card-body" style="padding-top:0.75rem;border-top:1px solid var(--border);display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
                    <p style="font-size:0.82rem;color:var(--text-muted);flex:1;margin:0;"><?= escape($sub['description']) ?></p>
                    <div style="display:flex;gap:0.5rem;flex-shrink:0;">
                        <?php if ($sub['is_available']): ?>
                        <!-- Renouveler = ajouter au panier -->
                        <form method="POST" action="/cyna/panier.php">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int)$sub['product_id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-rotate-right"></i> Renouveler
                            </button>
                        </form>
                        <?php endif; ?>

                        <!-- Résilier -->
                        <form method="POST" onsubmit="return confirm('Confirmer la résiliation de « <?= escape(addslashes($sub['product_name'])) ?> » ?')">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="cancel_order" value="<?= (int)$sub['order_id'] ?>">
                            <button type="submit" class="btn btn-sm" style="background:transparent;border:1px solid var(--red,#e74c3c);color:var(--red,#e74c3c);">
                                <i class="fa-solid fa-xmark"></i> Résilier
                            </button>
                        </form>

                        <a href="/cyna/commande-detail.php?id=<?= (int)$sub['order_id'] ?>" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-eye"></i> Détail
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Abonnements résiliés récents -->
    <?php if (!empty($cancelled)): ?>
    <div class="dashboard-section-title" style="margin-bottom:1rem;opacity:0.7;">
        <i class="fa-solid fa-clock-rotate-left"></i> Résiliés récemment
    </div>
    <div class="table-wrapper" style="opacity:0.7;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Commande</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Durée</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cancelled as $c): ?>
                <tr>
                    <td>#<?= str_pad($c['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td><?= escape($c['product_name']) ?></td>
                    <td><?= escape($c['category_name']) ?></td>
                    <td><?= $c['duration'] === 'annual' ? 'Annuel' : 'Mensuel' ?></td>
                    <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div style="margin-top:2rem;">
        <a href="/cyna/espace-client.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour à mon espace
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
