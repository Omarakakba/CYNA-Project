# Document d'Architecture Technique — CYNA

**Projet** : Plateforme SaaS Cybersécurité  
**Auteur** : Omar Akakba  
**Version** : 1.0  
**Date** : Avril 2026  
**Formation** : CPI 2025–2026

---

## 1. Présentation du projet

CYNA est une plateforme e-commerce B2B permettant à des entreprises d'acheter des abonnements à des solutions de cybersécurité : EDR (Endpoint Detection & Response), SOC Managé et VPN d'entreprise. La plateforme intègre un espace client complet, un back-office d'administration et un système de paiement par abonnement via Stripe.

### Objectifs techniques

- Aucun framework : PHP natif uniquement (contrainte pédagogique)
- Sécurité OWASP Top 10 implémentée de bout en bout
- Conformité RGPD (consentement, effacement, portabilité)
- Paiement sécurisé via Stripe Checkout (aucune donnée carte côté serveur)
- Interface premium responsive avec animations CSS

---

## 2. Architecture générale

```
Navigateur client
       │  HTTPS
       ▼
  Apache 2.4 (MAMP/LAMP)
       │
       ├── PHP 8.2 (pages et logique métier)
       │       │
       │       ├── PDO ──► MySQL 8.0 (cyna_db)
       │       ├── PHPMailer ──► Gmail SMTP
       │       └── Stripe PHP SDK ──► API Stripe
       │
       └── Assets statiques (CSS, JS, images)

Stripe (externe)
       │  Webhook HTTPS signé
       ▼
  stripe-webhook.php
       │
       └── PDO ──► MySQL 8.0
```

### Pattern architectural

L'architecture suit le pattern **Front Controller sans MVC** :

- Chaque page PHP est autonome (contrôleur + vue dans le même fichier)
- La logique commune est extraite dans `includes/` (connexion BDD, auth, sécurité, mail)
- Pas de routeur centralisé — Apache résout les fichiers directement
- Pas de template engine — HTML écrit directement dans les fichiers PHP

---

## 3. Environnement technique

| Composant | Technologie | Version |
|-----------|-------------|---------|
| Serveur web | Apache | 2.4 |
| Langage back-end | PHP | 8.2 |
| Base de données | MySQL | 8.0 |
| Accès BDD | PDO (PHP Data Objects) | — |
| Paiement | Stripe Checkout + Webhooks | SDK PHP 15 |
| E-mail | PHPMailer + Gmail SMTP | 6.9 |
| Front-end | HTML5 / CSS3 / JavaScript ES6 | — |
| Icônes | Font Awesome 6 | 6.x |
| Gestionnaire de dépendances | Composer | 2.x |

### Dépendances Composer

```json
{
  "require": {
    "stripe/stripe-php": "^15",
    "phpmailer/phpmailer": "^6.9"
  }
}
```

---

## 4. Structure des fichiers

### Racine publique

Tous les fichiers `.php` à la racine sont accessibles directement par le navigateur. Ils constituent les pages de l'application.

| Fichier | Rôle |
|---------|------|
| `index.php` | Page d'accueil (carousel + produits phares + stats) |
| `catalogue.php` | Catalogue filtré par catégorie |
| `produit.php` | Fiche produit avec tarification mensuelle/annuelle |
| `panier.php` | Panier d'achat |
| `commande.php` | Tunnel d'achat (sélection adresse + durée) |
| `paiement.php` | Création session Stripe Checkout |
| `succes.php` | Confirmation post-paiement |
| `facture.php` | Génération de facture HTML imprimable |
| `connexion.php` | Authentification utilisateur |
| `inscription.php` | Création de compte + envoi e-mail de bienvenue |
| `mot-de-passe-oublie.php` | Demande de reset par e-mail |
| `reinitialiser-mdp.php` | Formulaire de nouveau mot de passe |
| `espace-client.php` | Tableau de bord client (commandes, abonnements) |
| `profil.php` | Modification des informations personnelles |
| `adresses.php` | CRUD carnet d'adresses |
| `contact.php` | Formulaire de contact |
| `export-donnees.php` | Export RGPD JSON (art. 20) |
| `supprimer-compte.php` | Suppression de compte RGPD (art. 17) |
| `logout.php` | Invalidation de session et cookie |
| `stripe-webhook.php` | Endpoint webhook Stripe (vérification signature HMAC) |

### Back-office (`admin/`)

Accessible uniquement aux utilisateurs avec `role = 'admin'`. Chaque page appelle `requireAdmin()` en début de fichier.

| Fichier | Rôle |
|---------|------|
| `admin/index.php` | Dashboard (CA total, commandes récentes, stats) |
| `admin/produits.php` | CRUD produits (nom, description, prix, catégorie, image, disponibilité) |
| `admin/carousel.php` | Gestion des slides d'accueil (CRUD + tri) |
| `admin/commandes.php` | Liste et changement de statut des commandes |
| `admin/utilisateurs.php` | Liste, promotion admin, suppression RGPD-compliant |
| `admin/messages.php` | Messagerie de contact (lecture, marquage, suppression) |

### Includes (`includes/`)

Protégé par `.htaccess` (accès direct interdit). Contient uniquement du code PHP, jamais accessible depuis le navigateur.

| Fichier | Rôle |
|---------|------|
| `config.php` | Connexion PDO MySQL, constantes Stripe et SMTP (hors git) |
| `auth.php` | `requireLogin()`, `requireAdmin()`, `login()`, `logout()`, remember-me |
| `security.php` | `escape()`, `generateCsrfToken()`, `verifyCsrfToken()`, rate limiting |
| `mail.php` | `sendWelcomeMail()`, `sendResetMail()`, `sendOrderConfirmation()` via PHPMailer |
| `header.php` | En-tête HTML commun (nav, CSS, meta) |
| `footer.php` | Pied de page + chatbot JavaScript |

---

## 5. Base de données

### Schéma entité-relation (simplifié)

```
user ──< order ──< order_item >── product >── category
 │                    │
 └──< address     └──< payment
```

### Tables

#### `user`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| email | VARCHAR(150) UNIQUE | Adresse e-mail (identifiant) |
| password | VARCHAR(255) | Hash bcrypt (`password_hash()`) |
| role | ENUM('user','admin') | Rôle applicatif |
| first_name | VARCHAR(100) | Prénom |
| last_name | VARCHAR(100) | Nom de famille |
| reset_token | VARCHAR(64) | Token SHA-256 (reset mdp + remember-me) |
| reset_token_exp | DATETIME | Expiration du token |
| cgu_accepted_at | DATETIME | Horodatage acceptation CGU (RGPD art. 7) |
| cgu_version | VARCHAR(10) | Version des CGU acceptées |
| created_at | DATETIME | Date d'inscription |

#### `category`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| name | VARCHAR(100) | Nom de la catégorie |
| slug | VARCHAR(100) UNIQUE | Identifiant URL |
| description | VARCHAR(300) | Description courte |
| image_url | VARCHAR(255) | Image de la catégorie |

#### `product`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| name | VARCHAR(200) | Nom du produit |
| description | TEXT | Description courte (listing) |
| long_description | TEXT | Description détaillée (fiche produit) |
| price | DECIMAL(10,2) | Prix mensuel HT |
| image / image_url | VARCHAR(255) | Image du produit |
| is_available | TINYINT(1) | Disponibilité (1 = actif) |
| category_id | INT FK | Référence vers `category` |
| created_at | DATETIME | Date de création |

#### `order`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| user_id | INT NULL FK | Référence vers `user` — NULL si compte supprimé (RGPD art. 17) |
| status | ENUM | pending / paid / shipped / cancelled |
| total | DECIMAL(10,2) | Montant total HT |
| created_at | DATETIME | Date de commande |

> `user_id` est nullable avec `ON DELETE SET NULL` pour permettre la suppression RGPD tout en conservant l'historique comptable.

#### `order_item`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| order_id | INT FK | Référence vers `order` |
| product_id | INT FK | Référence vers `product` |
| quantity | INT | Quantité |
| price | DECIMAL(10,2) | Prix unitaire au moment de l'achat |
| duration | VARCHAR(10) | 'monthly' ou 'annual' |

#### `payment`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| order_id | INT UNIQUE FK | Référence vers `order` (1:1) |
| stripe_id | VARCHAR(255) | Payment Intent ID ou Session ID Stripe |
| status | ENUM | pending / paid / failed |
| amount | DECIMAL(10,2) | Montant payé |
| paid_at | DATETIME | Horodatage du paiement |

#### `address`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| user_id | INT FK | Référence vers `user` (CASCADE DELETE) |
| label | VARCHAR(100) | Libellé de l'adresse |
| first_name / last_name | VARCHAR(100) | Nom du destinataire |
| company | VARCHAR(150) | Raison sociale |
| address1 / address2 | VARCHAR(255) | Lignes d'adresse |
| city | VARCHAR(100) | Ville |
| postal_code | VARCHAR(20) | Code postal |
| country | VARCHAR(100) | Pays |
| phone | VARCHAR(30) | Téléphone |
| is_default | TINYINT(1) | Adresse principale |

#### `contact_message`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| name / email | VARCHAR | Coordonnées de l'expéditeur |
| subject / message | TEXT | Contenu du message |
| is_read | TINYINT(1) | 0 = non lu, 1 = lu |
| created_at | DATETIME | Date d'envoi |

#### `rate_limit`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| ip | VARCHAR(45) | Adresse IP (IPv4 ou IPv6) |
| action | VARCHAR(50) | 'login' / 'register' / 'reset_password' |
| created_at | DATETIME | Horodatage de la tentative |

Index composite `(ip, action, created_at)` pour les requêtes COUNT(*) de rate limiting.

#### `slide`
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT AUTO_INCREMENT | Clé primaire |
| title / subtitle | VARCHAR | Titre et sous-titre du slide |
| link_url / link_label | VARCHAR | Lien et texte du bouton CTA |
| image_url | VARCHAR(255) | Image de fond (optionnel) |
| bg_color | VARCHAR(50) | Couleur de fond CSS |
| sort_order | INT | Ordre d'affichage |
| is_active | TINYINT(1) | 1 = affiché sur le site |

---

## 6. Sécurité

### Authentification et sessions

- Mot de passe hashé avec `password_hash($pwd, PASSWORD_BCRYPT)` (coût 12)
- Vérification avec `password_verify()` uniquement
- `session_regenerate_id(true)` à chaque connexion (protection fixation de session)
- Cookie de session : `HttpOnly`, `SameSite=Strict`, `Secure` en production
- Option « Se souvenir de moi » : token aléatoire SHA-256 stocké en base avec expiration 30 jours

### Protection CSRF

Chaque formulaire POST inclut un token CSRF :

```php
// Génération
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Vérification
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    // Rejet de la requête
}
```

### Rate Limiting

Les actions sensibles sont limitées par IP via la table `rate_limit` :

| Action | Limite | Fenêtre |
|--------|--------|---------|
| Connexion (`login`) | 5 tentatives | 5 minutes |
| Inscription (`register`) | 5 tentatives | 1 heure |
| Reset mot de passe | 3 tentatives | 10 minutes |

### Protection XSS

Toute valeur affichée dans le HTML passe par la fonction `escape()` :

```php
function escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

### Protection SQL Injection

Toutes les requêtes utilisent des requêtes préparées PDO avec paramètres liés. Aucune concaténation de variable dans les requêtes SQL.

### Webhook Stripe

La signature du webhook est vérifiée avec la clé secrète Stripe avant tout traitement :

```php
$event = \Stripe\Webhook::constructEvent(
    $payload, $sig_header, STRIPE_WEBHOOK_SECRET
);
```

### .htaccess

Le fichier `includes/.htaccess` (ou la config Apache) interdit tout accès direct aux fichiers de configuration et d'authentification.

---

## 7. Flux de paiement

```
1. Client ──► commande.php
   └── Sélectionne adresse et durée d'abonnement
   └── INSERT INTO order (status='pending')
   └── INSERT INTO order_item (...)

2. Client ──► paiement.php
   └── Stripe\Checkout\Session::create([...])
   └── Redirect vers URL Stripe hébergée

3. Stripe ──► Traitement du paiement
   └── Redirect vers succes.php?session_id=...  (paiement accepté)
   └── OU redirect vers commande.php             (paiement annulé)

4. Stripe ──► stripe-webhook.php (POST signé HMAC)
   └── Événement checkout.session.completed
   └── UPDATE order SET status='paid'
   └── INSERT INTO payment (status='paid', paid_at=NOW())

5. Client ──► succes.php
   └── Récapitulatif commande
   └── Lien vers facture.php?order_id=...
```

---

## 8. Envoi d'e-mails

PHPMailer est utilisé avec Gmail SMTP (port 587, STARTTLS) pour trois types d'e-mails :

| Événement | Fonction | Contenu |
|-----------|----------|---------|
| Inscription | `sendWelcomeMail()` | Bienvenue + récapitulatif CGU |
| Reset mot de passe | `sendResetMail()` | Lien sécurisé (TTL 1h) |
| Confirmation commande | `sendOrderConfirmation()` | Récapitulatif + lien facture |

Les e-mails sont envoyés en HTML avec fallback texte brut.

---

## 9. Front-end

### Design System v6

Le fichier `assets/css/style.css` (2159 lignes) implémente un système de design complet basé sur des variables CSS :

```css
:root {
    --primary:      #060c1b;   /* Fond principal */
    --surface:      #0d1929;   /* Cartes et surfaces */
    --accent:       #0ea5e9;   /* Bleu principal */
    --accent-light: #38bdf8;   /* Bleu clair */
    --purple:       #818cf8;   /* Violet secondaire */
    --text:         #e2e8f0;   /* Texte principal */
    --text-muted:   #64748b;   /* Texte secondaire */
}
```

### Animations CSS

| Animation | Utilisation |
|-----------|-------------|
| `pulse` | Orbe lumineux du hero |
| `float` | Éléments flottants |
| `shimmer` | Texte en surbrillance (highlight) |
| `slideIn` | Apparition des alertes |
| `fadeInScale` | Cartes d'authentification |
| `gridScroll` | Grille de points du hero |
| `glowPulse` | Halo des boutons CTA |

### Scroll Reveal

Les éléments avec `data-reveal` sont observés via IntersectionObserver et reçoivent la classe `.revealed` à l'entrée dans le viewport. Des délais de 1 à 6 permettent les animations en cascade.

### Chatbot

Assistant virtuel implémenté en JavaScript pur. Les réponses sont définies dans un objet `responses` avec correspondance par mots-clés. Interface conversationnelle avec suggestions de réponses rapides.

---

## 10. RGPD

| Article | Droit | Implémentation technique |
|---------|-------|--------------------------|
| Art. 7 | Consentement | Checkbox CGU obligatoire à l'inscription. Colonnes `cgu_accepted_at` et `cgu_version` dans `user`. |
| Art. 17 | Effacement | `supprimer-compte.php` : `UPDATE order SET user_id=NULL`, `DELETE FROM address`, `DELETE FROM user`. Les commandes sont conservées anonymisées (obligation comptable). |
| Art. 20 | Portabilité | `export-donnees.php` : export JSON téléchargeable contenant profil, commandes, articles commandés et adresses. |

---

## 11. Déploiement

### Local (développement)

- MAMP Pro : Apache port 8888, MySQL port 8889
- `http://localhost:8888/cyna/`
- Variables de connexion dans `includes/config.php` (hors git)

### Production (recommandations)

- Hébergement Apache avec PHP 8.2+
- SSL/TLS obligatoire (HTTPS) pour les cookies sécurisés et Stripe
- Fichier `includes/config.php` configuré avec les clés de production Stripe
- `error_reporting(0)` en production, logs dans un fichier non accessible au web
- Cron job pour nettoyage de la table `rate_limit` (suppression des entrées expirées)
- Webhook Stripe configuré sur `https://votredomaine.fr/stripe-webhook.php`

---

## 12. Tests

### Comptes de test

| Rôle | E-mail | Mot de passe |
|------|--------|-------------|
| Admin | admin@cyna-security.fr | Admin1234! |
| Client | client@test.fr | Admin1234! |

### Scénarios couverts

- Inscription et réception e-mail de bienvenue
- Connexion + déconnexion + remember-me (30 jours)
- Reset de mot de passe par e-mail
- Parcours d'achat complet (panier → commande → Stripe → confirmation)
- Paiement Stripe en mode test (carte 4242 4242 4242 4242)
- Export RGPD JSON
- Suppression de compte (vérification anonymisation commandes)
- Rate limiting (5 tentatives de connexion → blocage 5 min)
- CSRF (modification manuelle du token → rejet)
- Administration : CRUD produits, gestion carousel, utilisateurs, commandes

---

*Document d'Architecture Technique — CYNA — Version 1.0 — Avril 2026*
