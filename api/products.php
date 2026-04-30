<?php
/**
 * CYNA REST API — Produits
 *
 * GET /api/products.php                          → liste tous les produits
 * GET /api/products.php?id=1                     → un produit par ID
 * GET /api/products.php?category_id=2            → produits d'une catégorie
 * GET /api/products.php?search=edr               → recherche par nom/description
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

// GET ?id=X — un seul produit
if (isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $db->prepare(
        'SELECT p.id, p.name, p.description, p.long_description, p.price,
                p.image_url, p.is_available,
                c.id AS category_id, c.name AS category_name
         FROM product p
         LEFT JOIN category c ON c.id = p.category_id
         WHERE p.id = ? AND p.is_available = 1'
    );
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Produit introuvable', 'code' => 404]);
        exit;
    }

    $product['price'] = (float)$product['price'];
    echo json_encode(['data' => $product]);
    exit;
}

// GET liste avec filtres optionnels
$where  = ['p.is_available = 1'];
$params = [];

if (!empty($_GET['category_id'])) {
    $where[]  = 'p.category_id = ?';
    $params[] = (int)$_GET['category_id'];
}

if (!empty($_GET['search'])) {
    $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
    $q        = '%' . $_GET['search'] . '%';
    $params[] = $q;
    $params[] = $q;
}

$sql  = 'SELECT p.id, p.name, p.description, p.price, p.image_url,
                c.id AS category_id, c.name AS category_name
         FROM product p
         LEFT JOIN category c ON c.id = p.category_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY p.name ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

foreach ($products as &$p) {
    $p['price'] = (float)$p['price'];
}

echo json_encode([
    'count' => count($products),
    'data'  => $products,
]);
