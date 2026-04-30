<?php
$page_title = 'Conditions Générales d\'Utilisation';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
session_start();
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Conditions Générales d'Utilisation</h1>
        <p>Dernière mise à jour : <?= date('d/m/Y') ?></p>
    </div>
</div>

<div class="page-content legal-page">

    <div class="legal-nav">
        <a href="#article1">1. Objet</a>
        <a href="#article2">2. Accès au service</a>
        <a href="#article3">3. Compte utilisateur</a>
        <a href="#article4">4. Abonnements et paiement</a>
        <a href="#article5">5. Protection des données</a>
        <a href="#article6">6. Responsabilité</a>
        <a href="#article7">7. Propriété intellectuelle</a>
        <a href="#article8">8. Résiliation</a>
        <a href="#article9">9. Droit applicable</a>
    </div>

    <div class="legal-content">

        <section id="article1">
            <h2>Article 1 — Objet</h2>
            <p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation de la plateforme CYNA, éditée par la société CYNA SAS, au capital de 10 000 €, dont le siège social est situé au 42 Avenue de la Cybersécurité, 75008 Paris, immatriculée au RCS de Paris sous le numéro 123 456 789.</p>
            <p>CYNA est une plateforme SaaS proposant des solutions de cybersécurité à destination des entreprises : détection et réponse sur les endpoints (EDR), Centre d'Opérations de Sécurité managé (SOC), et réseau privé virtuel d'entreprise (VPN).</p>
        </section>

        <section id="article2">
            <h2>Article 2 — Accès au service</h2>
            <p>L'accès à la plateforme CYNA est réservé aux personnes morales (entreprises, associations, organisations) et aux professionnels. Toute personne physique agissant en dehors de son activité professionnelle est considérée comme un consommateur au sens du Code de la consommation.</p>
            <p>L'utilisation des services requiert :</p>
            <ul>
                <li>La création d'un compte utilisateur valide ;</li>
                <li>L'acceptation des présentes CGU ;</li>
                <li>Le paiement des abonnements choisis.</li>
            </ul>
            <p>CYNA se réserve le droit de refuser l'accès à ses services à toute personne ne respectant pas les présentes conditions.</p>
        </section>

        <section id="article3">
            <h2>Article 3 — Compte utilisateur</h2>
            <p>L'utilisateur s'engage à fournir des informations exactes, complètes et à jour lors de la création de son compte. Il est responsable de la confidentialité de ses identifiants de connexion.</p>
            <p>En cas de compromission du compte, l'utilisateur doit immédiatement en informer CYNA à l'adresse <strong>security@cyna-security.fr</strong>.</p>
            <p>Chaque compte est strictement personnel et ne peut être partagé entre plusieurs utilisateurs.</p>
        </section>

        <section id="article4">
            <h2>Article 4 — Abonnements et paiement</h2>
            <p>Les services CYNA sont proposés sous forme d'abonnements mensuels ou annuels. Les tarifs sont indiqués hors taxes (HT) sur la plateforme. La TVA applicable est celle en vigueur en France au taux de 20%.</p>
            <p>Le paiement est effectué par carte bancaire via notre prestataire de paiement sécurisé Stripe. CYNA ne stocke aucune donnée bancaire.</p>
            <p>Les abonnements sont renouvelés automatiquement à leur échéance. L'utilisateur peut résilier à tout moment depuis son espace client, avec effet à la fin de la période en cours.</p>
        </section>

        <section id="article5">
            <h2>Article 5 — Protection des données personnelles</h2>
            <p>CYNA collecte et traite les données personnelles des utilisateurs conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.</p>
            <p>Les données collectées sont utilisées uniquement pour :</p>
            <ul>
                <li>La gestion des comptes et des abonnements ;</li>
                <li>La fourniture des services contractuels ;</li>
                <li>L'envoi de communications relatives au service ;</li>
                <li>Le respect des obligations légales.</li>
            </ul>
            <p>Conformément au RGPD, vous disposez d'un droit d'accès, de rectification, d'effacement et de portabilité de vos données. Pour exercer ces droits, contactez notre DPO à <strong>dpo@cyna-security.fr</strong>.</p>
        </section>

        <section id="article6">
            <h2>Article 6 — Responsabilité</h2>
            <p>CYNA s'engage à mettre en œuvre tous les moyens raisonnables pour assurer la disponibilité et la sécurité de sa plateforme. Toutefois, CYNA ne saurait être tenue responsable :</p>
            <ul>
                <li>Des interruptions de service dues à des maintenances planifiées ou des incidents techniques indépendants de sa volonté ;</li>
                <li>Des dommages indirects résultant de l'utilisation ou de l'impossibilité d'utiliser les services ;</li>
                <li>Des attaques informatiques malgré les mesures de sécurité mises en place.</li>
            </ul>
            <p>La responsabilité de CYNA est limitée au montant des sommes effectivement versées par l'utilisateur au cours des 12 derniers mois.</p>
        </section>

        <section id="article7">
            <h2>Article 7 — Propriété intellectuelle</h2>
            <p>L'ensemble des éléments constituant la plateforme CYNA (marque, logo, design, code source, contenus) est la propriété exclusive de CYNA SAS et est protégé par le droit français et international de la propriété intellectuelle.</p>
            <p>Toute reproduction, représentation, modification ou exploitation non autorisée est strictement interdite.</p>
        </section>

        <section id="article8">
            <h2>Article 8 — Résiliation</h2>
            <p>L'utilisateur peut résilier son abonnement à tout moment depuis son espace client. La résiliation prend effet à la fin de la période d'abonnement en cours.</p>
            <p>CYNA se réserve le droit de suspendre ou résilier un compte en cas de non-respect des présentes CGU, sans remboursement des sommes versées.</p>
        </section>

        <section id="article9">
            <h2>Article 9 — Droit applicable et juridiction compétente</h2>
            <p>Les présentes CGU sont soumises au droit français. En cas de litige, les parties s'engagent à rechercher une solution amiable. À défaut, le Tribunal de Commerce de Paris sera seul compétent.</p>
        </section>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
