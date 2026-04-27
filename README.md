# CYNA — Plateforme E-Commerce · Solutions SaaS Cybersécurité

> Projet Fil Rouge CPI 2025–2026  
> **Back-End** : Omar Akakba | **Front-End** : Elyes Jaffel

## Stack technique

| Couche | Technologie | Pourquoi |
|--------|-------------|----------|
| Back-End | PHP 8 (pur, sans framework) | Maîtrisé en cours, lisible, portable |
| Base de données | MySQL 8 + PDO | Vu en cours, requêtes SQL directes |
| Front-End | HTML5 / CSS3 / JavaScript | Fondamentaux maîtrisés |
| Serveur | Apache (LAMP) | Simple, documenté, hébergement standard |
| Versioning | Git / GitHub | Vu en cours |
| Tests API | Postman | Vu en cours |

> Aucun framework imposé → aucun framework utilisé.

## Structure du projet

```
CYNA-Project/
├── public/                  ← Racine web (seul dossier accessible par le navigateur)
│   ├── index.php            ← Accueil
│   ├── catalogue.php        ← Liste des produits
│   ├── produit.php          ← Fiche produit
│   ├── panier.php           ← Panier
│   ├── connexion.php        ← Login
│   ├── inscription.php      ← Register
│   ├── commande.php         ← Tunnel d'achat
│   ├── espace-client.php    ← Historique commandes
│   ├── admin/
│   │   ├── index.php        ← Dashboard admin
│   │   ├── produits.php     ← CRUD produits
│   │   └── commandes.php    ← Gestion commandes
│   └── assets/
│       ├── css/style.css
│       ├── js/main.js
│       └── images/
├── includes/                ← Code PHP non accessible directement
│   ├── config.php           ← Connexion BDD (PDO)
│   ├── auth.php             ← Fonctions login / session
│   ├── security.php         ← Sécurité (CSRF, XSS, injection SQL)
│   └── functions.php        ← Fonctions utilitaires
├── sql/
│   ├── schema.sql           ← Création des tables
│   └── seed.sql             ← Données de test
└── docs/
    └── postman_collection.json  ← Collection Postman (routes testées)
```

## Installation locale

```bash
# 1. Cloner le dépôt
git clone https://github.com/Omarakakba/CYNA-Project.git
cd CYNA-Project

# 2. Créer la base de données
mysql -u root -p < sql/schema.sql
mysql -u root -p cyna_db < sql/seed.sql

# 3. Configurer la connexion BDD
cp includes/config.example.php includes/config.php
# Modifier includes/config.php avec tes identifiants MySQL

# 4. Placer le projet dans le dossier web (XAMPP/WAMP ou serveur Apache)
# Pointer Apache sur le dossier public/

# 5. Ouvrir dans le navigateur
# http://localhost/
```

## Sécurité mise en place

- Requêtes préparées PDO (protection injection SQL)
- `password_hash()` / `password_verify()` pour les mots de passe
- `htmlspecialchars()` pour l'affichage (protection XSS)
- Token CSRF fait maison sur chaque formulaire
- Sessions PHP sécurisées (`session_regenerate_id()`)
- `.htaccess` : accès interdit au dossier `includes/`

## Workflow Git

- Branche `main` protégée — pas de commit direct
- Convention : `feat/xxx`, `fix/xxx`, `chore/xxx`
- Pull Request obligatoire avec relecture croisée
- Messages de commit en anglais

## Lien DAT

Documentation technique (BC3) : [CYNA-DAT](https://github.com/Omarakakba/CYNA-DAT)
