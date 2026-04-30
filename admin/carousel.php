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

        if ($action === 'create' || $action === 'update') {
            $title      = trim($_POST['title']      ?? '');
            $subtitle   = trim($_POST['subtitle']   ?? '');
            $link_url   = trim($_POST['link_url']   ?? '');
            $link_label = trim($_POST['link_label'] ?? 'Découvrir');
            $bg_color   = trim($_POST['bg_color']   ?? '#0f172a');
            $sort_order = (int)($_POST['sort_order'] ?? 0);
            $is_active  = isset($_POST['is_active']) ? 1 : 0;
            $image_url  = trim($_POST['image_url']  ?? '');

            if (empty($title)) {
                $error = 'Le titre est obligatoire.';
            } else {
                if ($action === 'create') {
                    $db->prepare('INSERT INTO slide (title,subtitle,link_url,link_label,bg_color,sort_order,is_active,image_url) VALUES (?,?,?,?,?,?,?,?)')
                       ->execute([$title,$subtitle,$link_url,$link_label,$bg_color,$sort_order,$is_active,$image_url]);
                    $success = 'Slide ajouté.';
                } else {
                    $id = (int)($_POST['id'] ?? 0);
                    $db->prepare('UPDATE slide SET title=?,subtitle=?,link_url=?,link_label=?,bg_color=?,sort_order=?,is_active=?,image_url=? WHERE id=?')
                       ->execute([$title,$subtitle,$link_url,$link_label,$bg_color,$sort_order,$is_active,$image_url,$id]);
                    $success = 'Slide mis à jour.';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->prepare('DELETE FROM slide WHERE id=?')->execute([$id]);
                $success = 'Slide supprimé.';
            }
        } elseif ($action === 'toggle') {
            $id  = (int)($_POST['id'] ?? 0);
            $val = (int)($_POST['is_active'] ?? 0);
            $db->prepare('UPDATE slide SET is_active=? WHERE id=?')->execute([$val ? 0 : 1, $id]);
            $success = 'Visibilité mise à jour.';
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM slide WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$slides = $db->query('SELECT * FROM slide ORDER BY sort_order ASC, id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion carousel — CYNA Admin</title>
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
        <span style="color:rgba(255,255,255,0.4);font-size:0.8rem;margin-left:0.5rem;">Administration</span>
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
        <a href="/cyna/admin/carousel.php" class="admin-nav-item active">
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
            <h1><?= $editing ? 'Modifier le slide' : 'Gestion du carousel' ?></h1>
            <div class="admin-topbar-right">
                <a href="/cyna/" target="_blank" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-eye"></i> Voir le résultat
                </a>
            </div>
        </div>

        <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div><?php endif; ?>

        <!-- Formulaire ajout/édition -->
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <i class="fa-solid fa-<?= $editing ? 'pen' : 'plus' ?>"></i>
                    <?= $editing ? 'Modifier : ' . escape($editing['title']) : 'Ajouter un slide' ?>
                </div>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

                    <div class="form-group">
                        <label>Titre <span style="color:var(--red)">*</span></label>
                        <input type="text" name="title" required
                               value="<?= escape($editing['title'] ?? '') ?>"
                               placeholder="Ex : Protection EDR en temps réel">
                    </div>

                    <div class="form-group">
                        <label>Sous-titre / description</label>
                        <input type="text" name="subtitle"
                               value="<?= escape($editing['subtitle'] ?? '') ?>"
                               placeholder="Description courte affichée sous le titre">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>URL du bouton</label>
                            <input type="text" name="link_url"
                                   value="<?= escape($editing['link_url'] ?? '') ?>"
                                   placeholder="/cyna/catalogue.php?cat=1">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Texte du bouton</label>
                            <input type="text" name="link_label"
                                   value="<?= escape($editing['link_label'] ?? 'Découvrir') ?>"
                                   placeholder="Découvrir">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 80px;gap:1rem;margin-top:1rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Image de fond (URL)</label>
                            <input type="text" name="image_url"
                                   value="<?= escape($editing['image_url'] ?? '') ?>"
                                   placeholder="https://... ou /cyna/assets/...">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Couleur de fond (CSS)</label>
                            <input type="text" name="bg_color"
                                   value="<?= escape($editing['bg_color'] ?? 'linear-gradient(135deg,#0f172a,#1e3a5f)') ?>"
                                   placeholder="linear-gradient(...)">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Ordre</label>
                            <input type="number" name="sort_order" min="0"
                                   value="<?= (int)($editing['sort_order'] ?? 0) ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:1rem;">
                        <label style="display:flex;align-items:center;gap:0.6rem;cursor:pointer;font-weight:normal;">
                            <input type="checkbox" name="is_active" value="1"
                                   <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>
                                   style="width:18px;height:18px;accent-color:var(--primary);">
                            Slide actif (visible sur le site)
                        </label>
                    </div>

                    <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-<?= $editing ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $editing ? 'Enregistrer' : 'Ajouter le slide' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="/cyna/admin/carousel.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste slides -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="fa-solid fa-list"></i> Slides (<?= count($slides) ?>)</div>
            </div>
            <div class="admin-card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ordre</th>
                            <th>Aperçu</th>
                            <th>Titre</th>
                            <th>Bouton</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($slides)): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucun slide</td></tr>
                    <?php else: foreach ($slides as $s): ?>
                        <tr>
                            <td><strong><?= (int)$s['sort_order'] ?></strong></td>
                            <td>
                                <div style="width:80px;height:40px;border-radius:6px;background:<?= escape($s['bg_color']) ?>;display:flex;align-items:center;justify-content:center;">
                                    <i class="fa-solid fa-image" style="color:rgba(255,255,255,0.4);font-size:0.9rem;"></i>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?= escape($s['title']) ?></div>
                                <div style="font-size:0.78rem;color:var(--text-muted);"><?= escape(mb_strimwidth($s['subtitle'] ?? '', 0, 60, '…')) ?></div>
                            </td>
                            <td style="font-size:0.82rem;"><?= escape($s['link_label']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= (int)$s['is_active'] ?>">
                                    <button type="submit" class="status-badge <?= $s['is_active'] ? 'status-paid' : 'status-cancelled' ?>"
                                            style="border:none;cursor:pointer;font-family:inherit;">
                                        <?= $s['is_active'] ? 'Actif' : 'Masqué' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.5rem;">
                                    <a href="/cyna/admin/carousel.php?edit=<?= (int)$s['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Supprimer ce slide ?')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
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
