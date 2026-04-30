<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
session_start();
requireAdmin();

$db         = getDB();
$categories = $db->query('SELECT * FROM category ORDER BY name')->fetchAll();
$error      = '';
$success    = '';

// CREATE / UPDATE / DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée.';
    } else {
        $action           = $_POST['action'] ?? '';
        $name             = trim($_POST['name']             ?? '');
        $description      = trim($_POST['description']      ?? '');
        $long_description = trim($_POST['long_description'] ?? '');
        $price            = (float)str_replace(',', '.', $_POST['price'] ?? '0');
        $category_id      = (int)($_POST['category_id']    ?? 0);
        $is_available     = isset($_POST['is_available'])   ? 1 : 0;
        $image_url        = trim($_POST['image_url']        ?? '');

        if ($action === 'create') {
            if (empty($name) || $price <= 0 || $category_id === 0) {
                $error = 'Veuillez remplir tous les champs obligatoires.';
            } else {
                $db->prepare('INSERT INTO product (name, description, long_description, price, category_id, is_available, image_url) VALUES (?,?,?,?,?,?,?)')
                   ->execute([$name, $description, $long_description ?: null, $price, $category_id, $is_available, $image_url ?: null]);
                $success = 'Produit ajouté avec succès.';
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            if (empty($name) || $price <= 0 || $category_id === 0 || $id === 0) {
                $error = 'Données invalides.';
            } else {
                $db->prepare('UPDATE product SET name=?,description=?,long_description=?,price=?,category_id=?,is_available=?,image_url=? WHERE id=?')
                   ->execute([$name, $description, $long_description ?: null, $price, $category_id, $is_available, $image_url ?: null, $id]);
                $success = 'Produit mis à jour.';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->prepare('DELETE FROM product WHERE id=?')->execute([$id]);
                $success = 'Produit supprimé.';
            }
        } elseif ($action === 'bulk_delete') {
            $ids = array_filter(array_map('intval', (array)($_POST['ids'] ?? [])));
            if (!empty($ids)) {
                $in = implode(',', $ids);
                $db->query("DELETE FROM product WHERE id IN ($in)");
                $success = count($ids) . ' produit(s) supprimé(s).';
            }
        }
    }
}

// Chargement pour édition
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM product WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$products = $db->query('SELECT p.*, c.name AS category_name FROM product p JOIN category c ON p.category_id = c.id ORDER BY p.name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion produits — CYNA Admin</title>
    
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
        <a href="/cyna/admin/produits.php" class="admin-nav-item active">
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
            <h1><?= $editing ? 'Modifier le produit' : 'Gestion des produits' ?></h1>
            <?php if (!$editing): ?>
            <div class="admin-topbar-right">
                <a href="#form-ajout" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Ajouter un produit
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div><?php endif; ?>

        <!-- Formulaire ajout / édition -->
        <div class="admin-card" id="form-ajout" style="margin-bottom:1.5rem;">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <i class="fa-solid fa-<?= $editing ? 'pen' : 'plus' ?>"></i>
                    <?= $editing ? 'Modifier : ' . escape($editing['name']) : 'Ajouter un produit' ?>
                </div>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action"     value="<?= $editing ? 'update' : 'create' ?>">
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
                    <?php endif; ?>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Nom du produit <span style="color:var(--red)">*</span></label>
                            <input type="text" name="name" required
                                   value="<?= escape($editing['name'] ?? '') ?>"
                                   placeholder="Ex: CrowdStrike Falcon">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Catégorie <span style="color:var(--red)">*</span></label>
                            <select name="category_id" required>
                                <option value="">— Choisir —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"
                                        <?= isset($editing) && $editing['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= escape($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description courte</label>
                        <input type="text" name="description"
                               value="<?= escape($editing['description'] ?? '') ?>"
                               placeholder="Description courte du produit">
                    </div>

                    <div class="form-group">
                        <label>Description longue</label>
                        <textarea name="long_description" rows="4"
                                  placeholder="Description détaillée affichée sur la fiche produit…"
                                  style="width:100%;padding:0.75rem 1rem;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:0.9rem;resize:vertical;box-sizing:border-box;"><?= escape($editing['long_description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Image du produit (URL)</label>
                        <input type="url" name="image_url"
                               value="<?= escape($editing['image_url'] ?? '') ?>"
                               placeholder="https://... ou /cyna/assets/uploads/products/nom.jpg">
                        <?php if (!empty($editing['image_url'])): ?>
                            <div style="margin-top:0.5rem;">
                                <img src="<?= escape($editing['image_url']) ?>"
                                     alt="Aperçu"
                                     style="max-height:80px;border-radius:8px;border:1px solid var(--border);"
                                     onerror="this.style.display='none'">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="max-width:220px;">
                        <label>Prix mensuel (€) <span style="color:var(--red)">*</span></label>
                        <input type="number" name="price" step="0.01" min="0.01" required
                               value="<?= $editing ? number_format($editing['price'], 2, '.', '') : '' ?>"
                               placeholder="9.99">
                    </div>

                    <div class="form-group">
                        <label>Disponibilité</label>
                        <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-weight:normal; color:var(--text);">
                            <input type="checkbox" name="is_available" value="1"
                                   <?= (!$editing || $editing['is_available']) ? 'checked' : '' ?>
                                   style="width:18px; height:18px; cursor:pointer; accent-color:var(--primary);">
                            Disponible immédiatement
                        </label>
                        <small style="color:var(--text-muted); display:block; margin-top:0.35rem;">
                            Si décoché, le produit affichera « Service momentanément indisponible »
                        </small>
                    </div>

                    <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-<?= $editing ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $editing ? 'Enregistrer les modifications' : 'Ajouter le produit' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="/cyna/admin/produits.php" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des produits -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">
                    <i class="fa-solid fa-list"></i> Liste des produits
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <span style="font-size:0.8rem; color:var(--text-muted);"><?= count($products) ?> produit<?= count($products) > 1 ? 's' : '' ?></span>
                    <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm" style="display:none;" onclick="bulkDelete()">
                        <i class="fa-solid fa-trash"></i> Supprimer la sélection
                    </button>
                </div>
            </div>
            <div class="admin-card-body">
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="bulk_delete">
                </form>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" title="Tout sélectionner" style="width:16px;height:16px;"></th>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Prix / mois</th>
                            <th>Dispo</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:2rem;">Aucun produit</td></tr>
                        <?php else: foreach ($products as $p): ?>
                        <tr>
                            <td><input type="checkbox" class="bulk-cb" data-id="<?= (int)$p['id'] ?>" style="width:16px;height:16px;"></td>
                            <td><strong>#<?= (int)$p['id'] ?></strong></td>
                            <td><?= escape($p['name']) ?></td>
                            <td><span class="product-badge"><?= escape($p['category_name']) ?></span></td>
                            <td><?= number_format($p['price'], 2) ?> €</td>
                            <td>
                                <?php if ($p['is_available']): ?>
                                    <span class="status-badge status-paid"><i class="fa-solid fa-circle-check"></i></span>
                                <?php else: ?>
                                    <span class="status-badge status-cancelled"><i class="fa-solid fa-clock"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:0.5rem;">
                                    <a href="/cyna/admin/produits.php?edit=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-pen"></i> Modifier
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Supprimer ce produit définitivement ?')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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

<script>
// Sélection multiple + suppression en lot
const selectAll    = document.getElementById('selectAll');
const bulkBtn      = document.getElementById('bulkDeleteBtn');
const checkboxes   = () => document.querySelectorAll('.bulk-cb');

function updateBulkBtn() {
    const checked = document.querySelectorAll('.bulk-cb:checked').length;
    bulkBtn.style.display = checked > 0 ? 'inline-flex' : 'none';
    bulkBtn.textContent = '';
    bulkBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Supprimer (' + checked + ')';
}

if (selectAll) {
    selectAll.addEventListener('change', () => {
        checkboxes().forEach(cb => cb.checked = selectAll.checked);
        updateBulkBtn();
    });
    document.querySelectorAll('.bulk-cb').forEach(cb => cb.addEventListener('change', updateBulkBtn));
}

function bulkDelete() {
    const ids = [...document.querySelectorAll('.bulk-cb:checked')].map(c => c.dataset.id);
    if (!ids.length) return;
    if (!confirm('Supprimer ' + ids.length + ' produit(s) définitivement ?')) return;
    const form = document.getElementById('bulkForm');
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
        form.appendChild(inp);
    });
    form.submit();
}
</script>

<script src="/cyna/assets/js/main.js"></script>
</body>
</html>
