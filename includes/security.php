<?php
// Génère un token CSRF et le stocke en session
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifie le token CSRF soumis par le formulaire
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Nettoie une valeur pour l'affichage HTML (protection XSS)
function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
