<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mail.php';
require_once __DIR__ . '/includes/rate_limit.php';
session_start();

if (isLoggedIn()) { header('Location: /cyna/espace-client.php'); exit; }

checkRateLimit('register', 5, 3600); // max 5 inscriptions / heure par IP
$error = $GLOBALS['rate_limit_error'] ?? '';

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $email   = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        $cgu = isset($_POST['cgu']) && $_POST['cgu'] === '1';

        if (empty($email) || empty($password) || empty($confirm)) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse email invalide.';
        } elseif (strlen($password) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($password !== $confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } elseif (!$cgu) {
            $error = 'Vous devez accepter les conditions générales d\'utilisation.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare('SELECT id FROM user WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $db->prepare('INSERT INTO user (email, password, role, cgu_accepted_at, cgu_version) VALUES (?, ?, "user", NOW(), "1.0")')
                   ->execute([$email, $hash]);
                sendWelcomeEmail($email);
                login($email, $password);
                header('Location: /cyna/espace-client.php'); exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte — CYNA</title>
    <link rel="stylesheet" href="/cyna/assets/css/style.css">
    <link rel="stylesheet" href="/cyna/assets/css/fontawesome.min.css">
</head>
<body class="form-page">

<header>
    <div class="nav-inner">
        <a href="/cyna/" class="nav-logo">
            <div class="nav-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <span class="nav-logo-text">CY<span>NA</span></span>
        </a>
    </div>
</header>

<div class="form-wrap">
    <div class="form-container">
        <div class="form-logo">
            <div class="form-logo-icon"><i class="fa-solid fa-user-plus"></i></div>
            <h1 class="form-title">Créer un compte</h1>
            <p class="form-subtitle">Rejoignez CYNA et sécurisez votre entreprise</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-group">
                <label for="email">Adresse email professionnelle</label>
                <input type="email" id="email" name="email"
                       placeholder="vous@entreprise.fr"
                       value="<?= escape($_POST['email'] ?? '') ?>"
                       required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe <span style="font-weight:400; color:var(--text-muted)">(8 caractères min.)</span></label>
                <input type="password" id="password" name="password" placeholder="••••••••" required minlength="8" autocomplete="new-password">
                <!-- Barre de force du mot de passe -->
                <div id="pwd-strength-bar" style="display:none;margin-top:0.6rem;">
                    <div style="height:4px;border-radius:2px;background:var(--border);overflow:hidden;">
                        <div id="pwd-strength-fill" style="height:100%;width:0;transition:width .25s,background .25s;border-radius:2px;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.35rem;">
                        <span id="pwd-strength-label" style="font-size:0.75rem;font-weight:600;"></span>
                        <span id="pwd-strength-hints" style="font-size:0.72rem;color:var(--text-muted);"></span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm">Confirmer le mot de passe</label>
                <input type="password" id="confirm" name="confirm" placeholder="••••••••" required minlength="8" autocomplete="new-password">
                <div id="pwd-match-msg" style="display:none;font-size:0.78rem;margin-top:0.35rem;font-weight:600;"></div>
            </div>
            <div class="form-group" style="margin-bottom:0.5rem;">
                <label style="display:flex;align-items:flex-start;gap:0.6rem;cursor:pointer;font-weight:normal;color:var(--text-muted);font-size:0.875rem;line-height:1.5;">
                    <input type="checkbox" name="cgu" value="1" id="cgu-check" required
                           style="width:16px;height:16px;margin-top:2px;flex-shrink:0;accent-color:var(--primary);"
                           <?= isset($_POST['cgu']) ? 'checked' : '' ?>>
                    J'accepte les
                    <a href="/cyna/cgu.php" target="_blank" style="color:var(--accent);">Conditions Générales d'Utilisation</a>
                    et la politique de confidentialité de CYNA.
                </label>
            </div>
            <button type="submit" id="submit-btn" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
                Créer mon compte
            </button>
        </form>

        <p class="form-footer">
            Déjà un compte ? <a href="/cyna/connexion.php">Se connecter</a>
        </p>
    </div>
</div>

<script src="/cyna/assets/js/main.js"></script>
<script>
(function () {
    const pwdInput   = document.getElementById('password');
    const confirmInput = document.getElementById('confirm');
    const bar        = document.getElementById('pwd-strength-bar');
    const fill       = document.getElementById('pwd-strength-fill');
    const label      = document.getElementById('pwd-strength-label');
    const hints      = document.getElementById('pwd-strength-hints');
    const matchMsg   = document.getElementById('pwd-match-msg');
    const submitBtn  = document.getElementById('submit-btn');

    const checks = [
        { re: /.{8,}/,   text: '8+ caractères' },
        { re: /[A-Z]/,   text: 'majuscule' },
        { re: /[a-z]/,   text: 'minuscule' },
        { re: /[0-9]/,   text: 'chiffre' },
        { re: /[^A-Za-z0-9]/, text: 'caractère spécial' },
    ];

    const levels = [
        { label: 'Très faible', color: '#e74c3c', pct: 20 },
        { label: 'Faible',      color: '#e67e22', pct: 40 },
        { label: 'Moyen',       color: '#f1c40f', pct: 60 },
        { label: 'Fort',        color: '#2ecc71', pct: 80 },
        { label: 'Très fort',   color: '#27ae60', pct: 100 },
    ];

    function evalStrength(pwd) {
        return checks.reduce((acc, c) => acc + (c.re.test(pwd) ? 1 : 0), 0);
    }

    function updateStrength() {
        const pwd = pwdInput.value;
        if (!pwd) { bar.style.display = 'none'; return; }
        bar.style.display = 'block';
        const score  = evalStrength(pwd);
        const lvl    = levels[score - 1] || levels[0];
        fill.style.width      = lvl.pct + '%';
        fill.style.background = lvl.color;
        label.textContent     = lvl.label;
        label.style.color     = lvl.color;
        const missing = checks.filter(c => !c.re.test(pwd)).map(c => c.text);
        hints.textContent = missing.length ? 'Manque : ' + missing.join(', ') : '';
        updateSubmit();
    }

    function updateMatch() {
        const pwd = pwdInput.value;
        const cfm = confirmInput.value;
        if (!cfm) { matchMsg.style.display = 'none'; return; }
        matchMsg.style.display = 'block';
        if (pwd === cfm) {
            matchMsg.textContent = '✓ Les mots de passe correspondent';
            matchMsg.style.color = 'var(--green)';
        } else {
            matchMsg.textContent = '✗ Les mots de passe ne correspondent pas';
            matchMsg.style.color = 'var(--red, #e74c3c)';
        }
        updateSubmit();
    }

    function updateSubmit() {
        const score = evalStrength(pwdInput.value);
        const match = pwdInput.value === confirmInput.value && confirmInput.value !== '';
        submitBtn.disabled = score < 3 || !match;
        submitBtn.style.opacity = submitBtn.disabled ? '0.5' : '1';
    }

    pwdInput.addEventListener('input', () => { updateStrength(); updateMatch(); });
    confirmInput.addEventListener('input', updateMatch);
})();
</script>
</body>
</html>
