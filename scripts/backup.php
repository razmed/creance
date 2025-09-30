<?php
require_once '../config/database.php';
require_once '../classes/Database.php';

try {
    $db = new Database();
    $file = $db->backup();
    echo "Sauvegarde créée : $file\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}