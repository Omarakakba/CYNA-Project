<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
session_start();
requireAdmin();

$db      = getDB();
$error   = '';
$success = '';

// Mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée.';
    } else {
        $id     = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $valid  = ['pending', 'paid', 'shipped', 'cancelled'];
        if ($id > 0 && in_array($status, $valid)) {
            $db->prepare('UPDATE `order` SET status=? WHERE id=?')->execute([$status, $id]);
            if ($status === 'paid') {
                $db->prepare('UPDATE payment SET status="paid", paid_at=NOW() WHERE order_id=?')->execute([$id]);
            }
            $success = 'Statut de la commande #' . str_pad($id, 5, '0', STR_PAD_LEFT) . ' mis à jour.';
        }
    }
}

// Filtre statut
$filter        = $_GET['status'] ?? '';
$valid_filters = ['pending', 'paid', 'shipped', 'cancelled'];

if (in_array($filter, $valid_filters)) {
    $stmt = $db->prepare('SELECT o.*, u.email, COUNT(oi.id) AS nb_items, p.stripe_id, p.paid_at FROM `order` o JOIN user u ON o.user_id = u.id LEFT JOIN order_item oi ON o.id = oi.order_id LEFT JOIN payment p ON p.order_id = o.id WHERE o.status = ? GROUP BY o.id ORDER BY o.created_at DESC');
    $stmt->execute([$filter]);
} else {
    $stmt = $db->query('SELECT o.*, u.email, COUNT(oi.id) AS nb_items, p.stripe_id, p.paid_at FROM `order` o JOIN user u ON o.user_id = u.id LEFT JOIN order_item oi ON o.id = oi.order_id LEFT JOIN payment p ON p.order_id = o.id GROUP BY o.id ORDER BY o.created_at DESC');
}
$orders = $stmt->fetchAll();

$status_map = [
    'pending'   => ['En attente', 'status-pending'],
    'paid'      => ['Payé',       'status-paid'],
    'shipped'   => ['Livré',      'status-shipped'],
    'cancelled' => ['Annulé',     'status-cancelled'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion commandes — CYNA Admin</title>
    
    <link rel="stylesheet" href="/cyna/assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="/cyna/assets/css/style.css?v=5">
</head>
<body>

<header>
    <div class="nav-inner">
        <a href="/cyna/" class="nav-logo">
            <div class="nav-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <span class="nav-logo-text">CY<span>NA</span></span>
        </a>
        <span style="color:rgba(255,255,255,0.4); font-size:0.8rem; margin-left:0.5rem;">Administration</span>
        <div class="nav-right">
            <a href="/cyna/espace-client.php" class="nav-link-login">
                <i class="fa-solid fa-arrow-left"></i> Retour au site
            </a>
            <a href="/cyna/logout.php" class="nav-cta">Déconnexion</a>
        </div>
    </div>
</header>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo">
            <span>Navigation</span>
        </div>
        <div class="admin-nav-section">Menu principal</div>
        <a href="/cyna/admin/" class="admin-nav-item">
            <i class="fa-solid fa-house"></i> Tableau de bord
        </a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Catalogue</div>
        <a href="/cyna/admin/produits.php" class="admin-nav-item">
            <i class="fa-solid fa-box"></i> Produits
        </a>
        <a href="/cyna/admin/carousel.php" class="admin-nav-item">
            <i class="fa-solid fa-images"></i> Carousel
        </a>
        <a href="/cyna/admin/categories.php" class="admin-nav-item">
            <i class="fa-solid fa-tags"></i> Catégories
        </a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Ventes</div>
        <a href="/cyna/admin/commandes.php" class="admin-nav-item active">
            <i class="fa-solid fa-receipt"></i> Commandes
        </a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Utilisateurs</div>
        <a href="/cyna/admin/utilisateurs.php" class="admin-nav-item">
            <i class="fa-solid fa-users"></i> Utilisateurs
        </a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Communication</div>
        <?php $_nr = (int)getDB()->query('SELECT COUNT(*) FROM contact_message WHERE is_read=0')->fetchColumn(); ?>
        <a href="/cyna/admin/messages.php" class="admin-nav-item">
            <i class="fa-solid fa-envelope"></i> Messages de contact
            <?php if ($_nr > 0): ?><span style="margin-left:auto;background:var(--accent);color:#fff;border-radius:20px;padding:0.1rem 0.5rem;font-size:0.7rem;font-weight:700;"><?= $_nr ?></span><?php endif; ?>
        </a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Système</div>
        <a href="/cyna/" class="admin-nav-item">
            <i class="fa-solid fa-globe"></i> Voir le site
        </a>
    </aside>

    <main class="admin-content">
        <div class="admin-topbar">
            <h1>Gestion des commandes</h1>
            <div class="admin-topbar-right">
                <span style="font-size:0.85rem; color:var(--text-muted);"><?= count($orders) ?> commande<?= count($orders) > 1 ? 's' : '' ?></span>
            </div>
        </div>

        <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div><?php endif; ?>

        <!-- Filtres statut -->
        <div class="catalogue-filters" style="margin-bottom:1.5rem;">
            <a href="/cyna/admin/commandes.php" class="filter-btn <?= $filter === '' ? 'active' : '' ?>">Toutes</a>
            <a href="/cyna/admin/commandes.php?status=pending"   class="filter-btn <?= $filter === 'pending'   ? 'active' : '' ?>">En attente</a>
            <a href="/cyna/admin/commandes.php?status=paid"      class="filter-btn <?= $filter === 'paid'      ? 'active' : '' ?>">Payées</a>
            <a href="/cyna/admin/commandes.php?status=shipped"   class="filter-btn <?= $filter === 'shipped'   ? 'active' : '' ?>">Livrées</a>
            <a href="/cyna/admin/commandes.php?status=cancelled" class="filter-btn <?= $filter === 'cancelled' ? 'active' : '' ?>">Annulées</a>
        </div>

        <!-- Tableau commandes -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <i class="fa-solid fa-receipt"></i> Liste des commandes
                </div>
            </div>
            <div class="admin-card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Client</th>
                            <th>Articles</th>
                            <th>Date</th>
                            <th>Total TTC</th>
                            <th>Statut</th>
                            <th>Stripe</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:2rem;">Aucune commande</td></tr>
                        <?php else: foreach ($orders as $o):
                            [$label, $cls] = $status_map[$o['status']] ?? [$o['status'], ''];
                        ?>
                        <tr>
                            <td><strong>#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                            <td><?= escape($o['email']) ?></td>
                            <td><?= (int)$o['nb_items'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td><strong><?= number_format($o['total'], 2) ?> €</strong></td>
                            <td><span class="status-badge <?= $cls ?>"><?= $label ?></span></td>
                            <td>
                                <?php if (!empty($o['stripe_id'])): ?>
                                    <code style="font-size:0.7rem;background:var(--surface-2);padding:2px 5px;border-radius:4px;color:var(--primary);">
                                        <?= escape(substr($o['stripe_id'], 0, 20)) ?>…
                                    </code>
                                    <?php if ($o['paid_at']): ?>
                                        <div style="font-size:0.7rem;color:var(--text-muted);margin-top:2px;"><?= date('d/m/Y H:i', strtotime($o['paid_at'])) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:0.78rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:flex; gap:0.4rem; align-items:center;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="order_id"   value="<?= (int)$o['id'] ?>">
                                    <select name="status" style="font-size:0.78rem; padding:0.3rem 0.5rem; border:1px solid var(--border); border-radius:6px; background:var(--surface); color:var(--text);">
                                        <?php foreach ($status_map as $val => [$lbl, $_]): ?>
                                            <option value="<?= $val ?>" <?= $o['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Mettre à jour">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="/cyna/assets/js/main.js"></script>
</body>
</html>
