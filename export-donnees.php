<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

$db  = getDB();
$uid = $_SESSION['user_id'];

// Données utilisateur
$user = $db->prepare('SELECT id, email, first_name, last_name, role, cgu_accepted_at, cgu_version, created_at FROM user WHERE id = ?');
$user->execute([$uid]);
$user_data = $user->fetch();

// Commandes
$orders = $db->prepare('SELECT id, status, total, created_at FROM `order` WHERE user_id = ? ORDER BY created_at DESC');
$orders->execute([$uid]);
$orders_data = $orders->fetchAll();

// Articles commandés
$items = $db->prepare('
    SELECT oi.order_id, p.name AS product, oi.quantity, oi.price, oi.duration
    FROM order_item oi
    JOIN product p ON p.id = oi.product_id
    JOIN `order` o ON o.id = oi.order_id
    WHERE o.user_id = ?
');
$items->execute([$uid]);
$items_data = $items->fetchAll();

// Adresses
$addresses = $db->prepare('SELECT label, first_name, last_name, address1, address2, city, postal_code, country, phone FROM address WHERE user_id = ?');
$addresses->execute([$uid]);
$addresses_data = $addresses->fetchAll();

$export = [
    'export_date'  => date('Y-m-d H:i:s'),
    'rgpd_article' => 'Art. 20 — Droit à la portabilité des données',
    'utilisateur'  => $user_data,
    'commandes'    => $orders_data,
    'articles'     => $items_data,
    'adresses'     => $addresses_data,
];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="cyna-mes-donnees-' . date('Y-m-d') . '.json"');
header('Cache-Control: no-store');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
