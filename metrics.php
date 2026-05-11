<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\RenderTextFormat;

$registry = new CollectorRegistry(new InMemory());
$db = getDB();

// --- Utilisateurs ---
$totalUsers = (int) $db->query("SELECT COUNT(*) FROM user")->fetchColumn();
$gauge = $registry->registerGauge('cyna', 'users_total', 'Nombre total d\'utilisateurs inscrits');
$gauge->set($totalUsers);

$adminCount = (int) $db->query("SELECT COUNT(*) FROM user WHERE role = 'admin'")->fetchColumn();
$gauge2 = $registry->registerGauge('cyna', 'users_admin_total', 'Nombre d\'administrateurs');
$gauge2->set($adminCount);

// --- Commandes ---
$orderStats = $db->query("SELECT status, COUNT(*) as cnt FROM `order` GROUP BY status")->fetchAll();
$orderGauge = $registry->registerGauge('cyna', 'orders_by_status', 'Commandes par statut', ['status']);
foreach ($orderStats as $row) {
    $orderGauge->set((int)$row['cnt'], [$row['status']]);
}

$totalRevenue = (float) $db->query("SELECT COALESCE(SUM(total), 0) FROM `order` WHERE status = 'paid'")->fetchColumn();
$revenueGauge = $registry->registerGauge('cyna', 'revenue_total_euros', 'Chiffre d\'affaires total (commandes payées)');
$revenueGauge->set($totalRevenue);

// --- Produits ---
$totalProducts = (int) $db->query("SELECT COUNT(*) FROM product WHERE is_available = 1")->fetchColumn();
$prodGauge = $registry->registerGauge('cyna', 'products_active_total', 'Nombre de produits actifs');
$prodGauge->set($totalProducts);

// --- Messages de contact ---
$unreadMessages = (int) $db->query("SELECT COUNT(*) FROM contact_message WHERE is_read = 0")->fetchColumn();
$msgGauge = $registry->registerGauge('cyna', 'contact_messages_unread', 'Messages de contact non lus');
$msgGauge->set($unreadMessages);

// --- Rate limiting (tentatives de connexion) ---
$loginAttempts = (int) $db->query(
    "SELECT COUNT(*) FROM rate_limit WHERE action = 'login' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
)->fetchColumn();
$rlGauge = $registry->registerGauge('cyna', 'login_attempts_last_5min', 'Tentatives de connexion sur les 5 dernières minutes');
$rlGauge->set($loginAttempts);

// --- Rendu au format texte Prometheus ---
header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
$renderer = new RenderTextFormat();
echo $renderer->render($registry->getMetricFamilySamples());
