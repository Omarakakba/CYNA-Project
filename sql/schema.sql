-- ============================================================
-- CYNA — Schéma de base de données (version complète)
-- Auteur  : Omar Akakba
-- Projet  : Plateforme SaaS Cybersécurité
-- Moteur  : MySQL 8.0 / InnoDB — utf8mb4_unicode_ci
-- ============================================================

-- Créer et sélectionner la base
CREATE DATABASE IF NOT EXISTS cyna_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cyna_db;

-- ============================================================
-- TABLE : user
-- Comptes utilisateurs (clients et administrateurs)
-- Colonnes de sécurité : reset_token (réinitialisation mdp),
--   remember_token (connexion persistante via cookie)
-- Colonnes RGPD : cgu_accepted_at, cgu_version
-- ============================================================
CREATE TABLE `user` (
    `id`              INT            NOT NULL AUTO_INCREMENT,
    `email`           VARCHAR(150)   NOT NULL,
    `password`        VARCHAR(255)   NOT NULL,           -- bcrypt via password_hash()
    `role`            ENUM('user','admin') DEFAULT 'user',
    `first_name`      VARCHAR(100)   DEFAULT NULL,
    `last_name`       VARCHAR(100)   DEFAULT NULL,
    `reset_token`     VARCHAR(64)    DEFAULT NULL,       -- token SHA-256 (reset mdp + remember me)
    `reset_token_exp` DATETIME       DEFAULT NULL,       -- expiration du token
    `cgu_accepted_at` DATETIME       DEFAULT NULL,       -- RGPD art. 7 — consentement
    `cgu_version`     VARCHAR(10)    DEFAULT '1.0',      -- version des CGU acceptées
    `created_at`      DATETIME       DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : category
-- Catégories de produits (EDR, SOC, VPN…)
-- ============================================================
CREATE TABLE `category` (
    `id`          INT           NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)  NOT NULL,
    `slug`        VARCHAR(100)  NOT NULL,
    `description` VARCHAR(300)  DEFAULT NULL,
    `image_url`   VARCHAR(255)  DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : product
-- Produits / solutions SaaS disponibles à la vente
-- ============================================================
CREATE TABLE `product` (
    `id`               INT            NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(200)   NOT NULL,
    `description`      TEXT           DEFAULT NULL,      -- description courte (listing)
    `long_description` TEXT           DEFAULT NULL,      -- description longue (fiche produit)
    `price`            DECIMAL(10,2)  NOT NULL,
    `image`            VARCHAR(255)   DEFAULT NULL,
    `image_url`        VARCHAR(255)   DEFAULT NULL,
    `is_available`     TINYINT(1)     NOT NULL DEFAULT 1, -- 1 = disponible, 0 = indisponible
    `category_id`      INT            DEFAULT NULL,
    `created_at`       DATETIME       DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : order
-- Commandes passées par les utilisateurs
-- user_id est nullable pour la suppression RGPD (art. 17) :
--   à la suppression du compte, user_id passe à NULL
--   (conservation légale de la commande, anonymisée)
-- ============================================================
CREATE TABLE `order` (
    `id`         INT           NOT NULL AUTO_INCREMENT,
    `user_id`    INT           DEFAULT NULL,             -- NULL après suppression compte (RGPD)
    `status`     ENUM('pending','paid','shipped','cancelled') DEFAULT 'pending',
    `total`      DECIMAL(10,2) NOT NULL,
    `created_at` DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : order_item
-- Lignes de commande (un produit par ligne)
-- duration : 'monthly' ou 'annual'
-- ============================================================
CREATE TABLE `order_item` (
    `id`         INT           NOT NULL AUTO_INCREMENT,
    `order_id`   INT           NOT NULL,
    `product_id` INT           NOT NULL,
    `quantity`   INT           NOT NULL DEFAULT 1,
    `price`      DECIMAL(10,2) NOT NULL,                -- prix unitaire au moment de l'achat
    `duration`   VARCHAR(10)   NOT NULL DEFAULT 'monthly', -- 'monthly' | 'annual'
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`)   REFERENCES `order` (`id`),
    CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : payment
-- Paiements Stripe liés aux commandes
-- stripe_id : Payment Intent ID ou Session ID Stripe
-- ============================================================
CREATE TABLE `payment` (
    `id`         INT            NOT NULL AUTO_INCREMENT,
    `order_id`   INT            NOT NULL,
    `stripe_id`  VARCHAR(255)   DEFAULT NULL,
    `status`     ENUM('pending','paid','failed') DEFAULT 'pending',
    `amount`     DECIMAL(10,2)  NOT NULL,
    `paid_at`    DATETIME       DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_id` (`order_id`),
    CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : address
-- Carnet d'adresses des utilisateurs
-- Suppression en cascade si l'utilisateur est supprimé
-- ============================================================
CREATE TABLE `address` (
    `id`          INT          NOT NULL AUTO_INCREMENT,
    `user_id`     INT          NOT NULL,
    `label`       VARCHAR(100) DEFAULT 'Adresse principale',
    `first_name`  VARCHAR(100) DEFAULT NULL,
    `last_name`   VARCHAR(100) DEFAULT NULL,
    `company`     VARCHAR(150) DEFAULT NULL,
    `address1`    VARCHAR(255) NOT NULL,
    `address2`    VARCHAR(255) DEFAULT NULL,
    `city`        VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20)  NOT NULL,
    `country`     VARCHAR(100) DEFAULT 'France',
    `phone`       VARCHAR(30)  DEFAULT NULL,
    `is_default`  TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `address_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : contact_message
-- Messages reçus via le formulaire de contact
-- is_read : 0 = non lu, 1 = lu (géré dans admin/messages.php)
-- ============================================================
CREATE TABLE `contact_message` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(150) NOT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `subject`    VARCHAR(200) NOT NULL,
    `message`    TEXT         NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : rate_limit
-- Limitation de débit par IP et action (OWASP A09)
-- Nettoyage automatique des entrées expirées dans rate_limit.php
-- Index composite pour les recherches COUNT(*) par IP + action
-- ============================================================
CREATE TABLE `rate_limit` (
    `id`         INT         NOT NULL AUTO_INCREMENT,
    `ip`         VARCHAR(45) NOT NULL,                  -- IPv4 ou IPv6
    `action`     VARCHAR(50) NOT NULL,                  -- 'login' | 'register' | 'reset_password'
    `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ip_action` (`ip`, `action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : slide
-- Slides du carousel sur la page d'accueil
-- Géré depuis admin/carousel.php
-- ============================================================
CREATE TABLE `slide` (
    `id`          INT          NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(200) NOT NULL,
    `subtitle`    VARCHAR(300) DEFAULT NULL,
    `link_url`    VARCHAR(255) DEFAULT NULL,
    `link_label`  VARCHAR(100) DEFAULT 'Découvrir',
    `image_url`   VARCHAR(255) DEFAULT NULL,
    `bg_color`    VARCHAR(50)  DEFAULT '#060c1b',
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONNÉES INITIALES — Catégories
-- ============================================================
INSERT INTO `category` (`name`, `slug`, `description`) VALUES
('EDR',         'edr', 'Endpoint Detection & Response — protection des postes de travail'),
('SOC Managé',  'soc', 'Security Operations Center — surveillance 24/7 par des experts'),
('VPN',         'vpn', 'VPN entreprise chiffré de bout en bout avec gestion centralisée');

-- ============================================================
-- DONNÉES INITIALES — Compte administrateur
-- Mot de passe par défaut : Admin@cyna2024  (à changer immédiatement)
-- Hash généré avec : password_hash('Admin@cyna2024', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO `user` (`email`, `password`, `role`, `cgu_accepted_at`, `cgu_version`) VALUES
('admin@cyna-security.fr',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin',
 NOW(),
 '1.0');
