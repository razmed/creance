<?php
/**
 * Vérifier si le radar chart peut être généré
 * Nécessite au moins 3 natures différentes
 */

session_start();
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    require_once '../../config/constants.php';
    require_once '../../classes/Database.php';
    require_once '../../classes/Creance.php';
    
    $filters = $_SESSION['filters'] ?? [];
    $creance = new Creance();
    
    // Récupérer les données filtrées (limite réduite pour performance)
    $donnees = $creance->getAll($filters, 0, 1, 1000);
    
    // Compter les natures uniques
    $natures = [];
    foreach ($donnees as $row) {
        if (!empty($row['nature']) && !in_array($row['nature'], $natures)) {
            $natures[] = $row['nature'];
        }
    }
    
    $canGenerate = count($natures) >= 3;
    
    echo json_encode([
        'success' => true,
        'canGenerate' => $canGenerate,
        'naturesCount' => count($natures),
        'natures' => $natures
    ]);
    
} catch (Exception $e) {
    error_log("CHECK_RADAR ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'canGenerate' => false,
        'error' => $e->getMessage()
    ]);
}
?>