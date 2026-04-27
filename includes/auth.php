<?php
require_once __DIR__ . '/config.php';

// Démarre la session si pas déjà démarrée
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Connecte un utilisateur (retourne true si succès)
function login(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, email, password, role FROM user WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // Évite la fixation de session
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        return true;
    }
    return false;
}

// Déconnecte l'utilisateur
function logout(): void {
    session_destroy();
    header('Location: /connexion.php');
    exit;
}

// Vérifie si l'utilisateur est connecté
function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

// Vérifie si l'utilisateur est admin
function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

// Redirige si pas connecté
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /connexion.php');
        exit;
    }
}

// Redirige si pas admin
function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        exit('Accès interdit.');
    }
}
