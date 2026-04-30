<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rate_limit.php';
session_start();

if (isLoggedIn()) { header('Location: /cyna/espace-client.php'); exit; }

// Lecture et validation du paramètre de redirection (uniquement URLs internes /cyna/)
function safeRedirect(string $url): string {
    if ($url !== '' && str_starts_with($url, '/cyna/')) {
        return $url;
    }
    return '/cyna/espace-client.php';
}

$redirect = safeRedirect(trim($_GET['redirect'] ?? $_POST['redirect'] ?? ''));

$error = $GLOBALS['rate_limit_error'] ?? '';
checkRateLimit('login', 5, 300); // max 5 tentatives / 5 min
$error = $error ?: ($GLOBALS['rate_limit_error'] ?? '');

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($email) || empty($password)) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif (login($email, $password, isset($_POST['remember']))) {
            clearRateLimit('login');
            if ($_SESSION['user_role'] === 'admin' && $redirect === '/cyna/espace-client.php') {
                header('Location: /cyna/admin/'); exit;
            }
            header('Location: ' . safeRedirect($redirect)); exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}

$page_title = 'Connexion';
$extra_css  = 'auth-page';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <h1>Connexion</h1>
            <p>Accédez à votre espace client CYNA</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= escape($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="redirect" value="<?= escape($redirect) ?>">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       placeholder="vous@entreprise.fr"
                       value="<?= escape($_POST['email'] ?? '') ?>"
                       required autofocus>
            </div>
            <div class="form-group">
                <label for="password">
                    Mot de passe
                    <a href="/cyna/mot-de-passe-oublie.php" class="label-link" tabindex="-1">Oublié ?</a>
                </label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-group" style="margin-bottom:0.25rem;">
                <label style="display:flex;align-items:center;gap:0.6rem;cursor:pointer;font-weight:normal;color:var(--text-muted);font-size:0.875rem;">
                    <input type="checkbox" name="remember" value="1"
                           style="width:16px;height:16px;accent-color:var(--primary);">
                    Se souvenir de moi (30 jours)
                </label>
            </div>
            <button type="submit" class="btn btn-primary auth-submit">
                <i class="fa-solid fa-right-to-bracket"></i> Se connecter
            </button>
        </form>

        <div class="auth-footer">
            Pas encore de compte ?
            <a href="/cyna/inscription.php">Créer un compte</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
