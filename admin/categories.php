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
            $name        = trim($_POST['name']        ?? '');
            $slug        = trim($_POST['slug']        ?? '');
            $description = trim($_POST['description'] ?? '');
            $image_url   = trim($_POST['image_url']   ?? '');

            // Auto-génération du slug
            if (empty($slug)) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                $slug = trim($slug, '-');
            }

            if (empty($name)) {
                $error = 'Le nom est obligatoire.';
            } else {
                if ($action === 'create') {
                    try {
                        $db->prepare('INSERT INTO category (name, slug, description, image_url) VALUES (?,?,?,?)')
                           ->execute([$name, $slug, $description ?: null, $image_url ?: null]);
                        $success = 'Catégorie créée.';
                    } catch (\PDOException $e) {
                        $error = 'Ce nom ou slug est déjà utilisé.';
                    }
                } else {
                    $id = (int)($_POST['id'] ?? 0);
                    try {
                        $db->prepare('UPDATE category SET name=?,slug=?,description=?,image_url=? WHERE id=?')
                           ->execute([$name, $slug, $description ?: null, $image_url ?: null, $id]);
                        $success = 'Catégorie mise à jour.';
                    } catch (\PDOException $e) {
                        $error = 'Ce nom ou slug est déjà utilisé.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id  = (int)($_POST['id'] ?? 0);
            $cnt = $db->prepare('SELECT COUNT(*) FROM product WHERE category_id=?');
            $cnt->execute([$id]);
            if ($cnt->fetchColumn() > 0) {
                $error = 'Impossible de supprimer une catégorie qui contient des produits.';
            } else {
                $db->prepare('DELETE FROM category WHERE id=?')->execute([$id]);
                $success = 'Catégorie supprimée.';
            }
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM category WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$categories = $db->query('SELECT c.*, COUNT(p.id) AS nb FROM category c LEFT JOIN product p ON p.category_id = c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion catégories — CYNA Admin</title>
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
        <a href="/cyna/admin/" class="admin-nav-item"><i class="fa-solid fa-house"></i> Tableau de bord</a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Catalogue</div>
        <a href="/cyna/admin/produits.php" class="admin-nav-item"><i class="fa-solid fa-box"></i> Produits</a>
        <a href="/cyna/admin/carousel.php" class="admin-nav-item"><i class="fa-solid fa-images"></i> Carousel</a>
        <a href="/cyna/admin/categories.php" class="admin-nav-item active"><i class="fa-solid fa-tags"></i> Catégories</a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Ventes</div>
        <a href="/cyna/admin/commandes.php" class="admin-nav-item"><i class="fa-solid fa-receipt"></i> Commandes</a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Utilisateurs</div>
        <a href="/cyna/admin/utilisateurs.php" class="admin-nav-item"><i class="fa-solid fa-users"></i> Utilisateurs</a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Communication</div>
        <?php $_nr = (int)getDB()->query('SELECT COUNT(*) FROM contact_message WHERE is_read=0')->fetchColumn(); ?>
        <a href="/cyna/admin/messages.php" class="admin-nav-item">
            <i class="fa-solid fa-envelope"></i> Messages de contact
            <?php if ($_nr > 0): ?><span style="margin-left:auto;background:var(--accent);color:#fff;border-radius:20px;padding:0.1rem 0.5rem;font-size:0.7rem;font-weight:700;"><?= $_nr ?></span><?php endif; ?>
        </a>
        <hr class="admin-nav-divider">
        <div class="admin-nav-section">Système</div>
        <a href="/cyna/" class="admin-nav-item"><i class="fa-solid fa-globe"></i> Voir le site</a>
    </aside>

    <main class="admin-content">
        <div class="admin-topbar">
            <h1><?= $editing ? 'Modifier la catégorie' : 'Gestion des catégories' ?></h1>
        </div>

        <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div><?php endif; ?>

        <!-- Formulaire -->
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <i class="fa-solid fa-<?= $editing ? 'pen' : 'plus' ?>"></i>
                    <?= $editing ? 'Modifier : ' . escape($editing['name']) : 'Ajouter une catégorie' ?>
                </div>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label>Nom <span style="color:var(--red)">*</span></label>
                            <input type="text" name="name" required
                                   value="<?= escape($editing['name'] ?? '') ?>"
                                   placeholder="Ex: EDR / Endpoint">
                        </div>
                        <div class="form-group">
                            <label>Slug (URL)</label>
                            <input type="text" name="slug"
                                   value="<?= escape($editing['slug'] ?? '') ?>"
                                   placeholder="ex: edr-endpoint (auto-généré si vide)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description"
                               value="<?= escape($editing['description'] ?? '') ?>"
                               placeholder="Courte description affichée dans le catalogue">
                    </div>

                    <div class="form-group">
                        <label>Image (URL)</label>
                        <input type="url" name="image_url"
                               value="<?= escape($editing['image_url'] ?? '') ?>"
                               placeholder="https://...">
                    </div>

                    <div style="display:flex;gap:0.75rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-<?= $editing ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $editing ? 'Enregistrer' : 'Ajouter' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="/cyna/admin/categories.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title"><i class="fa-solid fa-tags"></i> Catégories (<?= count($categories) ?>)</div>
            </div>
            <div class="admin-card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Slug</th>
                            <th>Produits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucune catégorie</td></tr>
                    <?php else: foreach ($categories as $cat): ?>
                        <tr>
                            <td><strong>#<?= (int)$cat['id'] ?></strong></td>
                            <td>
                                <div style="font-weight:600;"><?= escape($cat['name']) ?></div>
                                <?php if ($cat['description']): ?>
                                    <div style="font-size:0.78rem;color:var(--text-muted);"><?= escape(mb_strimwidth($cat['description'], 0, 60, '…')) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><code style="font-size:0.78rem;background:var(--surface-2);padding:0.15rem 0.4rem;border-radius:4px;"><?= escape($cat['slug']) ?></code></td>
                            <td><?= (int)$cat['nb'] ?></td>
                            <td>
                                <div style="display:flex;gap:0.5rem;">
                                    <a href="/cyna/admin/categories.php?edit=<?= (int)$cat['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <?php if ($cat['nb'] == 0): ?>
                                    <form method="POST" onsubmit="return confirm('Supprimer cette catégorie ?')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
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
