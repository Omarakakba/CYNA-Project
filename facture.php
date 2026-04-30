<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare('
    SELECT o.*, u.email, u.first_name, u.last_name
    FROM `order` o
    JOIN user u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
');
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order || $order['status'] !== 'paid') {
    http_response_code(403);
    die('Facture disponible uniquement pour les commandes payées.');
}

$stmt2 = $db->prepare('
    SELECT oi.*, p.name AS product_name, c.name AS category_name
    FROM order_item oi
    JOIN product p ON oi.product_id = p.id
    JOIN category c ON p.category_id = c.id
    WHERE oi.order_id = ?
');
$stmt2->execute([$id]);
$items = $stmt2->fetchAll();

$stmt3 = $db->prepare('SELECT * FROM payment WHERE order_id = ?');
$stmt3->execute([$id]);
$payment = $stmt3->fetch();

$total_ht = $order['total'] / 1.20;
$tva      = $order['total'] - $total_ht;
$ref      = 'CYNA-' . date('Y') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
$date_facture = $payment && $payment['paid_at'] ? date('d/m/Y', strtotime($payment['paid_at'])) : date('d/m/Y', strtotime($order['created_at']));
$client_name  = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?: $order['email'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= $ref ?> — CYNA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f7fa; color: #1a1a2e; font-size: 14px; }

        /* Barre d'actions (masquée à l'impression) */
        .print-bar {
            background: #0d1117;
            color: #fff;
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .print-bar a { color: #60a5fa; text-decoration: none; font-size: 0.85rem; }
        .print-bar .btn-print {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 0.55rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .print-bar .btn-print:hover { background: #2563eb; }

        /* Facture */
        .invoice-wrap {
            max-width: 800px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* En-tête coloré */
        .invoice-header {
            background: linear-gradient(135deg, #0d1117 0%, #1a237e 100%);
            color: #fff;
            padding: 2.5rem 2.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
        }
        .invoice-logo { display: flex; align-items: center; gap: 0.75rem; }
        .invoice-logo-icon {
            width: 44px; height: 44px;
            background: rgba(59,130,246,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            color: #60a5fa;
        }
        .invoice-logo-text { font-size: 1.6rem; font-weight: 900; letter-spacing: 1px; }
        .invoice-logo-text span { color: #3b82f6; }
        .invoice-logo-sub { font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 2px; }
        .invoice-meta { text-align: right; }
        .invoice-meta .invoice-number { font-size: 1.1rem; font-weight: 700; color: #60a5fa; }
        .invoice-meta .invoice-date { font-size: 0.82rem; color: rgba(255,255,255,0.6); margin-top: 0.3rem; }
        .invoice-badge {
            display: inline-block;
            background: #22c55e;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            margin-top: 0.5rem;
            letter-spacing: 0.5px;
        }

        /* Corps */
        .invoice-body { padding: 2.5rem; }

        /* Parties émetteur / destinataire */
        .invoice-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }
        .party-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            margin-bottom: 0.6rem;
        }
        .party-name { font-weight: 700; font-size: 1rem; color: #1a1a2e; margin-bottom: 0.3rem; }
        .party-detail { font-size: 0.85rem; color: #6b7280; line-height: 1.6; }

        /* Tableau articles */
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .invoice-table thead tr { background: #f1f5f9; }
        .invoice-table th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }
        .invoice-table th:last-child, .invoice-table td:last-child { text-align: right; }
        .invoice-table tbody tr { border-bottom: 1px solid #f1f5f9; }
        .invoice-table tbody tr:last-child { border-bottom: none; }
        .invoice-table td { padding: 1rem; font-size: 0.88rem; color: #374151; }
        .invoice-table .item-name { font-weight: 600; color: #1a1a2e; }
        .invoice-table .item-cat {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            margin-top: 0.25rem;
        }
        .invoice-table .item-dur { font-size: 0.78rem; color: #3b82f6; margin-top: 0.2rem; }

        /* Totaux */
        .invoice-totals { display: flex; justify-content: flex-end; margin-bottom: 2.5rem; }
        .totals-box { width: 300px; }
        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.88rem;
            color: #6b7280;
            border-bottom: 1px solid #f1f5f9;
        }
        .total-line.grand-total {
            font-size: 1rem;
            font-weight: 800;
            color: #1a1a2e;
            border-bottom: none;
            padding-top: 0.75rem;
        }
        .total-line.grand-total span:last-child { color: #3b82f6; }

        /* Pied de facture */
        .invoice-footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 2rem;
        }
        .invoice-footer .legal { font-size: 0.75rem; color: #9ca3af; line-height: 1.6; }
        .invoice-footer .stripe-badge {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            color: #9ca3af;
        }

        /* CSS PRINT */
        @media print {
            body { background: #fff; font-size: 12px; }
            .print-bar { display: none !important; }
            .invoice-wrap {
                margin: 0;
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
            .invoice-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<!-- Barre d'actions (masquée à l'impression) -->
<div class="print-bar">
    <div>
        <a href="/cyna/commande-detail.php?id=<?= $id ?>">← Retour à la commande</a>
    </div>
    <button class="btn-print" onclick="window.print()">
        ⬇ Télécharger / Imprimer la facture
    </button>
</div>

<!-- Facture -->
<div class="invoice-wrap">

    <!-- En-tête -->
    <div class="invoice-header">
        <div>
            <div class="invoice-logo">
                <div class="invoice-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <div class="invoice-logo-text">CY<span>NA</span></div>
                    <div class="invoice-logo-sub">Solutions SaaS Cybersécurité</div>
                </div>
            </div>
        </div>
        <div class="invoice-meta">
            <div class="invoice-number"><?= $ref ?></div>
            <div class="invoice-date">Date : <?= $date_facture ?></div>
            <div><span class="invoice-badge">✓ PAYÉE</span></div>
        </div>
    </div>

    <!-- Corps -->
    <div class="invoice-body">

        <!-- Émetteur / Client -->
        <div class="invoice-parties">
            <div>
                <div class="party-label">De</div>
                <div class="party-name">CYNA Security SAS</div>
                <div class="party-detail">
                    42 Avenue de la Cybersécurité<br>
                    75008 Paris, France<br>
                    contact@cyna-security.fr<br>
                    SIRET : 123 456 789 00012<br>
                    TVA : FR12 123456789
                </div>
            </div>
            <div>
                <div class="party-label">Facturé à</div>
                <div class="party-name"><?= escape($client_name) ?></div>
                <div class="party-detail">
                    <?= escape($order['email']) ?><br>
                    Commande #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?><br>
                    Passée le <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                </div>
            </div>
        </div>

        <!-- Articles -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align:center;">Qté</th>
                    <th style="text-align:right;">Prix unitaire HT</th>
                    <th style="text-align:right;">Total HT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item):
                    $pu_ht   = ($item['price'] * $item['quantity']) / 1.20 / max(1, (int)$item['quantity']);
                    $tot_ht  = $pu_ht * $item['quantity'];
                    $durLabel = ($item['duration'] ?? 'monthly') === 'annual' ? 'Abonnement annuel' : 'Abonnement mensuel';
                ?>
                <tr>
                    <td>
                        <div class="item-name"><?= escape($item['product_name']) ?></div>
                        <span class="item-cat"><?= escape($item['category_name']) ?></span>
                        <div class="item-dur">↻ <?= $durLabel ?></div>
                    </td>
                    <td style="text-align:center;"><?= (int)$item['quantity'] ?></td>
                    <td style="text-align:right;"><?= number_format($pu_ht, 2) ?> €</td>
                    <td style="text-align:right;font-weight:600;"><?= number_format($tot_ht, 2) ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totaux -->
        <div class="invoice-totals">
            <div class="totals-box">
                <div class="total-line">
                    <span>Sous-total HT</span>
                    <span><?= number_format($total_ht, 2) ?> €</span>
                </div>
                <div class="total-line">
                    <span>TVA (20%)</span>
                    <span><?= number_format($tva, 2) ?> €</span>
                </div>
                <div class="total-line grand-total">
                    <span>Total TTC</span>
                    <span><?= number_format($order['total'], 2) ?> €</span>
                </div>
            </div>
        </div>

        <!-- Pied -->
        <div class="invoice-footer">
            <div class="legal">
                Facture générée automatiquement — CYNA Security SAS<br>
                Paiement sécurisé via Stripe. Cette facture fait foi de règlement.<br>
                En cas de question : contact@cyna-security.fr
            </div>
            <div class="stripe-badge">
                <i class="fa-solid fa-lock"></i> Paiement Stripe sécurisé
            </div>
        </div>

    </div>
</div>

</body>
</html>
