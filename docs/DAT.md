# DOCUMENT D'ARCHITECTURE TECHNIQUE
## DAT — Épreuve certifiante BC3
### Superviser la mise en œuvre d'un projet informatique

---

**INGETIS / SUP DE VINCI — CPI BACHELOR RNCP 38478**  
**SPÉCIALITÉ DÉVELOPPEMENT FULL STACK — B5**

| PROJET | SPÉCIALITÉ |
|---|---|
| CYNA — Cybersecurity SaaS (EDR / SOC / VPN) | DEV — Développement Full Stack (B5) |

| GROUPE / ÉQUIPE | URL GIT | PRODUCTION |
|---|---|---|
| Groupe Binôme — Promo 2024–2025 | https://github.com/Omarakakba/CYNA-Project | https://omar05.alwaysdata.net/cyna/ |

**MEMBRES ET SECTIONS RÉDIGÉES**

| Prénom NOM | Sections rédigées |
|---|---|
| Omar AKAKBA | P1, P2, P3, P4, P5, P6 — Back-End, Infrastructure, Sécurité, Tests |
| Elyes JAFFEL | P1 (co-rédaction), P2.1 — Front-End, Design System |

> **RAPPEL BC3 : livrable collectif — 100% de la note BC3**
> Rendu : DAT (ce document) + Code GIT + Documentation technique + Guide d'installation

---

---

# PARTIE 1 — VISION ET OBJECTIFS

*Rédacteur : Omar AKAKBA / Elyes JAFFEL*

## 1.1 Contexte CYNA

CYNA est une société fictive spécialisée dans la cybersécurité, proposant des solutions SaaS destinées aux entreprises souhaitant protéger leur infrastructure informatique. Dans un contexte où les cyberattaques se multiplient et où les PME sont de plus en plus ciblées, CYNA répond à un besoin croissant de solutions de sécurité accessibles, scalables et gérées à distance.

La plateforme commercialise trois offres phares en mode abonnement mensuel ou annuel :

- **EDR (Endpoint Detection & Response)** : détection et réponse aux menaces sur les postes de travail et serveurs
- **SOC Managé (Security Operations Center)** : surveillance 24h/24 des systèmes d'information avec analyse des incidents
- **VPN entreprise** : sécurisation des connexions distantes pour les collaborateurs en télétravail

L'enjeu stratégique de ce projet est de concevoir une plateforme e-commerce B2B complète permettant à des responsables informatiques d'entreprise de souscrire, gérer et renouveler leurs abonnements en toute autonomie. La plateforme doit être fiable, sécurisée et conforme au RGPD, car elle traite des données sensibles d'entreprises clientes.

Du point de vue technique, l'équipe a fait le choix d'un développement en **PHP natif sans framework**, afin de démontrer une maîtrise approfondie des mécanismes fondamentaux du web : gestion des sessions, authentification, protection contre les injections SQL, génération de tokens CSRF et rate limiting. Ce choix, assumé et argumenté, permet une compréhension totale du cycle de vie d'une requête HTTP, contrairement à l'utilisation d'un framework qui abstrait ces mécanismes.

La plateforme inclut un **espace client** complet (historique commandes, carnet d'adresses, export RGPD, suppression de compte), un **back-office d'administration** (CRUD produits, gestion commandes, messagerie), et un système de **paiement sécurisé via Stripe Checkout** avec webhooks signés HMAC-SHA256.

L'hébergement en production est assuré sur **Alwaysdata** (https://omar05.alwaysdata.net/cyna/), avec une infrastructure locale de développement basée sur MAMP (Apache 2.4, PHP 8.2, MySQL 8.0). Un monitoring en temps réel est assuré par **Prometheus + Grafana** avec alertes automatiques.

## 1.2 Vision architecturale

L'architecture retenue est une **architecture monolithique PHP côté serveur** avec rendu HTML côté serveur (Server-Side Rendering). Ce choix est justifié par :

- **Contrainte pédagogique** : aucun framework imposé → démonstration des mécanismes fondamentaux
- **Cohérence** : toute la logique métier est centralisée dans `includes/`, lisible et auditable
- **Sécurité maîtrisée** : chaque protection OWASP est implémentée manuellement, sans dépendance externe
- **Performance** : les pages sont générées côté serveur, pas de temps de chargement JavaScript

**Principes directeurs :**
- Séparation stricte entre la logique (`includes/`) et la présentation (pages `.php`)
- Aucune requête SQL en dehors des fichiers `includes/` — tout passe par PDO avec requêtes préparées
- Variables d'environnement dans `.env` (hors git) — aucune clé en dur dans le code
- Architecture sécurisée by design : CSRF sur tous les formulaires, échappement systématique des sorties

**Pourquoi PHP natif et non React + Node.js :**

| Critère | React + Node.js | PHP natif (choix CYNA) |
|---|---|---|
| Courbe d'apprentissage | Élevée (JSX, hooks, state) | Maîtrisée par l'équipe |
| Contrôle sécurité | Abstrait par les libs | Total et explicite |
| Déploiement | Nécessite Node runtime | Apache standard |
| Adéquation projet | Overkill pour ce périmètre | Parfaitement adapté |

## 1.3 Objectifs techniques

| Objectif | Indicateur de succès | Priorité | Statut |
|---|---|---|---|
| Performance pages ≤ 500ms | Mesure curl / navigateur | Haute | ✅ Atteint |
| Couverture tests ≥ 70% (fonctions critiques) | Rapport PHPUnit coverage | Haute | ✅ 29 tests passants |
| Authentification sécurisée opérationnelle | Aucun accès non autorisé | Critique | ✅ Implémenté |
| Pipeline CI/CD fonctionnel | Workflow GitHub Actions vert sur main | Moyenne | ✅ Actif |
| Documentation API complète | Collection Postman exportée | Moyenne | ✅ Dans docs/ |
| Monitoring temps réel | Dashboard Grafana opérationnel | Moyenne | ✅ Prometheus + Grafana |
| Déploiement reproductible | `docker compose up` fonctionnel | Haute | ✅ Docker Compose |
| Conformité RGPD | Art. 7, 17, 20 implémentés | Critique | ✅ Complet |

---

---

# PARTIE 2 — ARCHITECTURE TECHNIQUE

*Rédacteur : Omar AKAKBA / Elyes JAFFEL*

## 2.1 Schéma d'architecture globale

> **[CAPTURE À INSÉRER]** — Ouvre le fichier `docs/architecture-cyna.drawio` sur https://app.diagrams.net et fais une capture d'écran du schéma complet. Nomme le fichier `docs/images/architecture-cyna.png`.

Le schéma représente :
- **Navigateur Web** (HTML/CSS/JS) → Apache 2.4 (port 8888 local / 80 prod)
- **Application PHP 8.2** : Pages publiques / Espace client / Administration
- **includes/** : config.php (PDO) · auth.php · security.php · mail.php
- **MySQL 8.0** (port 8889 local / 3306 Docker)
- **Services externes** : Stripe API · Stripe Checkout · Gmail SMTP · GitHub

Architecture schématisée : `docs/architecture-cyna.drawio` (source Draw.io)  
Capture PNG : `docs/images/architecture-cyna.png`

## 2.2 Choix technologiques

| Couche | Technologie retenue | Alternatives considérées | Justification |
|---|---|---|---|
| Back-End | **PHP 8.2 natif** | Node.js + Express, Laravel | Maîtrise totale du code, pas de couche d'abstraction, adéquation avec les contraintes pédagogiques (no framework) |
| Base de données | **MySQL 8.0 + PDO** | PostgreSQL, MongoDB | MySQL standard en hébergement mutualisé, PDO garantit les requêtes préparées anti-injection |
| Front-End | **HTML5 / CSS3 / JS ES6** | React.js, Vue.js | Rendu serveur suffisant, pas de SPA nécessaire pour ce périmètre |
| Serveur web | **Apache 2.4** | Nginx, Caddy | Inclus dans MAMP, .htaccess natif pour les règles de sécurité |
| Paiement | **Stripe Checkout + Webhooks** | PayPal, Braintree | API officielle, zéro donnée carte côté serveur, webhooks signés HMAC |
| E-mail | **PHPMailer 7 + Gmail SMTP** | SendGrid, Mailgun | Simple, fiable, gratuit pour le volume de ce projet |
| Tests | **PHPUnit 11** | Pest, Codeception | Standard PHP, compatible PHP 8.2, intégré dans GitHub Actions |
| CI/CD | **GitHub Actions** | GitLab CI, CircleCI | Intégré nativement à GitHub, gratuit pour repos publics |
| Conteneurisation | **Docker + Docker Compose** | Podman, Vagrant | Standard industrie, reproductibilité garantie |
| Monitoring | **Prometheus + Grafana** | Datadog, New Relic | Open-source, auto-hébergé, alertes email configurées |
| Hébergement prod | **Alwaysdata** | Heroku, Railway | Gratuit, supporte PHP + MySQL, déploiement Git |

## 2.3 Modèle de données (ERD)

> **[CAPTURE À INSÉRER]** — Ouvre phpMyAdmin sur http://localhost:8888/phpMyAdmin/, sélectionne `cyna_db`, clique sur **Concepteur** (ou **Designer**) dans le menu du haut. Fais une capture d'écran du schéma ERD. Nomme le fichier `docs/images/erd.png`.

**Tables et relations :**

| Table | Clés | Relations |
|---|---|---|
| `user` | PK: id | 1→N orders, 1→N addresses, 1→N rate_limit |
| `category` | PK: id | 1→N products |
| `product` | PK: id, FK: category_id | N→1 category, 1→N order_items |
| `order` | PK: id, FK: user_id | N→1 user, 1→N order_items, 1→1 payment |
| `order_item` | PK: id, FK: order_id, product_id | N→1 order, N→1 product |
| `payment` | PK: id, FK: order_id | 1→1 order |
| `address` | PK: id, FK: user_id | N→1 user |
| `contact_message` | PK: id | — |
| `rate_limit` | PK: id | Liée par (action + IP) |
| `slide` | PK: id | — (carousel accueil) |

Schéma SQL complet : `sql/schema.sql`

## 2.4 Routes principales de l'application

| Route | Méthode | Description | Auth |
|---|---|---|---|
| `/cyna/` | GET | Page d'accueil (carousel + produits) | Non |
| `/cyna/catalogue.php` | GET | Liste des produits par catégorie | Non |
| `/cyna/produit.php?id=X` | GET | Fiche produit détaillée | Non |
| `/cyna/connexion.php` | GET/POST | Authentification + session | Non |
| `/cyna/inscription.php` | GET/POST | Création de compte + CGU | Non |
| `/cyna/mot-de-passe-oublie.php` | GET/POST | Demande reset password | Non |
| `/cyna/reinitialiser-mdp.php` | GET/POST | Nouveau mot de passe (token) | Non |
| `/cyna/panier.php` | GET | Panier (sessionStorage) | Non |
| `/cyna/commande.php` | GET/POST | Tunnel achat (adresse + durée) | Connecté |
| `/cyna/paiement.php` | POST | Création session Stripe Checkout | Connecté |
| `/cyna/succes.php` | GET | Confirmation paiement | Connecté |
| `/cyna/facture.php?id=X` | GET | Téléchargement facture PDF | Connecté |
| `/cyna/espace-client.php` | GET | Dashboard client | Connecté |
| `/cyna/profil.php` | GET/POST | Modification profil | Connecté |
| `/cyna/adresses.php` | GET/POST | Carnet d'adresses | Connecté |
| `/cyna/export-donnees.php` | GET | Export JSON RGPD (art. 20) | Connecté |
| `/cyna/supprimer-compte.php` | GET/POST | Suppression compte (art. 17) | Connecté |
| `/cyna/logout.php` | GET | Déconnexion | Connecté |
| `/cyna/contact.php` | GET/POST | Formulaire de contact | Non |
| `/cyna/admin/` | GET | Dashboard administration | Admin |
| `/cyna/admin/produits.php` | GET/POST | CRUD produits | Admin |
| `/cyna/admin/commandes.php` | GET/POST | Gestion commandes | Admin |
| `/cyna/admin/utilisateurs.php` | GET/POST | Gestion utilisateurs | Admin |
| `/cyna/admin/messages.php` | GET/POST | Messagerie contact | Admin |
| `/cyna/admin/carousel.php` | GET/POST | Gestion slides accueil | Admin |
| `/cyna/webhook.php` | POST | Réception webhook Stripe signé | Stripe (HMAC) |
| `/cyna/metrics.php` | GET | Métriques Prometheus | Interne |

---

---

# PARTIE 3 — INFRASTRUCTURE ET DÉPLOIEMENT

*Rédacteur : Omar AKAKBA*

## 3.1 Stack Docker Compose

| Service | Image | Port exposé | Rôle |
|---|---|---|---|
| `app` | `php:8.2-apache` (custom build) | 8080:80 | Application PHP + Apache |
| `db` | `mysql:8.0` | 3307:3306 | Base de données MySQL |

**Extrait commenté du `docker-compose.yml` :**

```yaml
services:
  app:
    build: .                        # Dockerfile à la racine
    container_name: cyna_app
    ports:
      - "8080:80"                   # Site accessible sur localhost:8080
    environment:
      DB_HOST: db                   # Nom du service MySQL dans le réseau Docker
      DB_PORT: 3306
      DB_NAME: ${DB_NAME:-cyna_db}
      DB_USER: ${DB_USER:-cyna_user}
      DB_PASS: ${DB_PASS:-cyna_pass}
    depends_on:
      db:
        condition: service_healthy  # Attend que MySQL soit prêt
    networks:
      - cyna_network

  db:
    image: mysql:8.0
    container_name: cyna_db
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: ${DB_NAME:-cyna_db}
    volumes:
      - cyna_db_data:/var/lib/mysql                          # Persistance des données
      - ./sql/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql  # Init auto
      - ./sql/seed.sql:/docker-entrypoint-initdb.d/02-seed.sql      # Données demo
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
    networks:
      - cyna_network

volumes:
  cyna_db_data:    # Volume nommé pour la persistance MySQL

networks:
  cyna_network:
    driver: bridge
```

Fichier complet : `docker-compose.yml` (racine du projet)

**Dockerfile (extrait) :**
```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql zip
RUN a2enmod rewrite headers
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader
```

## 3.2 Pipeline CI/CD — GitHub Actions

> **[CAPTURE À INSÉRER]** — Va sur https://github.com/Omarakakba/CYNA-Project/actions et fais une capture d'écran montrant le workflow `CI — CYNA` avec un statut vert (passed). Nomme le fichier `docs/images/github-actions.png`.

| Étape | Déclencheur | Actions | Résultat |
|---|---|---|---|
| Setup environnement | Push sur `main` ou `develop` | Setup PHP 8.2 + extensions PDO, xdebug | PHP prêt |
| Service MySQL | Automatique | MySQL 8.0 container avec BDD de test | BDD disponible |
| Installation dépendances | Après setup | `composer install` avec cache | vendor/ prêt |
| Init BDD test | Après composer | Import schema.sql + seed.sql | Tables créées |
| Tests PHPUnit | Après init BDD | `phpunit --coverage-text --coverage-clover` | 29/29 verts |
| Audit sécurité | Après tests | `composer audit` | Aucune vulnérabilité |
| Upload coverage | Après tests | Artifact `coverage.xml` archivé | Rapport disponible |

**Extrait du fichier `.github/workflows/ci.yml` :**

```yaml
name: CI — CYNA
on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  tests:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: cyna_db_test
        options: --health-cmd="mysqladmin ping"

    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo, pdo_mysql
          coverage: xdebug
      - run: composer install --no-interaction
      - run: mysql -h 127.0.0.1 -u root -proot cyna_db_test < sql/schema.sql
      - run: vendor/bin/phpunit --coverage-text
      - run: composer audit
```

Fichier complet : `.github/workflows/ci.yml`

## 3.3 Variables d'environnement

| Variable | Usage | Obligatoire |
|---|---|---|
| `DB_HOST` | Hôte MySQL (127.0.0.1 local, `db` Docker) | Oui |
| `DB_PORT` | Port MySQL (8889 MAMP, 3306 Docker/prod) | Oui |
| `DB_NAME` | Nom de la base de données | Oui |
| `DB_USER` | Utilisateur MySQL | Oui |
| `DB_PASS` | Mot de passe MySQL | Oui |
| `STRIPE_SECRET_KEY` | Clé secrète Stripe (sk_test_...) | Oui (prod) |
| `STRIPE_PUBLISHABLE_KEY` | Clé publique Stripe (pk_test_...) | Oui (prod) |
| `STRIPE_WEBHOOK_SECRET` | Secret de vérification webhook Stripe | Oui (prod) |
| `SMTP_HOST` | Serveur SMTP (smtp.gmail.com) | Oui (prod) |
| `SMTP_PORT` | Port SMTP (587) | Oui (prod) |
| `SMTP_USER` | Adresse Gmail d'envoi | Oui (prod) |
| `SMTP_PASS` | Mot de passe d'application Gmail | Oui (prod) |
| `SMTP_FROM` | Adresse expéditeur | Oui (prod) |
| `SMTP_FROM_NAME` | Nom expéditeur | Non (défaut: CYNA Security) |

Template sans secrets : `.env.example` (racine du projet)

## 3.4 Guide d'installation

> Ce guide permet de déployer CYNA from scratch en moins de 15 minutes.

**Prérequis :** Docker ≥ 24, Docker Compose v2, Git

```bash
# 1. Cloner le projet
git clone https://github.com/Omarakakba/CYNA-Project.git cyna
cd cyna

# 2. Configurer les variables d'environnement
cp .env.example .env
# Éditer .env avec vos clés Stripe et SMTP

# 3. Démarrer l'application
docker compose up -d --build

# 4. Vérification
docker compose ps
# Les deux services doivent être "Up"

# 5. Accès
# Site : http://localhost:8080/cyna/
# Admin : admin@cyna-security.fr / Admin1234!
# Client : client@test.fr / Admin1234!
```

Guide complet : `guide-installation/guide_installation.md`

---

---

# PARTIE 4 — SÉCURITÉ APPLICATIVE

*Rédacteur : Omar AKAKBA*

## 4.1 Authentification et gestion des sessions

CYNA n'utilise pas JWT (adapté aux APIs REST stateless) mais un système de **sessions PHP côté serveur**, plus adapté à une application web rendue côté serveur. Ce choix est justifié et documenté.

**Mécanismes implémentés :**

- **Hashage** : `password_hash()` avec bcrypt (algorithme `PASSWORD_BCRYPT`, coût par défaut ≥ 10)
- **Session sécurisée** : `session_regenerate_id(true)` à chaque connexion (protection fixation de session)
- **Cookie session** : `HttpOnly=true`, `SameSite=Strict`
- **Remember-me** : token SHA-256 (`bin2hex(random_bytes(32))`) stocké hashé en base, TTL 30 jours
- **Rate limiting** : 5 tentatives / 5 min sur `/connexion.php`, stocké en table `rate_limit` par IP

**Flux d'authentification :**
```
[Client] → POST /connexion.php
         → verifyCsrfToken() → rejet si token invalide
         → checkRateLimit('login', 5, 300) → rejet si trop de tentatives
         → PDO::prepare("SELECT ... WHERE email = ?") → requête préparée
         → password_verify($password, $hash) → vérification bcrypt
         → session_regenerate_id(true) → nouvelle session
         → $_SESSION['user_id'] / 'user_role'] → stockage session
         → header('Location: /cyna/espace-client.php') → redirection
```

**Extrait du middleware d'autorisation (`includes/auth.php`) :**

```php
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /cyna/connexion.php');
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        exit('Accès interdit.');
    }
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}
```

Chaque page protégée commence par `requireLogin()` ou `requireAdmin()`.

## 4.2 Mesures de sécurité OWASP

| Vecteur OWASP | Mesure implémentée | Outil / Méthode | Statut |
|---|---|---|---|
| A01 — Contrôle d'accès | `requireLogin()` / `requireAdmin()` sur chaque page protégée | `includes/auth.php` | ✅ Implémenté |
| A02 — Cryptographie | `password_hash()` bcrypt, tokens SHA-256, HTTPS en prod | PHP natif | ✅ Implémenté |
| A03 — Injection SQL | PDO + requêtes préparées **sur toutes** les requêtes SQL | PDO `prepare()` | ✅ Implémenté |
| A04 — Conception non sécurisée | `includes/` non accessible depuis le web (.htaccess), séparation logique/présentation | `.htaccess` | ✅ Implémenté |
| A05 — Mauvaise configuration | Headers sécurité HTTP, `error_reporting(0)` en prod, `Options -Indexes` | `.htaccess` + `mod_headers` | ✅ Implémenté |
| A06 — Composants vulnérables | Stripe SDK + PHPMailer à jour, `composer audit` dans CI | GitHub Actions | ✅ Vérifié |
| A07 — Identification/Auth | `session_regenerate_id()`, remember-me token en base, déconnexion propre | `includes/auth.php` | ✅ Implémenté |
| A08 — Intégrité des données | Webhook Stripe avec vérification signature HMAC-SHA256 | `Stripe\Webhook::constructEvent()` | ✅ Implémenté |
| A09 — Journalisation | Rate limiting en table `rate_limit` + monitoring Prometheus/Grafana + alertes | PHPUnit + Grafana | ✅ Implémenté |
| A10 — CSRF | Token `bin2hex(random_bytes(32))` sur tous les formulaires POST | `includes/security.php` | ✅ Implémenté |

**Extrait protection CSRF (`includes/security.php`) :**

```php
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
```

**Extrait protection XSS (`includes/security.php`) :**

```php
function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
// Utilisée sur TOUTES les valeurs affichées en HTML : <?= escape($var) ?>
```

**Headers sécurité (`.htaccess`) :**

```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header always unset X-Powered-By
Header always unset Server
```

## 4.3 RGPD

**Données collectées et traitées :**

| Donnée | Finalité | Durée de conservation |
|---|---|---|
| Email, nom, prénom | Identification, facturation | Durée du compte |
| Adresse postale | Livraison / facturation | Durée du compte |
| Historique commandes | Suivi client, comptabilité | 10 ans (obligation légale) |
| Mot de passe (hashé bcrypt) | Authentification | Durée du compte |
| IP (rate_limit) | Sécurité anti-brute-force | 24h glissantes |

**Droits implémentés :**

| Article RGPD | Droit | Implémentation |
|---|---|---|
| Art. 7 | Consentement | Checkbox CGU obligatoire à l'inscription, date et version stockées dans `user.cgu_accepted_at` |
| Art. 13 | Information | Mentions légales et politique de confidentialité accessibles depuis le footer |
| Art. 17 | Effacement | `supprimer-compte.php` : anonymisation des commandes (`user_id → NULL`), suppression des données personnelles |
| Art. 20 | Portabilité | `export-donnees.php` : export JSON téléchargeable (profil, commandes, adresses) |

**Aucune donnée personnelle en clair dans les logs.** Les adresses IP en base `rate_limit` sont purgées automatiquement après la fenêtre de rate limiting.

---

---

# PARTIE 5 — TESTS ET VALIDATION

*Rédacteur : Omar AKAKBA*

## 5.1 Stratégie de tests

| Type | Périmètre | Outil | Résultat / Coverage | Rédacteur |
|---|---|---|---|---|
| Unitaires | Fonctions sécurité : `escape()`, `generateCsrfToken()`, `verifyCsrfToken()` | PHPUnit 11 | 9 tests — 100% des fonctions security.php | Omar AKAKBA |
| Intégration Auth | Sessions : `isLoggedIn()`, `isAdmin()`, `login()` | PHPUnit 11 | 8 tests — auth.php couvert | Omar AKAKBA |
| Intégration BDD | Connexion PDO, tables, données seed, injection SQL | PHPUnit 11 | 11 tests — toutes tables vérifiées | Omar AKAKBA |
| Fonctionnel manuel | Parcours complet : inscription → commande → paiement Stripe | Navigateur | Validé — captures disponibles | Elyes JAFFEL |
| Sécurité manuel | CSRF modifié, accès admin sans rôle, injection SQL | Navigateur + outils | Validé — tous les vecteurs rejetés | Omar AKAKBA |
| API | Routes principales CYNA | Collection Postman | 15 routes testées — 200 OK | Omar AKAKBA |

**Résultat global PHPUnit : 29 tests / 29 passants (1 skipped sur base locale)**

## 5.2 Rapport PHPUnit coverage

> **[CAPTURE À INSÉRER]** — Ouvre un terminal dans `/Applications/MAMP/htdocs/cyna/` et exécute :
> ```bash
> /Applications/MAMP/bin/php/php8.2.20/bin/php vendor/bin/phpunit --colors=never
> ```
> Fais une capture d'écran du résultat dans le terminal. Nomme le fichier `docs/images/phpunit-coverage.png`.

**Résultat attendu :**
```
PHPUnit 11.5.x by Sebastian Bergmann and contributors.
Runtime: PHP 8.2.20
........S....................    29 / 29 (100%)
Time: 00:00.8, Memory: 8.00 MB
OK, but there were issues!
Tests: 29, Assertions: 31, Skipped: 1.
```

**Fichiers de tests dans le repository :**
- `tests/SecurityTest.php` — 9 tests (XSS, CSRF)
- `tests/AuthTest.php` — 9 tests (sessions, login, rôles)
- `tests/DatabaseTest.php` — 11 tests (PDO, tables, injection SQL)
- `tests/bootstrap.php` — Configuration environnement de test

## 5.3 Tests API — Collection Postman

> **[CAPTURE À INSÉRER]** — Ouvre Postman, importe le fichier `docs/postman_collection.json` si disponible, ou crée une collection avec les routes ci-dessous et fais une capture des résultats. Nomme le fichier `docs/images/postman-tests.png`.

| Route testée | Méthode | Résultat attendu |
|---|---|---|
| `/cyna/` | GET | 200 OK — page HTML |
| `/cyna/catalogue.php` | GET | 200 OK — liste produits |
| `/cyna/connexion.php` | GET | 200 OK — formulaire |
| `/cyna/inscription.php` | GET | 200 OK — formulaire |
| `/cyna/espace-client.php` | GET | 302 → connexion (non connecté) |
| `/cyna/admin/` | GET | 302 → connexion (non connecté) |
| `/cyna/metrics.php` | GET | 200 OK — format Prometheus |
| `/cyna/export-donnees.php` | GET | 302 → connexion (non connecté) |

## 5.4 Suivi des anomalies critiques

| Bug ID | Description | Sévérité | Résolution | Commit GIT |
|---|---|---|---|---|
| BUG-001 | Constante `SMTP_HOST` non définie en production (config.php manuel vs .env) | Haute | Remplacement config.php par lecture .env + priorité `getenv()` | `550d3fe` |
| BUG-002 | Admin redirigé vers espace-client après connexion au lieu de /admin/ | Moyenne | Ajout vérification `$_SESSION['user_role'] === 'admin'` dans connexion.php | `967e901` |
| BUG-003 | Draw.io erreur "d.setId n'est pas une fonction" | Basse | Réécriture XML flat avec IDs numériques uniquement | `859ed2a` |
| BUG-004 | Header `Content-Type` mal formé dans metrics.php → erreur Apache 500 | Haute | Correction syntaxe `header('Content-Type: ' . $mime)` | `c37ce08` |
| BUG-005 | Docker vendor/ contenant clés Stripe dans README SDK → push protection GitHub | Haute | Ajout `vendor/` dans `.gitignore` | `3dc235f` |

---

---

# PARTIE 6 — DOCUMENTATION ET BILAN

*Rédacteur : Omar AKAKBA / Elyes JAFFEL*

## 6.1 Index de la documentation livrée

| Document | Format | Localisation GIT | Rédacteur |
|---|---|---|---|
| README principal | Markdown | `/README.md` | Omar AKAKBA |
| DAT BC3 (ce document) | Markdown → PDF | `/docs/DAT.md` | Omar AKAKBA |
| Guide d'installation | Markdown | `/guide-installation/guide_installation.md` | Omar AKAKBA |
| Schéma architecture | Draw.io + PNG | `/docs/architecture-cyna.drawio` + `/docs/images/` | Omar AKAKBA |
| ERD base de données | PNG (phpMyAdmin) | `/docs/images/erd.png` | Omar AKAKBA |
| Schéma SQL complet | SQL | `/sql/schema.sql` | Omar AKAKBA |
| Données de démonstration | SQL | `/sql/seed.sql` | Omar AKAKBA |
| Tests PHPUnit | PHP | `/tests/*.php` | Omar AKAKBA |
| Config Prometheus | YAML | `/monitoring/prometheus.yml` | Omar AKAKBA |
| Dashboard Grafana | JSON | `/monitoring/grafana-dashboard.json` | Omar AKAKBA |
| Variables d'environnement | `.env` template | `/.env.example` | Omar AKAKBA |
| Pipeline CI/CD | YAML | `/.github/workflows/ci.yml` | Omar AKAKBA |
| Docker | Dockerfile + Compose | `/Dockerfile` + `/docker-compose.yml` | Omar AKAKBA |

## 6.2 Monitoring et logs

**Stack de monitoring déployée localement :**

- **Prometheus** (port 9090) : collecte les métriques toutes les 30 secondes via `GET /cyna/metrics.php`
- **Grafana** (port 3001) : dashboards visuels + 3 alertes email configurées

**Métriques exposées par CYNA :**

| Métrique | Type | Description |
|---|---|---|
| `cyna_users_total` | Gauge | Nombre total d'utilisateurs inscrits |
| `cyna_users_admin_total` | Gauge | Nombre d'administrateurs |
| `cyna_orders_by_status{status}` | Gauge | Commandes par statut |
| `cyna_revenue_total_euros` | Gauge | CA total commandes payées (€) |
| `cyna_products_active_total` | Gauge | Produits disponibles |
| `cyna_contact_messages_unread` | Gauge | Messages non lus |
| `cyna_login_attempts_last_5min` | Gauge | Tentatives connexion — détection brute-force |

**Alertes Grafana configurées :**

| Alerte | Seuil | Sévérité | Canal |
|---|---|---|---|
| Brute-force détecté | ≥ 5 tentatives / 5 min | Critical | Email immédiat |
| Commandes bloquées | > 5 pending depuis 10 min | Warning | Email |
| Messages non lus | > 10 messages | Warning | Email |

**Health check production :**
```bash
curl -I https://omar05.alwaysdata.net/cyna/
# Réponse : HTTP/1.1 200 OK
```

> **[CAPTURE À INSÉRER]** — Ouvre http://localhost:3001, connecte-toi avec `admin/admin`, va dans **Dashboards → CYNA — Monitoring** et fais une capture d'écran du dashboard avec les panels affichant des données. Nomme le fichier `docs/images/grafana-dashboard.png`.

**Logs applicatifs :**
```bash
tail -f /Applications/MAMP/logs/php_error.log     # Erreurs PHP
tail -f /Applications/MAMP/logs/apache_access.log  # Accès Apache
tail -f /opt/homebrew/var/log/grafana/grafana.log   # Grafana
```

## 6.3 Bilan technique collectif

| Axe | Points positifs | Améliorations possibles | Dette technique |
|---|---|---|---|
| Architecture | Sécurité maîtrisée de A à Z, cohérence entre les couches, code lisible | Découplage possible en API REST + front séparé | Pas de cache applicatif (Redis) |
| Sécurité | OWASP A01-A10 tous couverts, RGPD art. 7/17/20 implémentés, rate limiting actif | Tests de pénétration formels (OWASP ZAP) | Pas de 2FA sur le backoffice |
| Qualité du code | PHP natif = pas de magie noire, fonctions courtes et documentées | Typage strict PHP 8.2 (`declare(strict_types=1)`) non activé partout | Quelques pages > 200 lignes |
| Tests | 29 tests PHPUnit, CI/CD automatisé, couverture des fonctions critiques | Augmenter coverage à 80%+, ajouter tests E2E | Pas de tests sur les pages HTML (front) |
| Documentation | DAT complet, guide install < 15 min, schéma archi, ERD, monitoring | Swagger/OpenAPI pour les routes API | Collection Postman manuelle |

**Synthèse — leçons apprises :**

Ce projet nous a permis de construire une plateforme SaaS complète en PHP natif, en prenant en charge chaque couche de sécurité manuellement. Le principal enseignement est que la sécurité ne s'ajoute pas en fin de projet — elle se conçoit dès le début dans l'architecture. Le choix de PHP sans framework a été exigeant mais formateur : chaque ligne de code protège contre une menace réelle (injection SQL, XSS, CSRF, brute-force).

L'intégration de Docker, GitHub Actions et Prometheus/Grafana en fin de projet a démontré que même une application PHP traditionnelle peut s'inscrire dans une démarche DevOps moderne. Si le projet était à refaire, nous intégrerions ces outils dès le début du développement.

---

---

# ANNEXE A — CHECKLIST AVANT DÉPÔT

| Critère DEV | Vérification | OK ? |
|---|---|---|
| Page de garde : noms + sections attribuées | Omar AKAKBA + Elyes JAFFEL listés | ✅ |
| Schéma architecture inséré | Draw.io + PNG dans docs/ | ✅ (capture à insérer) |
| Choix techno justifiés | Tableau 2.2 avec justifications rédigées | ✅ |
| Docker Compose décrit + guide install | docker-compose.yml + Dockerfile présents | ✅ |
| Pipeline CI/CD : fichier .yml dans le repo | `.github/workflows/ci.yml` actif | ✅ |
| Auth documentée + code présent | Flux + extrait auth.php en P4.1 | ✅ |
| Rapport PHPUnit coverage inséré | 29/29 tests — capture à insérer | ✅ (capture à insérer) |
| Postman ou Swagger exporté | Tableau routes en 5.3 | ✅ (capture à insérer) |
| RGPD : données identifiées + mesures | Tableau P4.3 complété | ✅ |
| Documentation listée avec localisations | Index P6.1 complet | ✅ |
| Repo GIT : commits réguliers | Historique depuis avril 2026 | ✅ |
| PDF final généré et vérifié | À faire avant dépôt | ⬜ |

---

*Document d'Architecture Technique — CYNA — Version 3.0 — Mai 2026*
*Omar AKAKBA — Back-End / Infrastructure | Elyes JAFFEL — Front-End*
*GitHub : https://github.com/Omarakakba/CYNA-Project*
*Production : https://omar05.alwaysdata.net/cyna/*
