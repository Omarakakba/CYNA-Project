# CYNA — Plateforme SaaS Cybersécurité

> Projet Fil Rouge CPI 2025–2026  
> **Développeur Back-End** : Omar Akakba | **Développeur Front-End** : Elyes Jaffel

Plateforme e-commerce B2B de vente de solutions de cybersécurité (EDR, SOC Managé, VPN).  
Développée en PHP natif sans framework, avec MySQL 8, Stripe et une architecture orientée sécurité (OWASP Top 10 + RGPD).

---

## Stack technique

| Couche | Technologie | Version |
|--------|-------------|---------|
| Back-End | PHP natif (sans framework) | 8.2 |
| Base de données | MySQL + PDO | 8.0 |
| Front-End | HTML5 / CSS3 / JavaScript ES6 | — |
| Serveur | Apache (MAMP / LAMP) | 2.4 |
| Paiement | Stripe Checkout + Webhooks | SDK PHP 15 |
| E-mail transactionnel | PHPMailer + Gmail SMTP | 6.9 |
| Versioning | Git / GitHub | — |

> Aucun framework imposé → aucun framework utilisé. Toute la logique est en PHP procédural pur avec PDO.

---

## Fonctionnalités

### Espace client
- Inscription avec validation des CGU (RGPD art. 7) et confirmation par e-mail
- Connexion sécurisée + option « Se souvenir de moi » (token SHA-256 en base)
- Réinitialisation de mot de passe par e-mail (token à usage unique, TTL 1 heure)
- Espace client : historique des commandes, carnet d'adresses, profil
- Export des données personnelles en JSON (RGPD art. 20 — droit à la portabilité)
- Suppression de compte avec anonymisation des commandes (RGPD art. 17)

### Catalogue et achat
- Catalogue filtré par catégorie (EDR, SOC Managé, VPN)
- Fiches produits avec tarification mensuelle et annuelle
- Panier dynamique (sessionStorage) avec mise à jour en temps réel
- Tunnel d'achat : sélection adresse, durée d'abonnement, récapitulatif
- Paiement Stripe Checkout (redirection sécurisée, pas de données carte côté serveur)
- Webhook Stripe signé pour confirmation de paiement et mise à jour commande
- Facture téléchargeable après paiement

### Administration
- Tableau de bord avec statistiques (CA, commandes, utilisateurs, messages)
- CRUD complet des produits (avec image)
- Gestion du carousel (slides d'accueil — tri, activation, suppression)
- Gestion des commandes avec changement de statut
- Gestion des utilisateurs : promotion admin, suppression RGPD-compliant
- Messagerie de contact avec marquage lu/non lu

### Chatbot intégré
- Assistant virtuel flottant répondant aux questions fréquentes sur les produits et l'abonnement
- Interface conversationnelle avec suggestions de réponses rapides

---

## Structure du projet

```
cyna/
├── index.php                  ← Accueil (carousel + produits phares)
├── catalogue.php              ← Liste des produits filtrée par catégorie
├── produit.php                ← Fiche produit détaillée
├── panier.php                 ← Panier d'achat
├── commande.php               ← Tunnel d'achat (adresse + durée)
├── paiement.php               ← Création session Stripe Checkout
├── succes.php                 ← Page de confirmation post-paiement
├── facture.php                ← Génération et téléchargement de facture
├── connexion.php              ← Authentification
├── inscription.php            ← Création de compte
├── mot-de-passe-oublie.php    ← Demande de réinitialisation
├── reinitialiser-mdp.php      ← Formulaire de nouveau mot de passe
├── espace-client.php          ← Tableau de bord client
├── profil.php                 ← Modification des informations personnelles
├── adresses.php               ← Carnet d'adresses
├── contact.php                ← Formulaire de contact
├── export-donnees.php         ← Export RGPD JSON (art. 20)
├── supprimer-compte.php       ← Suppression de compte (art. 17)
├── logout.php                 ← Déconnexion
├── stripe-webhook.php         ← Réception et traitement des webhooks Stripe
│
├── admin/
│   ├── index.php              ← Tableau de bord administration
│   ├── produits.php           ← CRUD produits
│   ├── carousel.php           ← Gestion des slides
│   ├── commandes.php          ← Gestion des commandes
│   ├── utilisateurs.php       ← Gestion des utilisateurs
│   └── messages.php           ← Messagerie de contact
│
├── includes/
│   ├── config.php             ← Connexion BDD PDO (hors git)
│   ├── auth.php               ← Authentification, sessions, remember-me
│   ├── security.php           ← CSRF, escape, rate limiting
│   ├── mail.php               ← Envoi d'e-mails PHPMailer
│   ├── header.php             ← En-tête HTML commun
│   └── footer.php             ← Pied de page + chatbot
│
├── assets/
│   ├── css/style.css          ← Design System v6 (2159 lignes)
│   ├── js/main.js             ← JavaScript ES6 (scroll reveal, chatbot, panier)
│   └── images/                ← Images produits et UI
│
├── sql/
│   ├── schema.sql             ← Schéma complet de la base de données
│   └── seed.sql               ← Données de démonstration
│
└── vendor/                    ← Dépendances Composer (hors git — voir composer.json)
```

---

## Installation locale

### Prérequis
- MAMP / XAMPP / LAMP avec PHP 8.2+ et MySQL 8.0+
- Composer
- Un compte Stripe (mode test)
- Un compte Gmail avec mot de passe d'application activé

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/Omarakakba/CYNA-Project.git
cd CYNA-Project

# 2. Installer les dépendances PHP
composer install

# 3. Créer la base de données
/chemin/vers/mysql -u root -p < sql/schema.sql
/chemin/vers/mysql -u root -p cyna_db < sql/seed.sql

# 4. Configurer l'application
cp includes/config.example.php includes/config.php
# Éditer includes/config.php et renseigner :
#   - Identifiants MySQL (host, port, user, password, dbname)
#   - Clé secrète Stripe (STRIPE_SECRET_KEY)
#   - Clé publique Stripe (STRIPE_PUBLIC_KEY)
#   - Secret Webhook Stripe (STRIPE_WEBHOOK_SECRET)
#   - Identifiants Gmail SMTP (MAIL_USER, MAIL_PASS)

# 5. Placer le projet dans le dossier web Apache
# Ex. MAMP : /Applications/MAMP/htdocs/cyna/

# 6. Ouvrir dans le navigateur
# http://localhost:8888/cyna/
```

### Comptes de démonstration (après import de seed.sql)

| Rôle | E-mail | Mot de passe |
|------|--------|-------------|
| Administrateur | admin@cyna-security.fr | Admin1234! |
| Client | client@test.fr | Admin1234! |

---

## Sécurité — OWASP Top 10

| Risque OWASP | Mesure implémentée |
|---|---|
| A01 — Contrôle d'accès défaillant | `requireLogin()` / `requireAdmin()` sur chaque page protégée |
| A02 — Défaillances cryptographiques | `password_hash()` bcrypt, HTTPS recommandé, tokens SHA-256 |
| A03 — Injection | PDO + requêtes préparées sur toutes les requêtes SQL |
| A04 — Conception non sécurisée | Séparation includes / public, `.htaccess` restrictif |
| A05 — Mauvaise configuration | Headers sécurité, session HTTPOnly, SameSite=Strict |
| A06 — Composants vulnérables | Stripe SDK et PHPMailer à jour, `composer audit` |
| A07 — Identification et authentification | `session_regenerate_id()`, remember-me token en base |
| A08 — Intégrité des données | Webhook Stripe avec vérification de signature HMAC |
| A09 — Journalisation insuffisante | Rate limiting en base (login 5/5min, register 5/h, reset 3/10min) |
| A10 — Falsification de requête (CSRF) | Token CSRF généré et vérifié sur tous les formulaires POST |

---

## Conformité RGPD

| Article | Droit | Implémentation |
|---------|-------|----------------|
| Art. 7 | Consentement | CGU cochées à l'inscription, date et version stockées en base |
| Art. 13 | Information | Mentions légales et politique de confidentialité accessibles |
| Art. 17 | Droit à l'effacement | `supprimer-compte.php` — anonymisation des commandes (user_id → NULL), suppression des données personnelles |
| Art. 20 | Portabilité | `export-donnees.php` — export JSON complet (profil, commandes, adresses) |

---

## Paiement Stripe

Le paiement suit le flux Stripe Checkout :

1. Client valide son panier → `commande.php` crée la commande en base (statut `pending`)
2. `paiement.php` crée une session Stripe Checkout et redirige
3. Stripe traite le paiement et envoie un webhook signé à `stripe-webhook.php`
4. Le webhook met à jour la commande (`paid`) et le paiement en base
5. Client est redirigé vers `succes.php` avec récapitulatif et lien facture

Aucune donnée de carte bancaire ne transite par le serveur.

---

## Base de données

Le schéma complet est dans [sql/schema.sql](sql/schema.sql).  
Les données de démonstration sont dans [sql/seed.sql](sql/seed.sql).

Tables : `user`, `category`, `product`, `order`, `order_item`, `payment`, `address`, `contact_message`, `rate_limit`, `slide`

---

## Documentation technique

Le Document d'Architecture Technique complet est disponible dans [docs/DAT.md](docs/DAT.md).
