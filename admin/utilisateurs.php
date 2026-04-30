<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
session_start();
requireAdmin();

$db      = getDB();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['user_id'] ?? 0);

        if ($id <= 0) {
            $error = 'Utilisateur invalide.';
        } elseif ($id === (int)$_SESSION['user_id']) {
            $error = 'Vous ne pouvez pas modifier votre propre rôle depuis ce panneau.';
        } elseif ($action === 'set_role') {
            $role = $_POST['role'] ?? '';
            if (!in_array($role, ['user', 'admin'])) {
                $error = 'Rôle invalide.';
            } else {
                $db->prepare('UPDATE user SET role=? WHERE id=?')->execute([$role, $id]);
                $success = 'Rôle mis à jour.';
            }
        } elseif ($action === 'delete') {
            // Sécurité : ne pas supprimer un admin
            $chk = $db->prepare('SELECT role FROM user WHERE id=?');
            $chk->execute([$id]);
            $target = $chk->fetch();
            if ($target && $target['role'] === 'admin') {
                $error = 'Impossible de supprimer un administrateur.';
            } else {
                // Anonymiser les commandes (obligation légale de conservation)
                $db->prepare('UPDATE `order` SET user_id = NULL WHERE user_id = ?')->execute([$id]);
                // Supprimer les données personnelles liées
                $db->prepare('DELETE FROM address WHERE user_id = ?')->execute([$id]);
                $db->prepare('DELETE FROM user WHERE id = ?')->execute([$id]);
                $success = 'Utilisateur supprimé et données anonymisées.';
            }
        }
    }
}

$search = trim($_GET['s'] ?? '');
if ($search !== '') {
    $stmt = $db->prepare('SELECT u.*, COUNT(o.id) AS nb_orders FROM user u LEFT JOIN `order` o ON o.user_id = u.id WHERE u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? GROUP BY u.id ORDER BY u.created_at DESC');
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $db->query('SELECT u.*, COUNT(o.id) AS nb_orders FROM user u LEFT JOIN `order` o ON o.user_id = u.id GROUP BY u.id ORDER BY u.created_at DESC');
}
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion utilisateurs — CYNA Admin</title>
    <link rel="stylesheet" href="/cyna/assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="/cyna/assets/css/style.css?v=6">
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
        <a href="/cyna/admin/utilisateurs.php" class="admin-nav-item active">
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
            <h1>Gestion des utilisateurs</h1>
            <div class="admin-topbar-right">
                <span style="font-size:0.85rem; color:var(--text-muted);"><?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?></span>
            </div>
        </div>

        <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div><?php endif; ?>

        <!-- Barre de recherche -->
        <form method="GET" style="margin-bottom:1.5rem; display:flex; gap:0.75rem;">
            <input type="search" name="s" value="<?= escape($search) ?>"
                   placeholder="Rechercher par e-mail, prénom ou nom…"
                   style="flex:1; padding:0.6rem 1rem; background:var(--surface); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.9rem;">
            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Rechercher
            </button>
            <?php if ($search !== ''): ?>
                <a href="/cyna/admin/utilisateurs.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-xmark"></i> Réinitialiser
                </a>
            <?php endif; ?>
        </form>

        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <i class="fa-solid fa-users"></i> Liste des utilisateurs
                </div>
            </div>
            <div class="admin-card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom / E-mail</th>
                            <th>Rôle</th>
                            <th>Commandes</th>
                            <th>Inscrit le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:2rem;">Aucun utilisateur</td></tr>
                        <?php else: foreach ($users as $u): ?>
                        <tr <?= $u['id'] == $_SESSION['user_id'] ? 'style="background:rgba(99,102,241,0.05);"' : '' ?>>
                            <td><strong>#<?= (int)$u['id'] ?></strong></td>
                            <td>
                                <?php if ($u['first_name'] || $u['last_name']): ?>
                                    <div style="font-weight:600;"><?= escape(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></div>
                                <?php endif; ?>
                                <div style="font-size:0.82rem; color:var(--text-muted);"><?= escape($u['email']) ?></div>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="status-badge status-paid">Admin</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Utilisateur</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$u['nb_orders'] ?></td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                    <!-- Changer le rôle -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="set_role">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <select name="role" onchange="this.form.submit()"
                                                style="font-size:0.78rem; padding:0.3rem 0.5rem; border:1px solid var(--border); border-radius:6px; background:var(--surface); color:var(--text); cursor:pointer;">
                                            <option value="user"  <?= $u['role'] === 'user'  ? 'selected' : '' ?>>Utilisateur</option>
                                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    </form>
                                    <!-- Supprimer -->
                                    <?php if ($u['role'] !== 'admin'): ?>
                                    <form method="POST" onsubmit="return confirm('Supprimer cet utilisateur définitivement ?')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                    <span style="font-size:0.78rem; color:var(--text-muted);">Vous</span>
                                <?php endif; ?>
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
