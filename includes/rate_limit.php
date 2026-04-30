<?php

/**
 * Rate limiting par IP + action
 * Bloque après $max_attempts tentatives dans $window_seconds secondes
 */
function checkRateLimit(string $action, int $max_attempts = 5, int $window_seconds = 300): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $db = getDB();

    // Nettoyer les anciennes entrées
    $db->prepare('DELETE FROM rate_limit WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)')
       ->execute([$window_seconds]);

    // Compter les tentatives récentes
    $stmt = $db->prepare('SELECT COUNT(*) FROM rate_limit WHERE ip = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)');
    $stmt->execute([$ip, $action, $window_seconds]);
    $count = (int)$stmt->fetchColumn();

    if ($count >= $max_attempts) {
        $retry = ceil($window_seconds / 60);
        http_response_code(429);
        // Réponse adaptée selon le contexte (API JSON ou page HTML)
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Trop de tentatives. Réessayez dans ' . $retry . ' min.', 'code' => 429]);
        } else {
            // Stocker l'erreur pour affichage dans le formulaire
            $GLOBALS['rate_limit_error'] = 'Trop de tentatives échouées. Veuillez patienter ' . $retry . ' minute(s) avant de réessayer.';
            return;
        }
        exit;
    }

    // Enregistrer cette tentative
    $db->prepare('INSERT INTO rate_limit (ip, action) VALUES (?, ?)')->execute([$ip, $action]);
}

function clearRateLimit(string $action): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    getDB()->prepare('DELETE FROM rate_limit WHERE ip = ? AND action = ?')->execute([$ip, $action]);
}
