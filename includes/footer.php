<?php // footer.php — Pied de page partagé ?>
<footer>
    <div class="footer-inner">
        <div class="footer-top">
            <div class="footer-brand">
                <span class="nav-logo-text" style="font-size:1.2rem;">CY<span>NA</span></span>
                <p>Solutions SaaS de cybersécurité pour les entreprises. EDR, SOC managé, VPN et conformité réglementaire.</p>
                <div class="footer-badges">
                    <span class="footer-badge">ISO 27001</span>
                    <span class="footer-badge">SOC 2</span>
                    <span class="footer-badge">RGPD</span>
                </div>
            </div>
            <div class="footer-col">
                <h4>Solutions</h4>
                <a href="/cyna/catalogue.php?cat=1">EDR / Endpoint</a>
                <a href="/cyna/catalogue.php?cat=2">SOC Managé</a>
                <a href="/cyna/catalogue.php?cat=3">VPN Entreprise</a>
                <a href="/cyna/catalogue.php">Toutes les solutions</a>
                <a href="/cyna/recherche.php">Recherche</a>
            </div>
            <div class="footer-col">
                <h4>Compte</h4>
                <a href="/cyna/connexion.php">Connexion</a>
                <a href="/cyna/inscription.php">Créer un compte</a>
                <a href="/cyna/espace-client.php">Espace client</a>
                <a href="/cyna/profil.php">Mon profil</a>
                <a href="/cyna/panier.php">Mon panier</a>
            </div>
            <div class="footer-col">
                <h4>Informations</h4>
                <a href="/cyna/contact.php">Contact</a>
                <a href="/cyna/cgu.php">CGU</a>
                <a href="/cyna/mentions-legales.php">Mentions légales</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> CYNA — Tous droits réservés</span>
            <span>
                Hébergement EU &bull; Données chiffrées &bull; 99.99% SLA
                &bull; <a href="/cyna/mentions-legales.php" style="color:rgba(255,255,255,0.4);">Mentions légales</a>
                &bull; <a href="/cyna/cgu.php" style="color:rgba(255,255,255,0.4);">CGU</a>
            </span>
        </div>
    </div>
</footer>
<!-- Chatbot FAQ -->
<div class="chatbot-btn" id="chatbotBtn" title="Aide / FAQ" role="button" aria-label="Ouvrir l'aide">
    <i class="fa-solid fa-message"></i>
    <span class="chatbot-badge" id="chatbotBadge">?</span>
</div>

<div class="chatbot-panel" id="chatbotPanel" aria-hidden="true">
    <div class="chatbot-header">
        <div class="chatbot-header-info">
            <div class="chatbot-avatar"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <div style="font-weight:700;font-size:0.9rem;">Assistant CYNA</div>
                <div style="font-size:0.72rem;color:rgba(255,255,255,0.6);">Réponse immédiate</div>
            </div>
        </div>
        <button class="chatbot-close" id="chatbotClose" aria-label="Fermer">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chatbot-msg bot">
            Bonjour ! Je suis l'assistant CYNA. Comment puis-je vous aider ?
        </div>
        <div class="chatbot-suggestions" id="chatbotSuggestions">
            <button data-q="prix">Tarifs et abonnements</button>
            <button data-q="paiement">Paiement sécurisé</button>
            <button data-q="edr">Qu'est-ce que l'EDR ?</button>
            <button data-q="soc">Qu'est-ce que le SOC ?</button>
            <button data-q="vpn">Qu'est-ce que le VPN ?</button>
            <button data-q="essai">Essai gratuit</button>
            <button data-q="resilier">Résilier un abonnement</button>
            <button data-q="contact">Nous contacter</button>
        </div>
    </div>
    <div class="chatbot-input-wrap">
        <input type="text" id="chatbotInput" placeholder="Posez votre question…" autocomplete="off">
        <button id="chatbotSend"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
</div>

<script src="/cyna/assets/js/main.js"></script>
<script src="/cyna/assets/js/chatbot.js"></script>
</body>
</html>
