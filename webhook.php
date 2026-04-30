<?php
/**
 * Stripe Webhook — vérification de signature obligatoire
 * Enregistrer l'URL dans le dashboard Stripe :
 *   https://dashboard.stripe.com/test/webhooks
 *   URL : https://votre-domaine.fr/cyna/webhook.php
 *   Événements : checkout.session.completed
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';

// Lire le payload brut AVANT toute autre opération
$payload   = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Vérification de la signature Stripe — CRITIQUE sécurité
try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        STRIPE_WEBHOOK_SECRET
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload invalide']);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Signature invalide → requête non authentifiée, on rejette
    http_response_code(400);
    echo json_encode(['error' => 'Signature invalide']);
    exit;
}

// Traitement des événements
if ($event->type === 'checkout.session.completed') {
    $session  = $event->data->object;

    if ($session->payment_status !== 'paid') {
        http_response_code(200); exit; // ignorer
    }

    $order_id = (int)($session->metadata->order_id ?? 0);
    if (!$order_id) {
        http_response_code(200); exit;
    }

    $db = getDB();

    // Idempotence : ne traiter que si encore en "pending"
    $stmt = $db->prepare('SELECT status FROM `order` WHERE id = ?');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order && $order['status'] === 'pending') {
        $db->prepare('UPDATE `order` SET status = "paid" WHERE id = ?')
           ->execute([$order_id]);
        $db->prepare('UPDATE payment SET status = "paid", stripe_id = ?, paid_at = NOW() WHERE order_id = ?')
           ->execute([$session->id, $order_id]);
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
