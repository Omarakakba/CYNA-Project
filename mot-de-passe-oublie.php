<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/mail.php';
require_once __DIR__ . '/includes/rate_limit.php';
session_start();
checkRateLimit('reset_password', 3, 600); // max 3 demandes / 10 min par IP

$db      = getDB();
$error   = '';
$success = '';
$step    = $_GET['step'] ?? 'request'; // request | reset

// Étape 2 : réinitialisation avec token
if ($step === 'reset') {
    $token = trim($_GET['token'] ?? '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Session expirée.';
        } else {
            $token    = trim($_POST['token'] ?? '');
            $new_pw   = $_POST['new_password']     ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            $stmt = $db->prepare('SELECT id FROM user WHERE reset_token = ? AND reset_token_exp > NOW()');
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Ce lien est invalide ou a expiré. Faites une nouvelle demande.';
            } elseif (strlen($new_pw) < 8) {
                $error = 'Le mot de passe doit faire au moins 8 caractères.';
            } elseif ($new_pw !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                $hash = password_hash($new_pw, PASSWORD_BCRYPT);
                $db->prepare('UPDATE user SET password=?, reset_token=NULL, reset_token_exp=NULL WHERE id=?')
                   ->execute([$hash, $user['id']]);
                $success = 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.';
                $step = 'done';
            }
        }
    } else {
        // Vérifie validité du token
        $stmt = $db->prepare('SELECT id FROM user WHERE reset_token = ? AND reset_token_exp > NOW()');
        $stmt->execute([$token]);
        if (!$stmt->fetch()) {
            $error = 'Ce lien est invalide ou a expiré.';
            $step  = 'request';
        }
    }

// Étape 1 : demande par e-mail
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Session expirée.';
        } else {
            $email = trim($_POST['email'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse e-mail invalide.';
            } else {
                $stmt = $db->prepare('SELECT id FROM user WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                // Toujours afficher le même message (sécurité : ne pas révéler si l'email existe)
                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $exp   = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $db->prepare('UPDATE user SET reset_token=?, reset_token_exp=? WHERE id=?')
                       ->execute([$token, $exp, $user['id']]);

                    $reset_link = 'http://' . $_SERVER['HTTP_HOST'] . '/cyna/mot-de-passe-oublie.php?step=reset&token=' . $token;
                    sendResetEmail($email, $reset_link);
                    $success = 'Si cette adresse est enregistrée, un lien de réinitialisation a été envoyé.';
                    // Lien affiché en dev comme fallback (log aussi dans logs/mail.log)
                    $dev_link = $reset_link;
                } else {
                    $success = 'Si cette adresse est enregistrée, un lien de réinitialisation a été envoyé.';
                }
            }
        }
    }
}

$page_title = 'Mot de passe oublié';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">

        <?php if ($step === 'done'): ?>
            <div style="text-align:center; padding:1rem 0;">
                <div style="width:64px; height:64px; background:rgba(16,185,129,0.15); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
                    <i class="fa-solid fa-check" style="font-size:1.75rem; color:var(--green);"></i>
                </div>
                <h1 style="font-size:1.4rem; margin-bottom:0.75rem;">Mot de passe réinitialisé !</h1>
                <p style="color:var(--text-muted); margin-bottom:1.75rem;">Votre mot de passe a été modifié avec succès.</p>
                <a href="/cyna/connexion.php" class="btn btn-primary" style="width:100%; justify-content:center;">
                    <i class="fa-solid fa-arrow-right"></i> Se connecter
                </a>
            </div>

        <?php elseif ($step === 'reset'): ?>
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h1>Nouveau mot de passe</h1>
                <p>Choisissez un nouveau mot de passe sécurisé</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div>
                <div style="text-align:center; margin-top:1rem;">
                    <a href="/cyna/mot-de-passe-oublie.php" class="btn btn-secondary" style="width:100%; justify-content:center;">
                        Faire une nouvelle demande
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="token" value="<?= escape($token) ?>">

                    <div class="form-group">
                        <label>Nouveau mot de passe <span style="color:var(--red)">*</span></label>
                        <input type="password" name="new_password" required minlength="8"
                               placeholder="8 caractères minimum" autofocus>
                    </div>
                    <div class="form-group">
                        <label>Confirmer le mot de passe <span style="color:var(--red)">*</span></label>
                        <input type="password" name="confirm_password" required
                               placeholder="Répéter le mot de passe">
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <i class="fa-solid fa-lock"></i> Réinitialiser le mot de passe
                    </button>
                </form>
            <?php endif; ?>

        <?php else: ?>
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fa-solid fa-lock-open"></i>
                </div>
                <h1>Mot de passe oublié ?</h1>
                <p>Saisissez votre adresse e-mail pour recevoir un lien de réinitialisation</p>
            </div>

            <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?></div><?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= escape($success) ?></div>
                <?php if (isset($dev_link)): ?>
                    <div class="alert" style="background:rgba(14,165,233,0.1); border-color:var(--cyan); margin-top:0.75rem; font-size:0.82rem; word-break:break-all;">
                        <strong>Mode développement — lien de réinitialisation :</strong><br>
                        <a href="<?= escape($dev_link) ?>" style="color:var(--cyan);"><?= escape($dev_link) ?></a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <div class="form-group">
                        <label>Adresse e-mail <span style="color:var(--red)">*</span></label>
                        <input type="email" name="email" required
                               placeholder="votre@email.com" autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <i class="fa-solid fa-paper-plane"></i> Envoyer le lien
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <a href="/cyna/connexion.php">
                    <i class="fa-solid fa-arrow-left"></i> Retour à la connexion
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
