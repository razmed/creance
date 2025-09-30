<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'classes/Database.php';
require_once 'classes/Creance.php';

// Test avec filtres
$creance = new Creance();

$filters = [
    'REGION' => ['ARZEW']
];

echo "<h2>Test des filtres</h2>";
echo "<pre>Filtres: " . print_r($filters, true) . "</pre>";

try {
    $result = $creance->getAll($filters, 0, 1, 50, '');
    echo "<pre>Résultat: " . count($result) . " lignes</pre>";
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "<pre style='color:red'>ERREUR: " . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>