# Guide d'installation — Projet CYNA Security Platform

**Auteur : Omar Akakba**  
**Version : 1.2**  
**Date : Avril 2026**

---

## 1. Présentation du projet

La plateforme CYNA permet :

- Vente d'abonnements SaaS en cybersécurité (EDR, SOC Managé, VPN)
- Gestion des clients et de leurs commandes
- Paiement sécurisé via Stripe Checkout
- Espace d'administration complet
- Conformité RGPD (consentement, effacement, portabilité)

**Architecture technique :**

| Composant | Technologie | Version |
|-----------|-------------|---------|
| Back-End | PHP natif (sans framework) | 8.2 |
| Base de données | MySQL + PDO | 8.0 |
| Front-End | HTML5 / CSS3 / JavaScript | ES6 |
| Serveur web | Apache (via MAMP) | 2.4 |
| Paiement | Stripe Checkout + Webhooks | SDK PHP 15 |
| E-mail | PHPMailer + Gmail SMTP | 6.9 |
| Dépendances | Composer | 2.x |

---

## 2. Prérequis

**Système :**

- macOS (architecture ARM ou Intel)
- 4 Go RAM minimum
- 10 Go stockage disponible

**Logiciels :**

- MAMP (inclut Apache 2.4 + PHP 8.2 + MySQL 8.0)
- Composer 2.x
- Git 2.x

**Vérification :**

```bash
php -v
composer --version
git --version
mysql --version
```

**Comptes externes requis :**

- Compte Stripe (mode test gratuit) — [stripe.com](https://stripe.com)
- Compte Gmail avec mot de passe d'application activé

---

## 3. Clonage du projet

```bash
cd /Applications/MAMP/htdocs
git clone https://github.com/Omarakakba/CYNA-Project.git cyna
cd cyna
```

**Structure du projet :**

```
cyna/
├── index.php                  ← Page d'accueil
├── catalogue.php              ← Catalogue produits
├── connexion.php              ← Authentification
├── inscription.php            ← Création de compte
├── panier.php                 ← Panier
├── commande.php               ← Tunnel d'achat
├── paiement.php               ← Initiation Stripe
├── succes.php                 ← Confirmation paiement
├── espace-client.php          ← Tableau de bord client
├── export-donnees.php         ← Export RGPD (art. 20)
├── supprimer-compte.php       ← Suppression compte (art. 17)
├── webhook.php                ← Endpoint webhook Stripe
├── admin/                     ← Back-office (admin uniquement)
├── includes/                  ← Code PHP non accessible depuis le web
│   ├── config.php             ← Configuration (hors git)
│   ├── auth.php               ← Authentification et sessions
│   ├── security.php           ← CSRF, XSS, rate limiting
│   └── mail.php               ← Envoi d'e-mails PHPMailer
├── assets/                    ← CSS, JS, images
├── sql/
│   ├── schema.sql             ← Schéma de la base de données
│   └── seed.sql               ← Données de démonstration
├── guide-installation/        ← Ce guide
└── docs/                      ← DAT et collection Postman
```

---

## 4. Installation des dépendances PHP

```bash
cd /Applications/MAMP/htdocs/cyna
composer install
```

Dépendances installées :

- `stripe/stripe-php` — SDK officiel Stripe
- `phpmailer/phpmailer` — Envoi d'e-mails SMTP

---

## 5. Configuration de l'environnement

### 5.1 Démarrer MAMP

1. Ouvrir MAMP
2. Aller dans **Préférences** :
   - PHP : **8.2**
   - Port Apache : **8888**
   - Port MySQL : **8889**
   - Document Root : `/Applications/MAMP/htdocs`
3. Cliquer sur **Start** pour démarrer Apache et MySQL

### 5.2 Créer le fichier de configuration

```bash
cp includes/config.example.php includes/config.php
```

Éditer `includes/config.php` :

```php
<?php
// Base de données (MAMP)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '8889');
define('DB_NAME', 'cyna_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Stripe
define('STRIPE_PUBLIC_KEY',     'pk_test_...');
define('STRIPE_SECRET_KEY',     'sk_test_...');
define('STRIPE_WEBHOOK_SECRET', 'whsec_...');

// Gmail SMTP (PHPMailer)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'votre.adresse@gmail.com');
define('MAIL_PASS', 'xxxx xxxx xxxx xxxx');  // Mot de passe d'application Gmail
define('MAIL_FROM', 'votre.adresse@gmail.com');
define('MAIL_NAME', 'CYNA Security');
```

> `config.php` est dans `.gitignore` — aucune clé sensible n'est versionnée.

---

## 6. Configuration de la base de données

### Option A — phpMyAdmin (recommandé)

1. Ouvrir `http://localhost:8888/phpMyAdmin/` depuis MAMP
2. Créer une nouvelle base : nom `cyna_db`, interclassement `utf8mb4_unicode_ci`
3. Importer `sql/schema.sql` via l'onglet **Importer**
4. Importer `sql/seed.sql` de la même façon

### Option B — Ligne de commande

```bash
# Connexion MySQL MAMP
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot --port=8889

# Dans MySQL :
CREATE DATABASE cyna_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Import du schéma et des données
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot --port=8889 cyna_db < sql/schema.sql
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot --port=8889 cyna_db < sql/seed.sql
```

---

## 7. Configuration du serveur web (MAMP)

MAMP configure Apache automatiquement. Le projet est accessible directement à :

```
http://localhost:8888/cyna/
```

Vérifier que `AllowOverride All` est actif dans MAMP (nécessaire pour le `.htaccess`).

Si tu veux un domaine local personnalisé, éditer `/etc/hosts` :

```
127.0.0.1  cyna.local
```

---

## 8. Configuration Stripe

1. Créer un compte sur [dashboard.stripe.com](https://dashboard.stripe.com)
2. Passer en **mode test**
3. Récupérer les clés depuis **Développeurs > Clés API**
4. Pour les webhooks : **Développeurs > Webhooks > Ajouter un endpoint**
   - URL : `http://localhost:8888/cyna/webhook.php`
   - Événement à écouter : `checkout.session.completed`

---

## 9. Validation

### Test navigateur

Ouvrir `http://localhost:8888/cyna/` — la page d'accueil doit afficher le carousel et les produits.

### Test connectivité base de données

Vérifier dans phpMyAdmin que les tables suivantes sont présentes dans `cyna_db` :
`user`, `category`, `product`, `order`, `order_item`, `payment`, `address`, `contact_message`, `rate_limit`, `slide`

### Test curl

```bash
curl -I http://localhost:8888/cyna/
# Attendu : HTTP/1.1 200 OK
```

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|------|--------|-------------|
| Administrateur | admin@cyna-security.fr | Admin1234! |
| Client | client@test.fr | Admin1234! |

### Test paiement Stripe

Utiliser la carte test : `4242 4242 4242 4242` — date future — CVV : `123`

---

## 10. Gestion des conflits de version

**Problème : dépendances PHP incompatibles**

```bash
composer update
```

**Problème : version PHP incorrecte dans MAMP**

Vérifier dans Préférences MAMP que PHP 8.2 est sélectionné.

Utiliser `composer.lock` pour garantir la stabilité des versions entre développeurs.

---

## 11. Dépannage

| Problème | Solution |
|---------|---------|
| Page blanche | Activer `display_errors = 1` dans `config.php` (dev uniquement) |
| Connexion BDD échouée | Vérifier `DB_HOST = 127.0.0.1` et `DB_PORT = 8889` |
| Erreur 404 sur sous-pages | Vérifier la présence du `.htaccess` et `AllowOverride All` |
| E-mail non envoyé | Vérifier le mot de passe d'application Gmail (pas le mot de passe principal) |
| Webhook Stripe non reçu | Utiliser Stripe CLI pour tester en local |

**Logs :**

```bash
# Erreurs PHP
tail -f /Applications/MAMP/logs/php_error.log

# Accès Apache
tail -f /Applications/MAMP/logs/apache_access.log
```
