<?php
/**
 * Configuration de la base de données
 * Gestion des Créances - Version Web
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_creances');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'application
define('APP_NAME', 'Gestion des Créances');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Africa/Algiers');

// Configuration des chemins
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('UPLOAD_PATH', ROOT_PATH . '/exports/');
define('ASSETS_PATH', '/assets/');

// Configuration de sécurité
define('SESSION_TIMEOUT', 3600); // 1 heure
define('MAX_LOGIN_ATTEMPTS', 5);
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes

// Configuration PDF
define('PDF_AUTHOR', 'Système de Gestion des Créances');
define('PDF_TITLE', 'Rapport de Créances');
define('PDF_MARGIN_LEFT', 10);
define('PDF_MARGIN_RIGHT', 10);
define('PDF_MARGIN_TOP', 15);
define('PDF_MARGIN_BOTTOM', 15);

// Configuration des erreurs
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/error.log');

// Fuseau horaire
date_default_timezone_set(APP_TIMEZONE);

// Démarrage de session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Fonction pour créer les répertoires nécessaires
function createRequiredDirectories() {
    $directories = [
        ROOT_PATH . '/exports',
        ROOT_PATH . '/logs',
        ROOT_PATH . '/temp'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

createRequiredDirectories();
?>