<?php
// Copier ce fichier en config.php et renseigner tes valeurs
// config.php est dans .gitignore — ne jamais le versionner

define('DB_HOST', 'localhost');
define('DB_NAME', 'cyna_db');
define('DB_USER', 'ton_utilisateur');
define('DB_PASS', 'ton_mot_de_passe');
define('DB_CHARSET', 'utf8mb4');

// Connexion PDO
function getDB(): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    return new PDO($dsn, DB_USER, DB_PASS, $options);
}
