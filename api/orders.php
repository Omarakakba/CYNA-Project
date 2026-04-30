<?php
/**
 * CYNA REST API — Commandes (authentifié)
 *
 * Nécessite le header :  X-API-Key: <token>
 * Le token est l'email + ":" + mot de passe hashé stocké en session.
 * Pour simplifier la démo : on accepte les credentials en Basic Auth.
 *
 * GET /api/orders.php              → commandes de l'utilisateur connecté
 * GET /api/orders.php?id=1         → détail d'une commande
 *
 * Authentification HTTP Basic :
 *   Authorization: Basic base64(email:password)
 */

header('Content-Type: application/json; charset=utf-8');
// CORS — liste blanche explicite
$allowed_origins = ['http://localhost:8888', 'http://localhost', 'http://127.0.0.1'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:8888');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once __DIR__ . '/../includes/config.php';

$db = getDB();

// --- Authentification Basic ---
$auth_header = $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['PHP_AUTH_USER'] ?? null;

$user_id = null;

// Support PHP_AUTH_USER / PHP_AUTH_PW (Apache décode Basic automatiquement)
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    $email    = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION']) &&
          str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Basic ')) {
    $decoded  = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6));
    [$email, $password] = explode(':', $decoded, 2) + ['', ''];
} else {
    http_response_code(401);
    header('WWW-Authenticate: Basic realm="CYNA API"');
    echo json_encode(['error' => 'Authentification requise', 'code' => 401]);
    exit;
}

$stmt = $db->prepare('SELECT id, email, role FROM user WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Identifiants invalides', 'code' => 401]);
    exit;
}

// Vérifier le mot de passe
$stmt = $db->prepare('SELECT password FROM user WHERE id = ?');
$stmt->execute([$user['id']]);
$hash = $stmt->fetchColumn();

if (!password_verify($password, $hash)) {
    http_response_code(401);
    echo json_encode(['error' => 'Identifiants invalides', 'code' => 401]);
    exit;
}

$user_id = $user['id'];

// --- Détail d'une commande ---
if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];

    $stmt = $db->prepare(
        'SELECT o.id, o.status, o.total, o.created_at, p.stripe_id, p.paid_at
         FROM `order` o
         LEFT JOIN payment p ON p.order_id = o.id
         WHERE o.id = ? AND o.user_id = ?'
    );
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Commande introuvable', 'code' => 404]);
        exit;
    }

    $stmt = $db->prepare(
        'SELECT oi.quantity, oi.price, oi.duration, pr.name AS product_name
         FROM order_item oi
         JOIN product pr ON pr.id = oi.product_id
         WHERE oi.order_id = ?'
    );
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    foreach ($items as &$i) {
        $i['price'] = (float)$i['price'];
    }

    echo json_encode([
        'data'  => $order,
        'items' => $items,
    ]);
    exit;
}

// --- Liste des commandes ---
$stmt = $db->prepare(
    'SELECT o.id, o.status, o.total, o.created_at,
            COUNT(oi.id) AS nb_items, p.stripe_id
     FROM `order` o
     LEFT JOIN order_item oi ON oi.order_id = o.id
     LEFT JOIN payment p ON p.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC'
);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

foreach ($orders as &$o) {
    $o['total']    = (float)$o['total'];
    $o['nb_items'] = (int)$o['nb_items'];
}

echo json_encode([
    'user'  => ['id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']],
    'count' => count($orders),
    'data'  => $orders,
]);
