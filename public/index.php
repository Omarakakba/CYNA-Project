<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';

session_start();

$db = getDB();

// Récupérer les produits mis en avant (4 derniers)
$stmt = $db->query('SELECT p.*, c.name AS category_name FROM product p JOIN category c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 4');
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYNA — Solutions SaaS Cybersécurité</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="/">CYNA</a>
            <a href="/catalogue.php">Catalogue</a>
            <a href="/panier.php">Panier</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/espace-client.php">Mon compte</a>
                <a href="/logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="/connexion.php">Connexion</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <h1>Protégez votre entreprise avec CYNA</h1>
        <p>Solutions SaaS de cybersécurité : EDR, SOC, VPN et plus encore.</p>

        <section class="products">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <h2><?= escape($product['name']) ?></h2>
                    <p><?= escape($product['description']) ?></p>
                    <p class="price"><?= number_format($product['price'], 2) ?> € / mois</p>
                    <a href="/produit.php?id=<?= (int)$product['id'] ?>">Voir le produit</a>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 CYNA — Tous droits réservés</p>
    </footer>
    <script src="/assets/js/main.js"></script>
</body>
</html>
