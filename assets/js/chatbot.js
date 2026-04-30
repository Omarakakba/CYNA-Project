// CYNA — Chatbot FAQ statique

const FAQ = {
    prix: "Nos abonnements débutent à partir de 9,99 € HT / mois. Chaque solution est disponible en formule mensuelle ou annuelle (2 mois offerts). Consultez notre catalogue pour voir les tarifs détaillés.",
    paiement: "Tous les paiements sont traités via Stripe, certifié PCI DSS. Vos données bancaires ne transitent jamais par nos serveurs. Nous acceptons Visa, Mastercard et American Express.",
    edr: "L'EDR (Endpoint Detection & Response) est une solution de cybersécurité qui surveille et protège en temps réel les postes de travail et serveurs. Elle détecte les comportements anormaux, isole les menaces et permet une réponse aux incidents automatisée.",
    soc: "Le SOC (Security Operations Center) managé est un service de surveillance 24/7 de votre infrastructure par des analystes certifiés. Notre équipe détecte les incidents en moins de 15 minutes et intervient immédiatement.",
    vpn: "Notre VPN entreprise garantit un accès sécurisé à vos ressources depuis n'importe où. Il utilise un chiffrement AES-256, une authentification multi-facteurs (MFA) intégrée et une architecture Zero Trust.",
    essai: "Nous proposons un essai de 14 jours sans carte bancaire requise. Contactez notre équipe commerciale via le formulaire de contact pour en bénéficier.",
    resilier: "Vous pouvez résilier votre abonnement à tout moment depuis votre espace client. La résiliation prend effet à la fin de la période en cours, sans frais supplémentaires.",
    contact: "Notre équipe est disponible par e-mail (contact@cyna-security.fr) et par téléphone (+33 1 23 45 67 89) du lundi au vendredi de 9h à 18h. Pour le support 24/7 : support@cyna-security.fr.",
    rgpd: "CYNA est entièrement conforme au RGPD. Vos données sont hébergées en Union Européenne. Vous disposez d'un droit d'accès, de rectification et d'effacement. Contactez dpo@cyna-security.fr.",
    installation: "Nos agents s'installent en moins de 5 minutes sur Windows, macOS et Linux. Aucune compétence réseau avancée n'est requise. Une documentation complète est fournie à l'activation.",
    sla: "CYNA garantit une disponibilité de 99,99% SLA. En cas d'incident, notre équipe intervient en moins de 15 minutes avec un rapport détaillé.",
};

const KEYWORDS = {
    prix: ['prix', 'tarif', 'coût', 'cout', 'abonnement', 'combien', 'cher', 'mensuel', 'annuel'],
    paiement: ['paiement', 'payer', 'carte', 'stripe', 'bancaire', 'facture', 'virement'],
    edr: ['edr', 'endpoint', 'endpoint detection', 'antivirus', 'malware'],
    soc: ['soc', 'security operations', 'surveillance', 'analyste', 'incident'],
    vpn: ['vpn', 'réseau', 'accès distant', 'télétravail', 'zero trust', 'mfa'],
    essai: ['essai', 'gratuit', 'tester', 'demo', 'démonstration', 'trial'],
    resilier: ['résilier', 'annuler', 'arrêter', 'résiliation', 'quitter'],
    contact: ['contact', 'téléphone', 'email', 'joindre', 'support', 'aide'],
    rgpd: ['rgpd', 'données', 'privacy', 'dpo', 'protection des données'],
    installation: ['install', 'déploiement', 'déployer', 'configurer', 'setup'],
    sla: ['sla', 'disponibilité', 'uptime', 'garantie'],
};

function findAnswer(input) {
    const lower = input.toLowerCase().trim();
    if (!lower) return null;
    for (const [key, words] of Object.entries(KEYWORDS)) {
        if (words.some(w => lower.includes(w))) return FAQ[key];
    }
    return null;
}

function addMsg(text, type) {
    const msgs = document.getElementById('chatbotMessages');
    const div  = document.createElement('div');
    div.className = 'chatbot-msg ' + type;
    div.textContent = text;
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
}

document.addEventListener('DOMContentLoaded', () => {
    const btn    = document.getElementById('chatbotBtn');
    const panel  = document.getElementById('chatbotPanel');
    const close  = document.getElementById('chatbotClose');
    const input  = document.getElementById('chatbotInput');
    const send   = document.getElementById('chatbotSend');
    const badge  = document.getElementById('chatbotBadge');
    const suggs  = document.getElementById('chatbotSuggestions');

    if (!btn) return;

    btn.addEventListener('click', () => {
        const isOpen = panel.classList.toggle('open');
        panel.setAttribute('aria-hidden', !isOpen);
        badge.style.display = 'none';
    });

    close.addEventListener('click', () => {
        panel.classList.remove('open');
        panel.setAttribute('aria-hidden', 'true');
    });

    function sendMsg() {
        const q = input.value.trim();
        if (!q) return;
        addMsg(q, 'user');
        input.value = '';
        if (suggs) suggs.style.display = 'none';

        setTimeout(() => {
            const ans = findAnswer(q);
            addMsg(ans || "Je n'ai pas trouvé de réponse précise. Contactez-nous à contact@cyna-security.fr ou au +33 1 23 45 67 89.", 'bot');
        }, 350);
    }

    send.addEventListener('click', sendMsg);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') sendMsg(); });

    // Suggestions rapides
    document.querySelectorAll('#chatbotSuggestions button').forEach(btn => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.q;
            addMsg(btn.textContent.replace(/^.{2} /, ''), 'user');
            if (suggs) suggs.style.display = 'none';
            setTimeout(() => {
                addMsg(FAQ[key] || "Désolé, je n'ai pas de réponse pour cela.", 'bot');
            }, 350);
        });
    });
});
