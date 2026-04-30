<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

$db      = getDB();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id         = (int)($_POST['id'] ?? 0);
            $label      = trim($_POST['label']       ?? 'Adresse principale');
            $first_name = trim($_POST['first_name']  ?? '');
            $last_name  = trim($_POST['last_name']   ?? '');
            $company    = trim($_POST['company']     ?? '');
            $address1   = trim($_POST['address1']    ?? '');
            $city       = trim($_POST['city']        ?? '');
            $postal     = trim($_POST['postal_code'] ?? '');
            $country    = trim($_POST['country']     ?? 'France');
            $phone      = trim($_POST['phone']       ?? '');
            $is_default = isset($_POST['is_default']) ? 1 : 0;

            if (empty($address1) || empty($city) || empty($postal)) {
                $error = 'Adresse, ville et code postal sont obligatoires.';
            } else {
                if ($is_default) {
                    $db->prepare('UPDATE address SET is_default=0 WHERE user_id=?')
                       ->execute([$_SESSION['user_id']]);
                }
                if ($id > 0) {
                    // Vérif appartenance
                    $chk = $db->prepare('SELECT id FROM address WHERE id=? AND user_id=?');
                    $chk->execute([$id, $_SESSION['user_id']]);
                    if ($chk->fetch()) {
                        $db->prepare('UPDATE address SET label=?,first_name=?,last_name=?,company=?,address1=?,city=?,postal_code=?,country=?,phone=?,is_default=? WHERE id=?')
                           ->execute([$label,$first_name,$last_name,$company,$address1,$city,$postal,$country,$phone,$is_default,$id]);
                        $success = 'Adresse mise à jour.';
                    }
                } else {
                    $db->prepare('INSERT INTO address (user_id,label,first_name,last_name,company,address1,city,postal_code,country,phone,is_default) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                       ->execute([$_SESSION['user_id'],$label,$first_name,$last_name,$company,$address1,$city,$postal,$country,$phone,$is_default]);
                    $success = 'Adresse ajoutée.';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM address WHERE id=? AND user_id=?')->execute([$id, $_SESSION['user_id']]);
            $success = 'Adresse supprimée.';
        } elseif ($action === 'set_default') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('UPDATE address SET is_default=0 WHERE user_id=?')->execute([$_SESSION['user_id']]);
            $db->prepare('UPDATE address SET is_default=1 WHERE id=? AND user_id=?')->execute([$id, $_SESSION['user_id']]);
            $success = 'Adresse principale mise à jour.';
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM address WHERE id=? AND user_id=?');
    $stmt->execute([(int)$_GET['edit'], $_SESSION['user_id']]);
    $editing = $stmt->fetch();
}

$addresses = $db->prepare('SELECT * FROM address WHERE user_id=? ORDER BY is_default DESC, id ASC');
$addresses->execute([$_SESSION['user_id']]);
$addresses = $addresses->fetchAll();

$page_title = 'Mes adresses';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Mes adresses</h1>
        <p>Gérez vos adresses de facturation</p>
    </div>
</div>

<div class="page-content" style="max-width:900px;">

    <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div><?php endif; ?>

    <!-- Formulaire -->
    <div class="admin-card" style="margin-bottom:1.5rem;">
        <div class="admin-card-header">
            <div class="admin-card-title">
                <i class="fa-solid fa-<?= $editing ? 'pen' : 'plus' ?>"></i>
                <?= $editing ? 'Modifier l\'adresse' : 'Ajouter une adresse' ?>
            </div>
        </div>
        <div class="admin-card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="save">
                <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label>Étiquette</label>
                        <input type="text" name="label" value="<?= escape($editing['label'] ?? 'Adresse principale') ?>" placeholder="Ex: Bureau, Domicile…">
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="first_name" value="<?= escape($editing['first_name'] ?? '') ?>" placeholder="Jean">
                    </div>
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="last_name" value="<?= escape($editing['last_name'] ?? '') ?>" placeholder="Dupont">
                    </div>
                </div>

                <div class="form-group">
                    <label>Société</label>
                    <input type="text" name="company" value="<?= escape($editing['company'] ?? '') ?>" placeholder="Mon Entreprise SAS">
                </div>

                <div class="form-group">
                    <label>Adresse <span style="color:var(--red)">*</span></label>
                    <input type="text" name="address1" required value="<?= escape($editing['address1'] ?? '') ?>" placeholder="12 rue de la Paix">
                </div>

                <div style="display:grid;grid-template-columns:120px 1fr 1fr 80px;gap:1rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Code postal <span style="color:var(--red)">*</span></label>
                        <input type="text" name="postal_code" required value="<?= escape($editing['postal_code'] ?? '') ?>" placeholder="75001">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Ville <span style="color:var(--red)">*</span></label>
                        <input type="text" name="city" required value="<?= escape($editing['city'] ?? '') ?>" placeholder="Paris">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Pays</label>
                        <input type="text" name="country" value="<?= escape($editing['country'] ?? 'France') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Téléphone</label>
                        <input type="text" name="phone" value="<?= escape($editing['phone'] ?? '') ?>" placeholder="06…">
                    </div>
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label style="display:flex;align-items:center;gap:0.6rem;cursor:pointer;font-weight:normal;">
                        <input type="checkbox" name="is_default" value="1"
                               <?= ($editing && $editing['is_default']) ? 'checked' : '' ?>
                               style="width:18px;height:18px;accent-color:var(--primary);">
                        Définir comme adresse principale
                    </label>
                </div>

                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <?= $editing ? 'Enregistrer' : 'Ajouter' ?>
                    </button>
                    <?php if ($editing): ?>
                        <a href="/cyna/adresses.php" class="btn btn-secondary">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des adresses -->
    <?php if (!empty($addresses)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <?php foreach ($addresses as $addr): ?>
        <div class="address-card <?= $addr['is_default'] ? 'address-card-default' : '' ?>">
            <?php if ($addr['is_default']): ?>
                <span class="address-default-badge"><i class="fa-solid fa-star"></i> Principale</span>
            <?php endif; ?>
            <div class="address-label"><?= escape($addr['label']) ?></div>
            <div class="address-name"><?= escape(trim(($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? ''))) ?></div>
            <?php if ($addr['company']): ?>
                <div style="font-size:0.82rem;color:var(--text-muted);"><?= escape($addr['company']) ?></div>
            <?php endif; ?>
            <div><?= escape($addr['address1']) ?></div>
            <div><?= escape($addr['postal_code']) ?> <?= escape($addr['city']) ?>, <?= escape($addr['country']) ?></div>
            <?php if ($addr['phone']): ?>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.3rem;"><?= escape($addr['phone']) ?></div>
            <?php endif; ?>
            <div class="address-actions">
                <a href="/cyna/adresses.php?edit=<?= (int)$addr['id'] ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-pen"></i> Modifier
                </a>
                <?php if (!$addr['is_default']): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="set_default">
                    <input type="hidden" name="id" value="<?= (int)$addr['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-star"></i></button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette adresse ?')">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$addr['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <a href="/cyna/espace-client.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Retour à l'espace client
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
