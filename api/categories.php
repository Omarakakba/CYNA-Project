<?php
/**
 * CYNA REST API — Catégories
 *
 * GET /api/categories.php         → liste toutes les catégories
 * GET /api/categories.php?id=1    → une catégorie + ses produits
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

// GET ?id=X — une catégorie + ses produits
if (isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $db->prepare('SELECT * FROM category WHERE id = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    if (!$category) {
        http_response_code(404);
        echo json_encode(['error' => 'Catégorie introuvable', 'code' => 404]);
        exit;
    }

    $stmt = $db->prepare(
        'SELECT id, name, description, price, image_url
         FROM product WHERE category_id = ? AND is_available = 1 ORDER BY name'
    );
    $stmt->execute([$id]);
    $products = $stmt->fetchAll();

    foreach ($products as &$p) {
        $p['price'] = (float)$p['price'];
    }

    echo json_encode([
        'data'     => $category,
        'products' => $products,
        'count'    => count($products),
    ]);
    exit;
}

// GET liste toutes les catégories avec comptage produits
$categories = $db->query(
    'SELECT c.id, c.name, c.slug, c.description, c.image_url,
            COUNT(p.id) AS nb_products
     FROM category c
     LEFT JOIN product p ON p.category_id = c.id AND p.is_available = 1
     GROUP BY c.id
     ORDER BY c.name ASC'
)->fetchAll();

foreach ($categories as &$c) {
    $c['nb_products'] = (int)$c['nb_products'];
}

echo json_encode([
    'count' => count($categories),
    'data'  => $categories,
]);
