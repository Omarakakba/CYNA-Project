<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

$db = getDB();

$search_order  = trim($_GET['sq'] ?? '');
$filter_status = $_GET['status'] ?? '';
$allowed_statuses = ['pending', 'paid', 'shipped', 'cancelled'];
if (!in_array($filter_status, $allowed_statuses)) $filter_status = '';

$sql_orders = 'SELECT o.*, COUNT(oi.id) AS nb_items FROM `order` o LEFT JOIN order_item oi ON o.id = oi.order_id WHERE o.user_id = ?';
$params_orders = [$_SESSION['user_id']];
if ($search_order !== '') {
    $sql_orders .= ' AND (LPAD(o.id,5,"0") LIKE ? OR o.id IN (SELECT oi2.order_id FROM order_item oi2 JOIN product p ON p.id=oi2.product_id WHERE p.name LIKE ?))';
    $params_orders[] = '%' . $search_order . '%';
    $params_orders[] = '%' . $search_order . '%';
}
if ($filter_status !== '') {
    $sql_orders .= ' AND o.status = ?';
    $params_orders[] = $filter_status;
}
$sql_orders .= ' GROUP BY o.id ORDER BY o.created_at DESC';

$stmt = $db->prepare($sql_orders);
$stmt->execute($params_orders);
$orders = $stmt->fetchAll();

$total_spent = array_sum(array_column($orders, 'total'));

$page_title = 'Mon espace client';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Espace client</h1>
        <p><?= escape($_SESSION['user_email']) ?></p>
    </div>
</div>

<div class="page-content">

    <!-- Cartes résumé -->
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <div class="dashboard-card-icon blue"><i class="fa-solid fa-envelope"></i></div>
            <div>
                <div class="dashboard-card-label">Compte</div>
                <div class="dashboard-card-value" style="font-size:0.9rem;"><?= escape($_SESSION['user_email']) ?></div>
            </div>
        </div>
        <div class="dashboard-card">
            <div class="dashboard-card-icon purple"><i class="fa-solid fa-receipt"></i></div>
            <div>
                <div class="dashboard-card-label">Commandes</div>
                <div class="dashboard-card-value"><?= count($orders) ?></div>
                <div class="dashboard-card-sub"><?= count($orders) > 1 ? 'commandes passées' : 'commande passée' ?></div>
            </div>
        </div>
        <div class="dashboard-card">
            <div class="dashboard-card-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="dashboard-card-label">Statut du compte</div>
                <div class="dashboard-card-value" style="color:var(--green);">Actif</div>
                <div class="dashboard-card-sub"><?= number_format($total_spent, 2) ?> € dépensés</div>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="dashboard-actions">
        <a href="/cyna/catalogue.php" class="btn btn-primary">
            <i class="fa-solid fa-shop"></i> Parcourir le catalogue
        </a>
        <a href="/cyna/panier.php" class="btn btn-secondary">
            <i class="fa-solid fa-cart-shopping"></i> Mon panier
        </a>
        <a href="/cyna/profil.php" class="btn btn-secondary">
            <i class="fa-solid fa-user-pen"></i> Mon profil
        </a>
        <a href="/cyna/abonnements.php" class="btn btn-secondary">
            <i class="fa-solid fa-rotate"></i> Mes abonnements
        </a>
        <a href="/cyna/adresses.php" class="btn btn-secondary">
            <i class="fa-solid fa-map-location-dot"></i> Mes adresses
        </a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="/cyna/admin/" class="btn btn-secondary" style="border-color:var(--accent); color:var(--accent);">
            <i class="fa-solid fa-gear"></i> Administration
        </a>
        <?php endif; ?>
        <a href="/cyna/logout.php" class="btn btn-secondary" style="margin-left:auto;">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>

    <!-- Historique commandes -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
        <div class="dashboard-section-title" style="margin:0;">
            <i class="fa-solid fa-clock-rotate-left"></i> Historique des commandes
        </div>
        <form method="GET" action="/cyna/espace-client.php" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.8rem;pointer-events:none;"></i>
                <input type="search" name="sq" value="<?= escape($search_order) ?>"
                       placeholder="N° commande ou produit…"
                       style="padding:0.5rem 0.75rem 0.5rem 2.1rem;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:0.85rem;width:200px;">
            </div>
            <select name="status" style="padding:0.5rem 0.75rem;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:0.85rem;">
                <option value="" <?= $filter_status==='' ? 'selected' : '' ?>>Tous les statuts</option>
                <option value="paid"      <?= $filter_status==='paid'      ? 'selected' : '' ?>>Payées</option>
                <option value="pending"   <?= $filter_status==='pending'   ? 'selected' : '' ?>>En attente</option>
                <option value="shipped"   <?= $filter_status==='shipped'   ? 'selected' : '' ?>>Livrées</option>
                <option value="cancelled" <?= $filter_status==='cancelled' ? 'selected' : '' ?>>Annulées</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-search"></i></button>
            <?php if ($search_order !== '' || $filter_status !== ''): ?>
                <a href="/cyna/espace-client.php" class="btn btn-secondary btn-sm" title="Effacer"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state" style="padding:3rem 2rem;">
            <div class="empty-icon"><i class="fa-solid fa-box-open"></i></div>
            <h3>Aucune commande</h3>
            <p>Vous n'avez pas encore passé de commande.</p>
            <a href="/cyna/catalogue.php" class="btn btn-primary" style="margin-top:1.25rem;">
                Découvrir nos solutions
            </a>
        </div>
    <?php else:
        $status_map = [
            'pending'   => ['En attente', 'status-pending'],
            'paid'      => ['Payé',       'status-paid'],
            'shipped'   => ['Livré',      'status-shipped'],
            'cancelled' => ['Annulé',     'status-cancelled'],
        ];
        // Regroup orders by year
        $by_year = [];
        foreach ($orders as $o) {
            $y = date('Y', strtotime($o['created_at']));
            $by_year[$y][] = $o;
        }
        krsort($by_year); // most recent year first
        $first = true;
    ?>
        <?php foreach ($by_year as $year => $year_orders): ?>
        <details <?= $first ? 'open' : '' ?> style="margin-bottom:1rem;">
            <summary style="cursor:pointer;padding:0.85rem 1.25rem;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);font-weight:700;font-size:0.95rem;color:var(--primary);list-style:none;display:flex;align-items:center;gap:0.6rem;user-select:none;">
                <i class="fa-solid fa-calendar-days" style="color:var(--accent);"></i>
                <?= $year ?>
                <span style="margin-left:auto;font-size:0.8rem;font-weight:500;color:var(--text-muted);">
                    <?= count($year_orders) ?> commande<?= count($year_orders) > 1 ? 's' : '' ?>
                    &bull;
                    <?= number_format(array_sum(array_column($year_orders, 'total')), 2) ?> €
                </span>
            </summary>
            <div class="table-wrapper" style="margin-top:0.5rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Articles</th>
                            <th>Total TTC</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($year_orders as $order):
                            [$label, $cls] = $status_map[$order['status']] ?? [$order['status'], ''];
                        ?>
                        <tr>
                            <td><strong>#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                            <td><?= (int)$order['nb_items'] ?> article<?= $order['nb_items'] > 1 ? 's' : '' ?></td>
                            <td><strong><?= number_format($order['total'], 2) ?> €</strong></td>
                            <td><span class="status-badge <?= $cls ?>"><?= $label ?></span></td>
                            <td style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                                <a href="/cyna/commande-detail.php?id=<?= (int)$order['id'] ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-eye"></i> Détail
                                </a>
                                <?php if ($order['status'] === 'paid'): ?>
                                <a href="/cyna/facture.php?id=<?= (int)$order['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" title="Télécharger la facture">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
        <?php $first = false; endforeach; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
