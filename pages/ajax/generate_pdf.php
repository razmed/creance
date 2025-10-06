<?php
/**
 * AJAX - Générer un rapport PDF
 * VERSION CORRIGÉE - Suppression de tous les warnings
 */

// CRITIQUE: Masquer TOUS les warnings avant tout autre code
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

session_start();

// Header JSON APRÈS suppression des erreurs
header('Content-Type: application/json; charset=utf-8');

// Vérifier méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée. Utilisez POST.'
    ]);
    exit;
}

try {
    // Includes
    require_once '../../config/database.php';
    require_once '../../config/constants.php';
    require_once '../../classes/Database.php';
    require_once '../../classes/Creance.php';
    require_once '../../classes/PDF.php';

    // Vérifier extension GD
    if (!extension_loaded('gd')) {
        echo json_encode([
            'success' => false,
            'error' => 'Extension PHP GD non installée. Impossible de générer les graphiques.'
        ]);
        exit;
    }
    
    // Récupérer paramètres
    $includeBarChart = isset($_POST['bar_chart']) && $_POST['bar_chart'] === '1';
    $includePieChart = isset($_POST['pie_chart']) && $_POST['pie_chart'] === '1';
    $includeRadarChart = isset($_POST['radar_chart']) && $_POST['radar_chart'] === '1';
    $archived = intval($_POST['archived'] ?? 0);
    
    // Filtres session
    $filters = $_SESSION['filters'] ?? [];
    $search = $_SESSION['last_search'] ?? '';
    
    // Init objets
    $creance = new Creance();
    
    // Récupérer données
    $donnees = $creance->getAll($filters, $archived, 1, MAX_EXPORT_ROWS, $search);
    
    if (empty($donnees)) {
        echo json_encode(['success' => false, 'error' => 'Aucune donnée à exporter']);
        exit;
    }
    
    // Stats
    $stats = $archived === 0 ? $creance->getStats($filters) : null;
    
    // Créer PDF
    $title = $archived === 1 ? 'Rapport d\'Archive des Créances' : 'Rapport de Gestion des Créances';
    $pdf = new CreancePDF($title, true);
    
    // Générer rapport
    $pdf->generateReport($donnees, $stats, $filters);
    
    // Graphiques
    $chartImages = [];
    $tempDir = ROOT_PATH . '/temp/';
    
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0755, true);
    }
    
    // Générer graphiques demandés
    $hasAnyChart = $includeBarChart || $includePieChart || $includeRadarChart;
    
    if ($hasAnyChart && $archived === 0) {
        try {
            if ($includeBarChart) {
                $barChartPath = $tempDir . 'bar_chart_' . uniqid() . '.png';
                if ($pdf->generateBarChart($donnees, $barChartPath)) {
                    $chartImages['Créances vs Provisions par Région'] = $barChartPath;
                }
            }
            
            if ($includePieChart) {
                $pieChartPath = $tempDir . 'pie_chart_' . uniqid() . '.png';
                if ($pdf->generatePieChart($donnees, $pieChartPath)) {
                    $chartImages['Répartition par Secteur'] = $pieChartPath;
                }
            }
            
            if ($includeRadarChart) {
                $natures = array_unique(array_column($donnees, 'nature'));
                if (count($natures) >= 3) {
                    $radarChartPath = $tempDir . 'radar_chart_' . uniqid() . '.png';
                    if ($pdf->generateRadarChart($donnees, $radarChartPath)) {
                        $chartImages['Radar par Nature'] = $radarChartPath;
                    }
                }
            }
            
            if (!empty($chartImages)) {
                $pdf->addCharts($chartImages);
            }
            
        } catch (Exception $chartError) {
            error_log("Erreur graphiques: " . $chartError->getMessage());
        }
    }
    
    // Nom fichier
    $filename = ($archived === 1 ? 'rapport_archive_' : 'rapport_creances_') . date('Y-m-d_H-i-s') . '.pdf';
    
    // Créer répertoire exports
    $exportsDir = ROOT_PATH . '/exports/';
    if (!is_dir($exportsDir)) {
        @mkdir($exportsDir, 0755, true);
    }
    
    $filepath = $exportsDir . $filename;
    
    // Sauvegarder PDF
    $pdf->Output($filepath, 'F');
    
    if (!file_exists($filepath)) {
        throw new Exception('Impossible de créer le fichier PDF');
    }
    
    $fileSize = filesize($filepath);
    
    // Nettoyer temporaires
    foreach ($chartImages as $imagePath) {
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }
    
    // Succès
    echo json_encode([
        'success' => true,
        'message' => 'PDF généré avec succès',
        'download_url' => 'exports/' . $filename,
        'filename' => $filename,
        'size' => round($fileSize / 1024, 2) . ' KB',
        'charts_included' => count($chartImages)
    ]);
    
} catch (Exception $e) {
    error_log("ERREUR GENERATE_PDF: " . $e->getMessage());
    
    // Nettoyer en cas d'erreur
    if (isset($chartImages)) {
        foreach ($chartImages as $imagePath) {
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}