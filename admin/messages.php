<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
session_start();
requireAdmin();

$db = getDB();

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $mid = (int)$_POST['mark_read'];
    $db->prepare('UPDATE contact_message SET is_read=1 WHERE id=?')->execute([$mid]);
    header('Location: /cyna/admin/messages.php'); exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
    $mid = (int)$_POST['delete'];
    $db->prepare('DELETE FROM contact_message WHERE id=?')->execute([$mid]);
    header('Location: /cyna/admin/messages.php'); exit;
}

$filter = $_GET['filter'] ?? 'all';
$sql    = 'SELECT * FROM contact_message';
if ($filter === 'unread') $sql .= ' WHERE is_read = 0';
$sql .= ' ORDER BY created_at DESC';
$messages = $db->query($sql)->fetchAll();

$nb_unread = (int)$db->query('SELECT COUNT(*) FROM contact_message WHERE is_read=0')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages de contact — Admin CYNA</title>
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
        <a href="/cyna/admin/" class="admin-nav-item">
            <i class="fa-solid fa-house"></i> Tableau de bord
        </a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Catalogue</div>
        <a href="/cyna/admin/produits.php" class="admin-nav-item">
            <i class="fa-solid fa-box"></i> Produits
        </a>
        <a href="/cyna/admin/categories.php" class="admin-nav-item">
            <i class="fa-solid fa-tags"></i> Catégories
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
        <a href="/cyna/admin/messages.php" class="admin-nav-item active">
            <i class="fa-solid fa-envelope"></i> Messages de contact
            <?php if ($nb_unread > 0): ?>
                <span style="margin-left:auto;background:var(--accent);color:#fff;border-radius:20px;padding:0.1rem 0.5rem;font-size:0.7rem;font-weight:700;"><?= $nb_unread ?></span>
            <?php endif; ?>
        </a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Système</div>
        <a href="/cyna/" class="admin-nav-item">
            <i class="fa-solid fa-globe"></i> Voir le site
        </a>
    </aside>

    <main class="admin-content">
        <div class="admin-topbar">
            <h1>Messages de contact</h1>
            <div class="admin-topbar-right">
                <?php if ($nb_unread > 0): ?>
                    <span style="font-size:0.85rem;color:var(--accent);font-weight:600;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= $nb_unread ?> non lu<?= $nb_unread > 1 ? 's' : '' ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtres -->
        <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;">
            <a href="?filter=all" class="btn btn-sm <?= $filter!=='unread' ? 'btn-primary' : 'btn-secondary' ?>">
                Tous (<?= count($messages) + ($filter==='unread' ? 0 : 0) ?>)
            </a>
            <a href="?filter=unread" class="btn btn-sm <?= $filter==='unread' ? 'btn-primary' : 'btn-secondary' ?>">
                Non lus (<?= $nb_unread ?>)
            </a>
        </div>

        <?php if (empty($messages)): ?>
            <div class="empty-state" style="padding:4rem 2rem;">
                <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
                <h3>Aucun message</h3>
                <p><?= $filter==='unread' ? 'Aucun message non lu.' : 'Aucun message de contact pour l\'instant.' ?></p>
            </div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <?php foreach ($messages as $msg): ?>
                <div class="admin-card" style="<?= !$msg['is_read'] ? 'border-left:3px solid var(--accent);' : 'opacity:0.8;' ?>">
                    <div class="admin-card-header" style="justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                            <?php if (!$msg['is_read']): ?>
                                <span style="width:8px;height:8px;background:var(--accent);border-radius:50%;flex-shrink:0;"></span>
                            <?php endif; ?>
                            <strong style="font-size:0.95rem;"><?= escape($msg['name']) ?></strong>
                            <a href="mailto:<?= escape($msg['email']) ?>" style="color:var(--accent);font-size:0.85rem;">
                                <i class="fa-solid fa-envelope"></i> <?= escape($msg['email']) ?>
                            </a>
                            <span class="status-badge" style="background:var(--surface-2);"><?= escape($msg['subject']) ?></span>
                            <span style="font-size:0.8rem;color:var(--text-muted);">
                                <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                            </span>
                        </div>
                        <div style="display:flex;gap:0.5rem;flex-shrink:0;">
                            <?php if (!$msg['is_read']): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="mark_read" value="<?= (int)$msg['id'] ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" title="Marquer comme lu">
                                    <i class="fa-solid fa-check"></i> Lu
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Supprimer ce message ?')">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="delete" value="<?= (int)$msg['id'] ?>">
                                <button type="submit" class="btn btn-sm" style="background:var(--red,#e74c3c);color:#fff;border-color:transparent;" title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="admin-card-body" style="padding-top:0.75rem;white-space:pre-wrap;color:var(--text-muted);font-size:0.9rem;line-height:1.7;">
                        <?= escape($msg['message']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>
