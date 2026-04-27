-- CYNA — Données de test
USE cyna_db;

INSERT INTO category (name, slug) VALUES
    ('EDR / Antivirus', 'edr-antivirus'),
    ('SOC / SIEM', 'soc-siem'),
    ('VPN / Accès distant', 'vpn-acces-distant');

INSERT INTO product (name, description, price, category_id) VALUES
    ('CrowdStrike Falcon Go', 'Protection endpoint EDR pour PME', 9.99, 1),
    ('Sentinel One Core', 'EDR nouvelle génération', 14.99, 1),
    ('Splunk Cloud', 'SIEM cloud managé', 49.99, 2),
    ('NordVPN Teams', 'VPN pour équipes distantes', 7.99, 3);

-- Compte admin de test (mot de passe : Admin1234!)
-- Généré avec password_hash('Admin1234!', PASSWORD_BCRYPT)
INSERT INTO user (email, password, role) VALUES
    ('admin@cyna.fr', '$2y$10$example_hash_replace_me', 'admin'),
    ('client@test.fr', '$2y$10$example_hash_replace_me', 'user');
