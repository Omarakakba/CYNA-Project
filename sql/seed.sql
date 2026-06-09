-- ============================================================
-- CYNA — Données de démonstration
-- Importer APRÈS schema.sql
-- ============================================================

USE cyna_db;

-- ============================================================
-- Catégories
-- ============================================================
INSERT IGNORE INTO `category` (`name`, `slug`, `description`) VALUES
('EDR',        'edr', 'Endpoint Detection & Response — protection des postes de travail'),
('SOC Managé', 'soc', 'Security Operations Center — surveillance 24/7 par des experts'),
('VPN',        'vpn', 'VPN entreprise chiffré de bout en bout avec gestion centralisée');

-- ============================================================
-- Produits
-- ============================================================
INSERT IGNORE INTO `product` (`name`, `description`, `long_description`, `price`, `category_id`, `is_available`) VALUES
(
    'CYNA EDR Starter',
    'Protection endpoint EDR pour TPE/PME. Détection comportementale en temps réel, isolation automatique des menaces.',
    'CYNA EDR Starter protège jusqu''à 25 postes de travail avec une détection comportementale de pointe. L''agent léger s''installe en moins de 5 minutes sur Windows, macOS et Linux. Tableau de bord unifié, alertes en temps réel et rapports de conformité inclus.',
    9.99, 1, 1
),
(
    'CYNA EDR Pro',
    'EDR nouvelle génération pour ETI. IA prédictive, threat hunting avancé et réponse aux incidents automatisée.',
    'CYNA EDR Pro intègre une intelligence artificielle prédictive capable d''identifier les menaces zero-day avant leur exécution. Inclut le threat hunting proactif, la forensique complète et une API d''intégration SIEM/SOAR.',
    24.99, 1, 1
),
(
    'CYNA SOC Managé 8/5',
    'Surveillance de votre infrastructure par des analystes certifiés, du lundi au vendredi de 8h à 20h.',
    'Notre SOC managé met à votre disposition une équipe d''analystes certifiés (CISSP, CEH) qui surveillent votre infrastructure pendant vos heures ouvrées. Détection des incidents en moins de 15 minutes, rapport mensuel complet et réunion de suivi trimestrielle.',
    299.00, 2, 1
),
(
    'CYNA SOC Managé 24/7',
    'Surveillance continue 24h/24, 7j/7 par nos experts en cybersécurité. SLA de détection < 5 minutes.',
    'La solution la plus complète pour les entreprises ayant des exigences critiques de disponibilité. Surveillance permanente, playbooks de réponse automatisés, astreinte experte et rapport en temps réel via notre portail client.',
    699.00, 2, 1
),
(
    'CYNA VPN Business',
    'VPN entreprise chiffré AES-256 avec authentification MFA intégrée et architecture Zero Trust.',
    'CYNA VPN Business sécurise les accès distants de vos collaborateurs avec un chiffrement AES-256-GCM, une authentification multi-facteurs (TOTP + push mobile) et une politique Zero Trust par défaut. Gestion centralisée des accès et audit complet des connexions.',
    7.99, 3, 1
);

-- ============================================================
-- Slides du carousel (page d'accueil)
-- ============================================================
INSERT IGNORE INTO `slide` (`title`, `subtitle`, `link_url`, `link_label`, `bg_color`, `sort_order`, `is_active`) VALUES
(
    'Protection Zero Trust pour votre entreprise',
    'CYNA EDR Pro détecte et neutralise les menaces en temps réel avant qu''elles se propagent sur votre réseau.',
    '/cyna/catalogue.php?cat=1',
    'Découvrir EDR Pro',
    '#060c1b',
    1, 1
),
(
    'SOC Managé 24/7 — Vos experts en cybersécurité',
    'Des analystes certifiés surveillent votre infrastructure en permanence. Détection d''incidents en moins de 5 minutes.',
    '/cyna/catalogue.php?cat=2',
    'Voir le SOC Managé',
    '#0d2044',
    2, 1
),
(
    'VPN Entreprise — Accès sécurisé partout',
    'Chiffrement AES-256, MFA intégré et architecture Zero Trust pour tous vos collaborateurs en télétravail.',
    '/cyna/catalogue.php?cat=3',
    'Découvrir le VPN',
    '#060c1b',
    3, 1
);

-- ============================================================
-- Comptes de test
-- Mot de passe pour tous : Admin1234!
-- Hash : password_hash('Admin1234!', PASSWORD_BCRYPT)
-- ============================================================
INSERT IGNORE INTO `user` (`email`, `password`, `role`, `first_name`, `last_name`, `cgu_accepted_at`, `cgu_version`) VALUES
(
    'admin@cyna-security.fr',
    '$2y$12$eN3uuBZ0gAY3aO16YM7i4eLrnj.D26sRZ0eV8SpaaB3cs16kw9LSi',
    'admin', 'Admin', 'CYNA', NOW(), '1.0'
),
(
    'client@test.fr',
    '$2y$12$eN3uuBZ0gAY3aO16YM7i4eLrnj.D26sRZ0eV8SpaaB3cs16kw9LSi',
    'user', 'Jean', 'Dupont', NOW(), '1.0'
);
