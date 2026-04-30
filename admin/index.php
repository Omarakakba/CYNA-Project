<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
session_start();
requireAdmin();

$db = getDB();

$nb_users    = $db->query('SELECT COUNT(*) FROM user')->fetchColumn();
$nb_products = $db->query('SELECT COUNT(*) FROM product')->fetchColumn();
$nb_orders   = $db->query('SELECT COUNT(*) FROM `order`')->fetchColumn();
$revenue     = $db->query('SELECT COALESCE(SUM(total),0) FROM `order` WHERE status = "paid"')->fetchColumn();

$recent_orders = $db->query('SELECT o.id, o.status, o.total, o.created_at, u.email FROM `order` o JOIN user u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 8')->fetchAll();

// Données graphique 1 : commandes par jour sur 14 jours
$sales_raw = $db->query('
    SELECT DATE(created_at) AS day, COUNT(*) AS nb, COALESCE(SUM(total),0) AS revenue
    FROM `order`
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
')->fetchAll();

// Construire un tableau complet sur 14 jours (sans trous)
$sales_days    = [];
$sales_counts  = [];
$sales_revenue = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $sales_days[]    = date('d/m', strtotime($d));
    $sales_counts[]  = 0;
    $sales_revenue[] = 0;
}
foreach ($sales_raw as $row) {
    $idx = array_search(date('d/m', strtotime($row['day'])), $sales_days);
    if ($idx !== false) {
        $sales_counts[$idx]  = (int)$row['nb'];
        $sales_revenue[$idx] = (float)$row['revenue'];
    }
}

// Données graphique 2 : répartition des commandes par statut
$status_raw = $db->query('SELECT status, COUNT(*) AS nb FROM `order` GROUP BY status')->fetchAll();
$status_labels = ['pending' => 'En attente', 'paid' => 'Payées', 'shipped' => 'Livrées', 'cancelled' => 'Annulées'];
$status_data   = ['pending' => 0, 'paid' => 0, 'shipped' => 0, 'cancelled' => 0];
foreach ($status_raw as $row) {
    if (isset($status_data[$row['status']])) $status_data[$row['status']] = (int)$row['nb'];
}

// Données graphique 3 : produits par catégorie
$cat_raw = $db->query('SELECT c.name, COUNT(p.id) AS nb FROM category c LEFT JOIN product p ON p.category_id = c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — CYNA</title>
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
        <div class="admin-sidebar-logo"><span>Navigation</span></div>
        <div class="admin-nav-section">Menu principal</div>
        <a href="/cyna/admin/" class="admin-nav-item active">
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
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Ventes</div>
        <a href="/cyna/admin/commandes.php" class="admin-nav-item">
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
            <h1>Tableau de bord</h1>
            <div class="admin-topbar-right">
                <span style="font-size:0.85rem; color:var(--text-muted);"><?= date('d/m/Y — H:i') ?></span>
            </div>
        </div>

        <!-- Stats -->
        <div class="admin-stats">
            <div class="admin-stat">
                <div class="admin-stat-header">
                    <div class="admin-stat-icon blue"><i class="fa-solid fa-users"></i></div>
                </div>
                <div class="admin-stat-value"><?= $nb_users ?></div>
                <div class="admin-stat-label">Utilisateurs inscrits</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-header">
                    <div class="admin-stat-icon purple"><i class="fa-solid fa-box"></i></div>
                </div>
                <div class="admin-stat-value"><?= $nb_products ?></div>
                <div class="admin-stat-label">Produits au catalogue</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-header">
                    <div class="admin-stat-icon orange"><i class="fa-solid fa-receipt"></i></div>
                </div>
                <div class="admin-stat-value"><?= $nb_orders ?></div>
                <div class="admin-stat-label">Commandes totales</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-header">
                    <div class="admin-stat-icon green"><i class="fa-solid fa-euro-sign"></i></div>
                </div>
                <div class="admin-stat-value"><?= number_format($revenue, 0, ',', ' ') ?> €</div>
                <div class="admin-stat-label">Revenus encaissés</div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="admin-charts-grid">

            <!-- Graphique : ventes 14 jours -->
            <div class="admin-card admin-chart-large">
                <div class="admin-card-header">
                    <div class="admin-card-title">
                        <i class="fa-solid fa-chart-line"></i> Commandes — 14 derniers jours
                    </div>
                </div>
                <div class="admin-card-body">
                    <canvas id="chartSales" height="100"></canvas>
                </div>
            </div>

            <!-- Graphique : statuts -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-title">
                        <i class="fa-solid fa-chart-pie"></i> Répartition par statut
                    </div>
                </div>
                <div class="admin-card-body" style="display:flex; justify-content:center; align-items:center; min-height:200px;">
                    <canvas id="chartStatus" style="max-width:220px; max-height:220px;"></canvas>
                </div>
            </div>

            <!-- Graphique : produits par catégorie -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-title">
                        <i class="fa-solid fa-chart-bar"></i> Produits par catégorie
                    </div>
                </div>
                <div class="admin-card-body" style="display:flex; justify-content:center; align-items:center; min-height:200px;">
                    <canvas id="chartCat" style="max-width:220px; max-height:220px;"></canvas>
                </div>
            </div>

        </div>

        <!-- Commandes récentes -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <i class="fa-solid fa-clock-rotate-left"></i> Commandes récentes
                </div>
                <a href="/cyna/admin/commandes.php" class="btn btn-secondary btn-sm">Voir tout</a>
            </div>
            <div class="admin-card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                        <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:2rem;">Aucune commande</td></tr>
                        <?php else: foreach ($recent_orders as $o):
                            $map = ['pending'=>['En attente','status-pending'],'paid'=>['Payé','status-paid'],'shipped'=>['Livré','status-shipped'],'cancelled'=>['Annulé','status-cancelled']];
                            [$label,$cls] = $map[$o['status']] ?? [$o['status'],''];
                        ?>
                        <tr>
                            <td><strong>#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                            <td><?= escape($o['email']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td><?= number_format($o['total'], 2) ?> €</td>
                            <td><span class="status-badge <?= $cls ?>"><?= $label ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = 'rgba(255,255,255,0.07)';

const salesDays    = <?= json_encode($sales_days) ?>;
const salesCounts  = <?= json_encode($sales_counts) ?>;
const salesRevenue = <?= json_encode($sales_revenue) ?>;

// Graphique 1 : courbe commandes + revenus
new Chart(document.getElementById('chartSales'), {
    type: 'bar',
    data: {
        labels: salesDays,
        datasets: [
            {
                label: 'Commandes',
                data: salesCounts,
                backgroundColor: 'rgba(99,102,241,0.7)',
                borderColor: '#6366f1',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'Revenus (€)',
                data: salesRevenue,
                type: 'line',
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                pointBackgroundColor: '#10b981',
                tension: 0.4,
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y:  { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Commandes' } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: '€' } }
        },
        plugins: { legend: { position: 'top' } }
    }
});

// Graphique 2 : camembert statuts
const statusLabels = <?= json_encode(array_values($status_labels)) ?>;
const statusData   = <?= json_encode(array_values($status_data)) ?>;
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: ['#f59e0b','#10b981','#6366f1','#ef4444'],
            borderWidth: 2,
            borderColor: '#0f172a',
        }]
    },
    options: {
        cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { padding: 12, boxWidth: 12 } } }
    }
});

// Graphique 3 : barres catégories
const catLabels = <?= json_encode(array_column($cat_raw, 'name')) ?>;
const catData   = <?= json_encode(array_map('intval', array_column($cat_raw, 'nb'))) ?>;
new Chart(document.getElementById('chartCat'), {
    type: 'doughnut',
    data: {
        labels: catLabels,
        datasets: [{
            data: catData,
            backgroundColor: ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444'],
            borderWidth: 2,
            borderColor: '#0f172a',
        }]
    },
    options: {
        cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { padding: 12, boxWidth: 12 } } }
    }
});
</script>
</body>
</html>
