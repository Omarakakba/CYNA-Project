<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
session_start();
logout(); // efface le cookie remember_me + détruit la session + redirige
