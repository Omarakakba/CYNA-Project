<?php
require_once __DIR__ . '/config.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function login(string $email, string $password, bool $remember = false): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, email, password, role FROM user WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        if ($remember) {
            // Cookie sécurisé valide 30 jours
            $token = bin2hex(random_bytes(32));
            $exp   = time() + 60 * 60 * 24 * 30;
            setcookie('remember_token', $token, [
                'expires'  => $exp,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $hash = hash('sha256', $token);
            $db->prepare('UPDATE user SET reset_token=?, reset_token_exp=? WHERE id=?')
               ->execute([$hash, date('Y-m-d H:i:s', $exp), $user['id']]);
        }
        return true;
    }
    return false;
}

// Vérifie le cookie "remember me" et reconnecte automatiquement
function checkRememberCookie(): void {
    if (isset($_SESSION['user_id'])) return;
    $token = $_COOKIE['remember_token'] ?? '';
    if (!$token) return;

    $db   = getDB();
    $hash = hash('sha256', $token);
    $stmt = $db->prepare('SELECT id, email, role FROM user WHERE reset_token=? AND reset_token_exp > NOW()');
    $stmt->execute([$hash]);
    $user = $stmt->fetch();

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
    } else {
        // Token invalide ou expiré : supprimer le cookie
        setcookie('remember_token', '', ['expires' => time() - 1, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }
}

function logout(): void {
    startSession();
    if (isset($_COOKIE['remember_token'])) {
        $db   = getDB();
        $hash = hash('sha256', $_COOKIE['remember_token']);
        $db->prepare('UPDATE user SET reset_token=NULL, reset_token_exp=NULL WHERE reset_token=?')->execute([$hash]);
        setcookie('remember_token', '', ['expires' => time() - 1, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }
    $_SESSION = [];
    session_destroy();
    header('Location: /cyna/connexion.php');
    exit;
}

function isLoggedIn(): bool {
    startSession();
    checkRememberCookie();
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $current = $_SERVER['REQUEST_URI'] ?? '';
        $redirect = $current !== '' ? '?redirect=' . urlencode($current) : '';
        header('Location: /cyna/connexion.php' . $redirect);
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        exit('Accès interdit.');
    }
}
