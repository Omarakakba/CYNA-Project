<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

$db      = getDB();
$error   = '';
$success = '';

$stmt = $db->prepare('SELECT * FROM user WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée, veuillez réessayer.';
    } else {
        $action = $_POST['action'] ?? '';

        // Mise à jour des informations personnelles
        if ($action === 'info') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name']  ?? '');
            $new_email  = trim($_POST['email']       ?? '');

            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse e-mail invalide.';
            } else {
                // Vérifie que l'email n'est pas déjà utilisé par un autre compte
                $check = $db->prepare('SELECT id FROM user WHERE email = ? AND id != ?');
                $check->execute([$new_email, $_SESSION['user_id']]);
                if ($check->fetch()) {
                    $error = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
                } else {
                    $db->prepare('UPDATE user SET first_name=?, last_name=?, email=? WHERE id=?')
                       ->execute([$first_name ?: null, $last_name ?: null, $new_email, $_SESSION['user_id']]);
                    $_SESSION['user_email'] = $new_email;
                    $success = 'Informations mises à jour avec succès.';
                    // Rafraîchit les données
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                }
            }
        }

        // Suppression de compte (RGPD art. 17)
        if ($action === 'delete_account') {
            $confirm_email = trim($_POST['confirm_email'] ?? '');
            if ($confirm_email !== $user['email']) {
                $error = 'L\'adresse e-mail saisie ne correspond pas à votre compte.';
            } else {
                $uid = $_SESSION['user_id'];
                // Anonymiser les commandes (conservation légale) puis supprimer les données perso
                $db->prepare('UPDATE `order` SET user_id = NULL WHERE user_id = ?')->execute([$uid]);
                $db->prepare('DELETE FROM address WHERE user_id = ?')->execute([$uid]);
                $db->prepare('DELETE FROM contact_message WHERE email = ?')->execute([$user['email']]);
                $db->prepare('DELETE FROM rate_limit WHERE ip = ?')->execute([$_SERVER['REMOTE_ADDR'] ?? '']);
                $db->prepare('DELETE FROM user WHERE id = ?')->execute([$uid]);
                // Déconnexion immédiate
                $_SESSION = [];
                session_destroy();
                setcookie('remember_token', '', ['expires' => time() - 1, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
                header('Location: /cyna/?compte_supprime=1'); exit;
            }
        }

        // Changement de mot de passe
        if ($action === 'password') {
            $current  = $_POST['current_password']  ?? '';
            $new_pw   = $_POST['new_password']       ?? '';
            $confirm  = $_POST['confirm_password']   ?? '';

            if (!password_verify($current, $user['password'])) {
                $error = 'Mot de passe actuel incorrect.';
            } elseif (strlen($new_pw) < 8) {
                $error = 'Le nouveau mot de passe doit faire au moins 8 caractères.';
            } elseif ($new_pw !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                $hash = password_hash($new_pw, PASSWORD_BCRYPT);
                $db->prepare('UPDATE user SET password=? WHERE id=?')
                   ->execute([$hash, $_SESSION['user_id']]);
                $success = 'Mot de passe modifié avec succès.';
            }
        }
    }
}

$page_title = 'Mon profil';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Mon profil</h1>
        <p>Gérez vos informations personnelles et votre sécurité</p>
    </div>
</div>

<div class="page-content" style="max-width:760px;">

    <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div><?php endif; ?>

    <!-- Informations personnelles -->
    <div class="admin-card" style="margin-bottom:1.5rem;">
        <div class="admin-card-header">
            <div class="admin-card-title">
                <i class="fa-solid fa-user"></i> Informations personnelles
            </div>
        </div>
        <div class="admin-card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="info">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Prénom</label>
                        <input type="text" name="first_name"
                               value="<?= escape($user['first_name'] ?? '') ?>"
                               placeholder="Votre prénom">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Nom</label>
                        <input type="text" name="last_name"
                               value="<?= escape($user['last_name'] ?? '') ?>"
                               placeholder="Votre nom">
                    </div>
                </div>

                <div class="form-group">
                    <label>Adresse e-mail <span style="color:var(--red)">*</span></label>
                    <input type="email" name="email" required
                           value="<?= escape($user['email']) ?>"
                           placeholder="votre@email.com">
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label style="color:var(--text-muted); font-size:0.8rem;">
                        <i class="fa-solid fa-shield-halved"></i>
                        Membre depuis le <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        — Rôle : <?= ucfirst($user['role']) ?>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:1.25rem;">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>

    <!-- Changement de mot de passe -->
    <div class="admin-card" style="margin-bottom:1.5rem;">
        <div class="admin-card-header">
            <div class="admin-card-title">
                <i class="fa-solid fa-lock"></i> Changer le mot de passe
            </div>
        </div>
        <div class="admin-card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="password">

                <div class="form-group">
                    <label>Mot de passe actuel <span style="color:var(--red)">*</span></label>
                    <input type="password" name="current_password" required
                           placeholder="Saisir votre mot de passe actuel">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Nouveau mot de passe <span style="color:var(--red)">*</span></label>
                        <input type="password" name="new_password" required minlength="8"
                               placeholder="8 caractères minimum">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Confirmer le nouveau mot de passe <span style="color:var(--red)">*</span></label>
                        <input type="password" name="confirm_password" required
                               placeholder="Répéter le mot de passe">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:1.25rem;">
                    <i class="fa-solid fa-key"></i> Modifier le mot de passe
                </button>
            </form>
        </div>
    </div>

    <!-- Suppression de compte (RGPD art. 17) -->
    <div class="admin-card" style="margin-bottom:1.5rem;border-color:var(--red,#e74c3c);">
        <div class="admin-card-header" style="border-bottom-color:var(--red,#e74c3c);">
            <div class="admin-card-title" style="color:var(--red,#e74c3c);">
                <i class="fa-solid fa-triangle-exclamation"></i> Supprimer mon compte
            </div>
        </div>
        <div class="admin-card-body">
            <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:1rem;line-height:1.6;">
                Conformément au <strong>RGPD (art. 17 — droit à l'effacement)</strong>, vous pouvez supprimer votre compte.<br>
                Vos données personnelles seront effacées. Vos commandes seront anonymisées (obligation légale de conservation comptable).
            </p>
            <details>
                <summary style="cursor:pointer;color:var(--red,#e74c3c);font-weight:600;font-size:0.9rem;user-select:none;">
                    Supprimer définitivement mon compte →
                </summary>
                <form method="POST" style="margin-top:1rem;" onsubmit="return confirm('Êtes-vous absolument sûr ? Cette action est irréversible.')">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="form-group">
                        <label>Confirmez votre adresse e-mail pour valider la suppression</label>
                        <input type="email" name="confirm_email" required
                               placeholder="<?= escape($user['email']) ?>"
                               style="border-color:var(--red,#e74c3c);">
                    </div>
                    <button type="submit" class="btn btn-sm" style="background:var(--red,#e74c3c);color:#fff;border:none;">
                        <i class="fa-solid fa-trash"></i> Supprimer définitivement mon compte
                    </button>
                </form>
            </details>
        </div>
    </div>

    <!-- Export données (RGPD art. 20) -->
    <div class="admin-card" style="margin-bottom:1.5rem;">
        <div class="admin-card-header">
            <div class="admin-card-title">
                <i class="fa-solid fa-download"></i> Exporter mes données personnelles
            </div>
        </div>
        <div class="admin-card-body">
            <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:1rem;line-height:1.6;">
                Conformément au <strong>RGPD (art. 20 — droit à la portabilité)</strong>, vous pouvez télécharger toutes vos données personnelles au format JSON : profil, commandes, adresses.
            </p>
            <a href="/cyna/export-donnees.php" class="btn btn-secondary">
                <i class="fa-solid fa-file-arrow-down"></i> Télécharger mes données (JSON)
            </a>
        </div>
    </div>

    <!-- Navigation retour -->
    <div style="display:flex; gap:1rem;">
        <a href="/cyna/espace-client.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour à l'espace client
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
