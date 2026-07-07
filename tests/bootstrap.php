<?php
/**
 * Bootstrap PHPUnit — CYNA
 * Les variables CI (DB_HOST, DB_PORT, etc.) ont priorité sur les défauts locaux.
 */
require_once __DIR__ . '/../vendor/autoload.php';

// Variables d'environnement pour les tests (override par CI si présentes)
$defaults = [
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '8889',
    'DB_NAME' => 'cyna_db',
    'DB_USER' => 'root',
    'DB_PASS' => 'root',
    'STRIPE_SECRET_KEY'      => 'sk_test_dummy',
    'STRIPE_PUBLISHABLE_KEY' => 'pk_test_dummy',
    'STRIPE_WEBHOOK_SECRET'  => 'whsec_dummy',
    'SMTP_HOST'      => 'smtp.gmail.com',
    'SMTP_PORT'      => '587',
    'SMTP_USER'      => 'test@example.com',
    'SMTP_PASS'      => 'dummy',
    'SMTP_FROM'      => 'test@example.com',
    'SMTP_FROM_NAME' => 'CYNA Test',
];

foreach ($defaults as $key => $value) {
    if (!getenv($key)) {
        $_ENV[$key] = $value;
    }
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
