# Document d'Architecture Technique — Projet CYNA

**Formation** : CPI 2025–2026 — Bachelor Développeur Full Stack  
**Version** : 2.0  
**Date** : Avril 2026

---

## 1. Introduction et Périmètre

**Auteur : Omar Akakba**

Ce document présente l'architecture technique réalisée dans le cadre du projet fil rouge CYNA. Il couvre le passage du planifié (Rendu 1) au réalisé, en décrivant les choix techniques effectivement mis en place, les configurations réelles, les mesures de sécurité et les tests effectués.

**Périmètre du projet :**

CYNA est une plateforme e-commerce B2B permettant à des entreprises d'acheter des abonnements à des solutions de cybersécurité (EDR, SOC Managé, VPN). La plateforme inclut un espace client complet, un back-office d'administration et un système de paiement par abonnement via Stripe.

**Stack technique réalisée :**

| Composant | Technologie | Version | Responsable |
|-----------|-------------|---------|-------------|
| Back-End | PHP natif (sans framework) | 8.2 | Omar Akakba |
| Base de données | MySQL + PDO | 8.0 | Omar Akakba |
| Front-End | HTML5 / CSS3 / JavaScript | ES6 | Elyes Jaffel |
| Serveur web | Apache (via MAMP) | 2.4 | Omar Akakba |
| Paiement | Stripe Checkout + Webhooks | SDK PHP 15 | Omar Akakba |
| E-mail | PHPMailer + Gmail SMTP | 6.9 | Omar Akakba |
| Versioning | Git / GitHub | — | Équipe |

**Choix assumé** : aucun framework PHP n'a été utilisé. PHP natif avec PDO permet une maîtrise totale du code, une compréhension approfondie des mécanismes de sécurité, et répond entièrement aux besoins du projet.

---

## 2. BC3.1 : Installation et Configuration

**Auteur : Omar Akakba**

### Environnement de développement

L'environnement local repose sur **MAMP** (macOS), qui fournit Apache 2.4, PHP 8.2 et MySQL 8.0 en un seul package.

**Prérequis :**
- macOS avec MAMP installé ([mamp.info](https://www.mamp.info))
- Git 2.x
- Composer 2.x

**Configuration MAMP :**
- PHP : **8.2**
- Port Apache : **8888**
- Port MySQL : **8889**
- Document Root : `/Applications/MAMP/htdocs`

### Procédure d'installation

```bash
# 1. Cloner le projet
cd /Applications/MAMP/htdocs
git clone https://github.com/Omarakakba/CYNA-Project.git cyna
cd cyna

# 2. Installer les dépendances PHP (Stripe SDK + PHPMailer)
composer install

# 3. Configurer la connexion base de données
cp includes/config.example.php includes/config.php
# Éditer includes/config.php avec les paramètres MAMP
```

### Configuration de l'environnement (`includes/config.php`)

```php
define('DB_HOST',     '127.0.0.1');
define('DB_PORT',     '8889');
define('DB_NAME',     'cyna_db');
define('DB_USER',     'root');
define('DB_PASS',     'root');

define('STRIPE_PUBLIC_KEY',      'pk_test_...');
define('STRIPE_SECRET_KEY',      'sk_test_...');
define('STRIPE_WEBHOOK_SECRET',  'whsec_...');

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USER', 'votre@gmail.com');
define('MAIL_PASS', 'app_password');
```

> Ce fichier est exclu du versioning (`.gitignore`) — aucune clé sensible n'est exposée sur GitHub.

### Initialisation de la base de données

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot --port=8889 \
  -e "CREATE DATABASE IF NOT EXISTS cyna_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot --port=8889 cyna_db \
  < sql/schema.sql

/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot --port=8889 cyna_db \
  < sql/seed.sql
```

Le projet est ensuite accessible à : `http://localhost:8888/cyna/`

### Preuve

> Capture d'écran : MAMP démarré avec Apache et MySQL actifs, page d'accueil CYNA affichée dans le navigateur à `http://localhost:8888/cyna/`

---

## 3. BC3.2 : Gouvernance des SI

**Auteur : Omar Akakba**

### Gestion des accès et des rôles

La plateforme implémente un contrôle d'accès basé sur les rôles (RBAC) avec deux niveaux :

| Rôle | Description | Accès |
|------|-------------|-------|
| `user` | Client enregistré | Espace client, panier, commandes, profil, export RGPD |
| `admin` | Administrateur | Tout le site + back-office (`/admin/`) |

Chaque page protégée commence par une vérification de rôle :

```php
requireLogin();   // Redirige vers /connexion.php si non connecté
requireAdmin();   // Redirige vers /espace-client.php si non admin
```

### Gestion des sessions

- `session_start()` à chaque page nécessitant une session
- `session_regenerate_id(true)` à chaque connexion (protection contre la fixation de session)
- Cookie de session : `HttpOnly`, `SameSite=Strict`
- Option "Se souvenir de moi" : token SHA-256 stocké en base avec expiration 30 jours

### Traçabilité et journalisation

La table `rate_limit` assure une traçabilité minimale des actions sensibles par IP :

| Action | Limite | Fenêtre |
|--------|--------|---------|
| `login` | 5 tentatives | 5 minutes |
| `register` | 5 tentatives | 1 heure |
| `reset_password` | 3 tentatives | 10 minutes |

### Conformité RGPD

| Article | Droit | Implémentation |
|---------|-------|----------------|
| Art. 7 | Consentement | Checkbox CGU obligatoire à l'inscription, date et version stockées dans `user.cgu_accepted_at` |
| Art. 17 | Effacement | `supprimer-compte.php` : anonymisation des commandes (`user_id → NULL`), suppression des données personnelles |
| Art. 20 | Portabilité | `export-donnees.php` : export JSON téléchargeable (profil, commandes, adresses) |

### Gestion des clés et secrets

- Clés Stripe, SMTP et BDD dans `includes/config.php` (hors git)
- `.gitignore` exclut `config.php`, `vendor/`
- Aucune clé en dur dans le code source versionné

### Preuve

> Capture d'écran : page d'administration (`/admin/utilisateurs.php`) affichant la liste des utilisateurs avec leurs rôles — accessible uniquement après connexion admin

---

## 4. BC3.3 : Gestion de la Sécurité

**Auteur : Omar Akakba**

La sécurité a été traitée selon le référentiel **OWASP Top 10**.

### Tableau OWASP Top 10

| Risque | Mesure implémentée |
|--------|--------------------|
| A01 — Contrôle d'accès | `requireLogin()` / `requireAdmin()` sur chaque page protégée |
| A02 — Cryptographie | `password_hash()` bcrypt (coût 12), tokens SHA-256, HTTPS recommandé |
| A03 — Injection SQL | PDO + requêtes préparées sur toutes les requêtes, aucune concaténation |
| A04 — Conception | Séparation `includes/` (non accessible web), `.htaccess` restrictif |
| A05 — Configuration | Headers sécurité, `HttpOnly`, `SameSite=Strict`, `error_reporting(0)` en prod |
| A06 — Composants | Stripe SDK et PHPMailer à jour, `composer audit` disponible |
| A07 — Authentification | `session_regenerate_id()` à la connexion, remember-me token en base |
| A08 — Intégrité | Webhook Stripe avec vérification signature HMAC-SHA256 |
| A09 — Journalisation | Rate limiting en base par IP (`rate_limit` table) |
| A10 — CSRF | Token `bin2hex(random_bytes(32))` généré et vérifié sur tous les formulaires POST |

### Protection CSRF

```php
// Génération (dans le formulaire HTML)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Vérification (au traitement POST)
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    // Rejet — session expirée
}
```

### Protection XSS

```php
function escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
// Utilisée sur toutes les valeurs affichées dans le HTML
```

### Paiement sécurisé (Stripe)

- Aucune donnée de carte bancaire ne transite par le serveur
- Stripe Checkout : redirection vers une page hébergée par Stripe
- Webhook signé HMAC-SHA256 vérifié avec `\Stripe\Webhook::constructEvent()`

### Preuve

> Capture d'écran : token CSRF présent dans le formulaire de connexion (inspecteur navigateur — champ `input[name=csrf_token]`)

---

## 5. BC3.4 : Tests et Validation

**Auteur : Omar Akakba**

### Tableau des tests réalisés

| Type de test | Scénario | Résultat | Preuve |
|---|---|---|---|
| Test fonctionnel | Inscription avec validation CGU | Succès | Capture navigateur |
| Test fonctionnel | Connexion avec mauvais mot de passe | Succès — erreur affichée | Capture navigateur |
| Test fonctionnel | 6ème tentative de connexion (rate limiting) | Succès — accès bloqué 5 min | Capture navigateur |
| Test fonctionnel | Ajout produit au panier + commande | Succès — tunnel complet | Capture navigateur |
| Test paiement | Stripe Checkout (carte test 4242...) | Succès — statut `paid` | Stripe Dashboard |
| Test paiement | Webhook Stripe reçu et traité | Succès — BDD mise à jour | Log Apache |
| Test sécurité | Token CSRF modifié manuellement | Succès — requête rejetée | Capture navigateur |
| Test sécurité | Accès `/admin/` sans être admin | Succès — redirection | Capture navigateur |
| Test sécurité | Injection SQL dans champ de recherche | Succès — neutralisée par PDO | Test manuel |
| Test RGPD | Export données JSON (art. 20) | Succès — fichier JSON téléchargé | Capture navigateur |
| Test RGPD | Suppression de compte (art. 17) | Succès — `user_id = NULL` en BDD | phpMyAdmin |
| Test API | Collection Postman — routes principales | Succès — 200 OK | Collection Postman |

### Comptes de test

| Rôle | E-mail | Mot de passe |
|------|--------|-------------|
| Administrateur | admin@cyna-security.fr | Admin1234! |
| Client | client@test.fr | Admin1234! |

### Test Stripe (mode test)

| Carte | Résultat |
|-------|---------|
| 4242 4242 4242 4242 | Paiement accepté |
| 4000 0000 0000 0002 | Paiement refusé |

### Preuve

> Collection Postman disponible dans : [docs/postman_collection.json](postman_collection.json)

---

## 6. BC3.5 : Documentation Technique

**Auteur : Omar Akakba**

### Documentation du projet

| Document | Emplacement | Contenu |
|---------|-------------|---------|
| README | [README.md](../README.md) | Installation, stack, fonctionnalités, sécurité |
| DAT | [docs/DAT.md](DAT.md) | Architecture technique BC3 |
| Schéma BDD | [sql/schema.sql](../sql/schema.sql) | Définition des 10 tables |
| Données de test | [sql/seed.sql](../sql/seed.sql) | Produits, catégories, comptes |
| Collection API | [docs/postman_collection.json](postman_collection.json) | Routes testées via Postman |

### Routes principales de l'application

| Route | Méthode | Accès | Description |
|-------|---------|-------|-------------|
| `/cyna/` | GET | Public | Page d'accueil |
| `/cyna/catalogue.php` | GET | Public | Catalogue produits |
| `/cyna/connexion.php` | GET/POST | Public | Authentification |
| `/cyna/inscription.php` | GET/POST | Public | Création compte |
| `/cyna/panier.php` | GET | Connecté | Panier |
| `/cyna/commande.php` | GET/POST | Connecté | Tunnel d'achat |
| `/cyna/paiement.php` | POST | Connecté | Initiation Stripe |
| `/cyna/succes.php` | GET | Connecté | Confirmation paiement |
| `/cyna/espace-client.php` | GET | Connecté | Tableau de bord client |
| `/cyna/export-donnees.php` | GET | Connecté | Export RGPD JSON |
| `/cyna/admin/` | GET | Admin | Dashboard administration |
| `/cyna/admin/produits.php` | GET/POST | Admin | CRUD produits |
| `/cyna/admin/commandes.php` | GET/POST | Admin | Gestion commandes |
| `/cyna/admin/utilisateurs.php` | GET/POST | Admin | Gestion utilisateurs |
| `/cyna/stripe-webhook.php` | POST | Stripe | Webhook paiement |

### Preuve

> Capture d'écran : README du projet affiché sur GitHub avec le tableau OWASP et les fonctionnalités

---

## Annexes

- **Annexe A** — Schéma entité-relation (ERD) de la base de données
- **Annexe B** — Diagramme d'architecture applicative (Draw.io dans `groupe7_dev_dat`)
- **Annexe C** — Flux de paiement Stripe (étapes 1 à 5) → Section BC3.3
- **Annexe D** — Collection Postman → [docs/postman_collection.json](postman_collection.json)

---

*Document d'Architecture Technique — CYNA — Version 2.0 — Avril 2026*  
*Auteur : Omar Akakba — Back-End | Elyes Jaffel — Front-End*
