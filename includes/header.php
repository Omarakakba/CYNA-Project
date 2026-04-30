<?php
// header.php — Navigation partagée
if (!isset($page_title)) $page_title = 'CYNA';

// Headers de sécurité HTTP (OWASP A5)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'");
header_remove('X-Powered-By');

// Assure que auth.php est chargé (pour les pages publiques qui ne l'incluent pas)
require_once __DIR__ . '/auth.php';
// Reconnecte via cookie "remember me" si la session a expiré
checkRememberCookie();

// Compte les articles dans le panier session
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $entry) {
        $cart_count += is_array($entry) ? (int)($entry['qty'] ?? 1) : (int)$entry;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?> — CYNA</title>
    <link rel="stylesheet" href="/cyna/assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="/cyna/assets/css/style.css?v=6">
</head>
<body class="<?= $extra_css ?? '' ?>">

<header>
    <div class="nav-inner">
        <a href="/cyna/" class="nav-logo">
            <div class="nav-logo-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span class="nav-logo-text">CY<span>NA</span></span>
        </a>
        <nav>
            <a href="/cyna/catalogue.php" <?= (basename($_SERVER['PHP_SELF']) === 'catalogue.php') ? 'class="nav-active"' : '' ?>>Catalogue</a>
            <a href="/cyna/catalogue.php?cat=1">EDR</a>
            <a href="/cyna/catalogue.php?cat=2">SOC</a>
            <a href="/cyna/catalogue.php?cat=3">VPN</a>
        </nav>

        <!-- Barre de recherche -->
        <form class="nav-search" action="/cyna/recherche.php" method="GET" role="search">
            <i class="fa-solid fa-magnifying-glass nav-search-icon"></i>
            <input type="search" name="q" placeholder="Rechercher une solution…"
                   value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   autocomplete="off">
        </form>

        <div class="nav-right">
            <!-- Panier visible pour tous (visiteurs et connectés) -->
            <a href="/cyna/panier.php" class="nav-icon-btn" title="Panier">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="nav-cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/cyna/espace-client.php" class="nav-cta">
                    <i class="fa-solid fa-user"></i> Mon compte
                </a>
            <?php else: ?>
                <a href="/cyna/connexion.php" class="nav-link-login">Connexion</a>
                <a href="/cyna/inscription.php" class="nav-cta">Démarrer</a>
            <?php endif; ?>
        </div>

        <!-- Burger menu mobile -->
        <button class="nav-burger" id="navBurger" aria-label="Menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Menu mobile déroulant -->
    <div class="nav-mobile" id="navMobile" aria-hidden="true">
        <form class="nav-search nav-search-mobile" action="/cyna/recherche.php" method="GET" role="search">
            <i class="fa-solid fa-magnifying-glass nav-search-icon"></i>
            <input type="search" name="q" placeholder="Rechercher…"
                   value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </form>
        <a href="/cyna/catalogue.php">Catalogue</a>
        <a href="/cyna/catalogue.php?cat=1">EDR</a>
        <a href="/cyna/catalogue.php?cat=2">SOC</a>
        <a href="/cyna/catalogue.php?cat=3">VPN</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/cyna/panier.php">Panier <?= $cart_count > 0 ? "($cart_count)" : '' ?></a>
            <a href="/cyna/espace-client.php">Mon compte</a>
            <a href="/cyna/logout.php">Déconnexion</a>
        <?php else: ?>
            <a href="/cyna/connexion.php">Connexion</a>
            <a href="/cyna/inscription.php">Démarrer</a>
        <?php endif; ?>
    </div>
</header>
