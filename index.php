<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
session_start();

$db = getDB();
$stmt = $db->query(
    'SELECT p.*, c.name AS category_name,
            COUNT(oi.id) AS nb_orders
     FROM product p
     JOIN category c ON p.category_id = c.id
     LEFT JOIN order_item oi ON oi.product_id = p.id
     GROUP BY p.id
     ORDER BY p.is_available DESC, nb_orders DESC, p.id DESC
     LIMIT 6'
);
$products = $stmt->fetchAll();

$slides = $db->query('SELECT * FROM slide WHERE is_active=1 ORDER BY sort_order ASC, id ASC')->fetchAll();

$page_title = 'Solutions SaaS Cybersécurité';
require_once __DIR__ . '/includes/header.php';
?>

<!-- CAROUSEL -->
<?php if (!empty($slides)): ?>
<section class="hero-carousel" id="carousel" aria-label="Actualités CYNA">
    <div class="carousel-track" id="carouselTrack">
        <?php foreach ($slides as $i => $slide): ?>
        <div class="carousel-slide <?= $i === 0 ? 'active' : '' ?>"
             style="background: <?= escape($slide['bg_color']) ?>; <?= $slide['image_url'] ? 'background-image:url(' . escape($slide['image_url']) . ');background-size:cover;background-position:center;' : '' ?>">
            <div class="carousel-overlay"></div>
            <div class="carousel-content">
                <div class="hero-badge" style="margin-bottom:1rem;">
                    <span class="dot"></span> Solution CYNA
                </div>
                <h2 class="carousel-title"><?= escape($slide['title']) ?></h2>
                <?php if ($slide['subtitle']): ?>
                    <p class="carousel-subtitle"><?= escape($slide['subtitle']) ?></p>
                <?php endif; ?>
                <?php if ($slide['link_url']): ?>
                    <a href="<?= escape($slide['link_url']) ?>" class="btn btn-primary" style="margin-top:1.5rem;">
                        <?= escape($slide['link_label'] ?: 'Découvrir') ?> <i class="fa-solid fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($slides) > 1): ?>
    <button class="carousel-btn carousel-prev" id="carouselPrev" aria-label="Slide précédent">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <button class="carousel-btn carousel-next" id="carouselNext" aria-label="Slide suivant">
        <i class="fa-solid fa-chevron-right"></i>
    </button>
    <div class="carousel-dots" id="carouselDots">
        <?php foreach ($slides as $i => $slide): ?>
            <button class="carousel-dot <?= $i === 0 ? 'active' : '' ?>"
                    data-index="<?= $i ?>" aria-label="Slide <?= $i+1 ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-grid">
        <div class="hero-content">
            <div class="hero-badge" data-reveal>
                <span class="dot"></span>
                Plateforme certifiée SOC 2 Type II
            </div>
            <h1 data-reveal data-reveal-delay="1">
                Sécurisez votre<br>
                entreprise avec<br>
                <span class="highlight">CYNA</span>
            </h1>
            <p data-reveal data-reveal-delay="2">
                Des solutions SaaS de cybersécurité de pointe — EDR, SOC managé, VPN d'entreprise. Protection en temps réel pour les organisations exigeantes.
            </p>
            <div class="hero-actions" data-reveal data-reveal-delay="3">
                <a href="/cyna/catalogue.php" class="btn btn-primary btn-lg">
                    Voir les solutions <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="#features" class="btn btn-outline btn-lg">En savoir plus</a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-visual-glow"></div>
            <div class="hero-card" data-reveal data-reveal-delay="2">
                <div class="hero-card-icon green"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="hero-card-label">Statut système</div>
                    <div class="hero-card-value">Tous les services opérationnels</div>
                </div>
            </div>
            <div class="hero-card" data-reveal data-reveal-delay="3">
                <div class="hero-card-icon blue"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <div class="hero-card-label">Menaces bloquées (24h)</div>
                    <div class="hero-card-value">14 832 tentatives neutralisées</div>
                </div>
            </div>
            <div class="hero-card" data-reveal data-reveal-delay="4">
                <div class="hero-card-icon cyan"><i class="fa-solid fa-lock"></i></div>
                <div>
                    <div class="hero-card-label">Disponibilité SLA</div>
                    <div class="hero-card-value">99.99% uptime garanti</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat-item">
            <span class="stat-number" data-count="2500" data-prefix="+" data-suffix="">+2 500</span>
            <div class="stat-label">Entreprises protégées</div>
        </div>
        <div class="stat-item">
            <span class="stat-number">99.99%</span>
            <div class="stat-label">Disponibilité SLA</div>
        </div>
        <div class="stat-item">
            <span class="stat-number">&lt; 1 min</span>
            <div class="stat-label">Temps de détection</div>
        </div>
        <div class="stat-item">
            <span class="stat-number">24/7</span>
            <div class="stat-label">Support SOC dédié</div>
        </div>
    </div>
</div>

<!-- FEATURES -->
<section class="section features-section" id="features">
    <div class="section-inner">
        <div class="section-header" data-reveal>
            <div class="section-tag">
                <i class="fa-solid fa-shield-halved"></i> Pourquoi CYNA
            </div>
            <h2 class="section-title">Une protection complète,<br>pensée pour les professionnels</h2>
            <p class="section-subtitle">Chaque solution est conçue pour répondre aux menaces actuelles avec une technologie de détection en temps réel.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card" data-reveal data-reveal-delay="1">
                <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h3>Détection en temps réel</h3>
                <p>Notre EDR analyse en continu les comportements suspects sur vos endpoints et bloque les menaces avant propagation.</p>
            </div>
            <div class="feature-card" data-reveal data-reveal-delay="2">
                <div class="feature-icon"><i class="fa-solid fa-eye"></i></div>
                <h3>SOC managé 24/7</h3>
                <p>Des analystes certifiés surveillent votre infrastructure en permanence. Alertes, rapports et réponse aux incidents inclus.</p>
            </div>
            <div class="feature-card" data-reveal data-reveal-delay="3">
                <div class="feature-icon"><i class="fa-solid fa-lock"></i></div>
                <h3>Chiffrement &amp; VPN</h3>
                <p>Sécurisez les accès distants avec un VPN d'entreprise chiffré de bout en bout et une gestion centralisée des accès.</p>
            </div>
            <div class="feature-card" data-reveal data-reveal-delay="4">
                <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
                <h3>Tableau de bord unifié</h3>
                <p>Visualisez l'état de sécurité de toute votre organisation depuis une interface unique, claire et actionnable.</p>
            </div>
            <div class="feature-card" data-reveal data-reveal-delay="5">
                <div class="feature-icon"><i class="fa-solid fa-bolt"></i></div>
                <h3>Déploiement rapide</h3>
                <p>Nos agents s'installent en quelques minutes sur Windows, macOS et Linux. Aucune compétence réseau avancée requise.</p>
            </div>
            <div class="feature-card" data-reveal data-reveal-delay="6">
                <div class="feature-icon"><i class="fa-solid fa-file-shield"></i></div>
                <h3>Conformité RGPD &amp; NIS2</h3>
                <p>Rapports de conformité automatisés, journalisation complète et audit trail pour les exigences réglementaires.</p>
            </div>
        </div>
    </div>
</section>

<!-- TOP PRODUITS -->
<section class="section products-section">
    <div class="section-inner">
        <div class="section-header" data-reveal>
            <div class="section-tag">
                <i class="fa-solid fa-fire" style="color:#ff6b35;"></i> Top produits
            </div>
            <h2 class="section-title">Les solutions les plus demandées</h2>
            <p class="section-subtitle">Nos abonnements phares, disponibles immédiatement et adoptés par des centaines d'entreprises.</p>
        </div>
        <div class="products-grid">
            <?php foreach ($products as $i => $product): ?>
            <div class="product-card" data-reveal data-reveal-delay="<?= min($i + 1, 6) ?>">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.6rem;">
                    <div class="product-badge"><?= escape($product['category_name']) ?></div>
                    <?php if ($product['is_available']): ?>
                        <span class="availability-badge available" style="font-size:0.7rem;padding:0.2rem 0.6rem;">
                            <i class="fa-solid fa-circle-check"></i> Disponible
                        </span>
                    <?php else: ?>
                        <span class="availability-badge unavailable" style="font-size:0.7rem;padding:0.2rem 0.6rem;">
                            <i class="fa-solid fa-clock"></i> Indisponible
                        </span>
                    <?php endif; ?>
                </div>
                <h2><?= escape($product['name']) ?></h2>
                <p><?= escape($product['description']) ?></p>
                <div class="product-footer">
                    <div class="price"><?= number_format($product['price'], 2) ?> € <span>/ mois</span></div>
                    <a href="/cyna/produit.php?id=<?= (int)$product['id'] ?>" class="btn-card">
                        Détails <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center; margin-top:3rem;" data-reveal>
            <a href="/cyna/catalogue.php" class="btn btn-primary btn-lg">
                Voir toutes les solutions <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- TRUST -->
<section class="trust-section">
    <div style="max-width:860px; margin:0 auto;">
        <h2 data-reveal>La sécurité, c'est notre seul métier</h2>
        <p data-reveal data-reveal-delay="1">Rejoignez les entreprises qui font confiance à CYNA pour protéger leurs actifs numériques.</p>
        <div class="trust-badges" data-reveal data-reveal-delay="2">
            <div class="trust-badge"><i class="fa-solid fa-certificate"></i> ISO 27001</div>
            <div class="trust-badge"><i class="fa-solid fa-certificate"></i> SOC 2 Type II</div>
            <div class="trust-badge"><i class="fa-solid fa-scale-balanced"></i> Conforme RGPD</div>
            <div class="trust-badge"><i class="fa-solid fa-scale-balanced"></i> NIS 2</div>
            <div class="trust-badge"><i class="fa-solid fa-server"></i> Hébergement EU</div>
        </div>
        <div data-reveal data-reveal-delay="3">
            <a href="/cyna/catalogue.php" class="btn btn-primary btn-lg">
                Démarrer maintenant <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
