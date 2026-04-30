<?php
$page_title = 'Mentions légales';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
session_start();
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Mentions légales</h1>
        <p>Conformément à la loi n° 2004-575 du 21 juin 2004 pour la confiance dans l'économie numérique</p>
    </div>
</div>

<div class="page-content legal-page">
    <div class="legal-content" style="max-width:800px;">

        <section>
            <h2>Éditeur du site</h2>
            <table class="legal-table">
                <tr><td>Raison sociale</td><td>CYNA SAS</td></tr>
                <tr><td>Capital social</td><td>10 000 €</td></tr>
                <tr><td>Siège social</td><td>42 Avenue de la Cybersécurité, 75008 Paris, France</td></tr>
                <tr><td>RCS</td><td>Paris 123 456 789</td></tr>
                <tr><td>SIRET</td><td>123 456 789 00011</td></tr>
                <tr><td>N° TVA Intracommunautaire</td><td>FR 12 123456789</td></tr>
                <tr><td>Directeur de la publication</td><td>Omar Akakba</td></tr>
                <tr><td>E-mail</td><td>contact@cyna-security.fr</td></tr>
                <tr><td>Téléphone</td><td>+33 1 23 45 67 89</td></tr>
            </table>
        </section>

        <section>
            <h2>Hébergement</h2>
            <table class="legal-table">
                <tr><td>Hébergeur</td><td>OVHcloud SAS</td></tr>
                <tr><td>Adresse</td><td>2 rue Kellermann, 59100 Roubaix, France</td></tr>
                <tr><td>Téléphone</td><td>+33 9 72 10 10 07</td></tr>
            </table>
            <p>Les serveurs sont hébergés exclusivement en Union Européenne, conformément aux exigences RGPD.</p>
        </section>

        <section>
            <h2>Propriété intellectuelle</h2>
            <p>L'ensemble des contenus présents sur ce site (textes, images, logos, icônes, code source) est protégé par le droit d'auteur et appartient à CYNA SAS ou fait l'objet d'une autorisation d'utilisation.</p>
            <p>Toute reproduction totale ou partielle sans autorisation préalable écrite est interdite et constituerait une contrefaçon sanctionnée par les articles L.335-2 et suivants du Code de la Propriété Intellectuelle.</p>
        </section>

        <section>
            <h2>Protection des données personnelles</h2>
            <p>CYNA collecte et traite vos données personnelles conformément au RGPD (Règlement UE 2016/679) et à la loi n° 78-17 du 6 janvier 1978 relative à l'informatique, aux fichiers et aux libertés.</p>
            <p>Délégué à la Protection des Données (DPO) : <strong>dpo@cyna-security.fr</strong></p>
            <p>Vous disposez des droits suivants sur vos données :</p>
            <ul>
                <li><strong>Droit d'accès</strong> : obtenir une copie de vos données ;</li>
                <li><strong>Droit de rectification</strong> : corriger des données inexactes ;</li>
                <li><strong>Droit à l'effacement</strong> : supprimer vos données sous certaines conditions ;</li>
                <li><strong>Droit à la portabilité</strong> : recevoir vos données dans un format structuré ;</li>
                <li><strong>Droit d'opposition</strong> : s'opposer au traitement de vos données.</li>
            </ul>
            <p>Pour exercer ces droits, contactez-nous à <strong>dpo@cyna-security.fr</strong> ou par courrier à notre siège social. En cas de litige, vous pouvez saisir la <a href="https://www.cnil.fr" target="_blank" rel="noopener">CNIL</a>.</p>
        </section>

        <section>
            <h2>Cookies</h2>
            <p>Ce site utilise uniquement des cookies strictement nécessaires au fonctionnement du service (session utilisateur, jeton CSRF). Aucun cookie de traçage ou publicitaire n'est utilisé.</p>
        </section>

        <section>
            <h2>Liens hypertextes</h2>
            <p>CYNA décline toute responsabilité quant au contenu des sites tiers vers lesquels des liens peuvent être établis depuis cette plateforme.</p>
        </section>

        <section>
            <h2>Droit applicable</h2>
            <p>Les présentes mentions légales sont soumises au droit français. En cas de litige, le Tribunal de Commerce de Paris est compétent.</p>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
