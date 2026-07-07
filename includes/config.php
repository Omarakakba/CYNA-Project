<?php

// Chargement du fichier .env
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

function env(string $key, string $default = ''): string {
    // Priorité : variable d'environnement système (Docker) > .env fichier > défaut
    return getenv($key) ?: ($_ENV[$key] ?? $default);
}

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'cyna_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', 'root'));
define('DB_PORT', env('DB_PORT', '8889'));

define('STRIPE_SECRET_KEY',      env('STRIPE_SECRET_KEY'));
define('STRIPE_PUBLISHABLE_KEY', env('STRIPE_PUBLISHABLE_KEY'));
define('STRIPE_WEBHOOK_SECRET',  env('STRIPE_WEBHOOK_SECRET'));

define('SMTP_HOST',      env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT',      (int)env('SMTP_PORT', '587'));
define('SMTP_USER',      env('SMTP_USER'));
define('SMTP_PASS',      env('SMTP_PASS'));
define('SMTP_FROM',      env('SMTP_FROM'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'CYNA Security'));

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
