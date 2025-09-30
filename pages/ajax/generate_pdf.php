<?php
/**
 * AJAX - Générer un rapport PDF
 * Gestion des Créances - Version Web
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Autoriser les méthodes GET et POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée. Utilisez POST ou GET.',
        'code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

try {
    require_once '../../config/database.php';
    require_once '../../config/constants.php';
    require_once '../../classes/Database.php';
    require_once '../../classes/Creance.php';
    require_once '../../classes/PDF.php';
    
    // Récupérer les paramètres
    $params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    
    $includeCharts = isset($params['charts']) && $params['charts'] === '1';
    $archived = intval($params['archived'] ?? 0);
    $format = $params['format'] ?? 'landscape'; // landscape ou portrait
    $downloadMode = $params['download'] ?? 'browser'; // browser, download, or json
    
    // Récupérer les filtres depuis la session
    $filters = $_SESSION['filters'] ?? [];
    $search = $_SESSION['last_search'] ?? '';
    
    // Validation des paramètres
    if ($archived !== 0 && $archived !== 1) {
        $archived = 0;
    }
    
    if (!in_array($format, ['landscape', 'portrait'])) {
        $format = 'landscape';
    }
    
    if (!in_array($downloadMode, ['browser', 'download', 'json'])) {
        $downloadMode = 'browser';
    }
    
    // Créer l'instance de génération de rapport
    $generator = new ReportGenerator();
    $creance = new Creance();
    
    // Récupérer les données (limite élevée pour export)
    $donnees = $creance->getAll($filters, $archived, 1, MAX_EXPORT_ROWS, $search);
    
    if (empty($donnees)) {
        echo json_encode([
            'success' => false,
            'error' => 'Aucune donnée à exporter',
            'code' => 'NO_DATA'
        ]);
        exit;
    }
    
    // Calculer les statistiques
    $stats = null;
    if ($archived === 0) {
        $stats = $creance->getStats($filters);
    }
    
    // Déterminer le titre du rapport
    $title = $archived === 1 ? 'Rapport d\'Archive des Créances' : 'Rapport de Gestion des Créances';
    if (!empty($search)) {
        $title .= ' (Recherche: "' . htmlspecialchars($search) . '")';
    }
    
    // Créer le PDF
    $isLandscape = ($format === 'landscape');
    $pdf = new CreancePDF($title, $isLandscape);
    
    // Générer le rapport principal
    $pdf->generateReport($donnees, $stats, $filters);
    
    // Ajouter les graphiques si demandé et pas en mode archive
    $chartImages = [];
    if ($includeCharts && $archived === 0) {
        $tempDir = ROOT_PATH . '/temp/';
        
        try {
            // Bar Chart par Région
            $barChartPath = $tempDir . 'bar_chart_' . uniqid() . '.png';
            if ($pdf->generateBarChart($donnees, $barChartPath)) {
                $chartImages['Créances vs Provisions par Région'] = $barChartPath;
            }
            
            // Pie Chart par Secteur
            $pieChartPath = $tempDir . 'pie_chart_' . uniqid() . '.png';
            if ($pdf->generatePieChart($donnees, $pieChartPath)) {
                $chartImages['Répartition par Secteur'] = $pieChartPath;
            }
            
            // Vérifier si on a assez de natures pour le radar
            $natures = array_unique(array_column($donnees, 'nature'));
            if (count($natures) >= 3) {
                $radarChartPath = $tempDir . 'radar_chart_' . uniqid() . '.png';
                // Note: generateRadarChart devrait être implémenté dans PDF.php
                // Pour l'instant, on skip cette fonctionnalité
            }
            
            // Ajouter les graphiques au PDF
            if (!empty($chartImages)) {
                $pdf->addCharts($chartImages);
            }
            
        } catch (Exception $chartError) {
            error_log("GENERATE_PDF - Erreur génération graphiques: " . $chartError->getMessage());
            // Continue sans les graphiques
        }
    }
    
    // Générer le nom de fichier
    $filename = 'rapport_creances_' . date('Y-m-d_H-i-s');
    if ($archived === 1) {
        $filename = 'rapport_archive_' . date('Y-m-d_H-i-s');
    }
    $filename .= '.pdf';
    
    // Générer le fichier selon le mode demandé
    if ($downloadMode === 'json') {
        // Mode JSON: sauvegarder le fichier et retourner les infos
        $filepath = ROOT_PATH . '/exports/' . $filename;
        $pdf->Output($filepath, 'F');
        
        $fileSize = file_exists($filepath) ? filesize($filepath) : 0;
        
        // Nettoyer les fichiers temporaires
        foreach ($chartImages as $imagePath) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'PDF généré avec succès',
            'data' => [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $fileSize,
                'size_formatted' => formatBytes($fileSize),
                'download_url' => 'exports/' . $filename,
                'creation_time' => date('Y-m-d H:i:s'),
                'records_count' => count($donnees),
                'includes_charts' => $includeCharts && !empty($chartImages),
                'format' => $format,
                'archived' => $archived === 1
            ]
        ]);
        
    } else {
        // Mode direct: envoyer le PDF au navigateur
        if ($downloadMode === 'download') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
        }
        
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        $pdf->Output($filename, $downloadMode === 'download' ? 'D' : 'I');
        
        // Nettoyer les fichiers temporaires
        foreach ($chartImages as $imagePath) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }

} catch (Exception $e) {
    error_log("GENERATE_PDF - Erreur: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
    
    // Nettoyer les fichiers temporaires en cas d'erreur
    if (isset($chartImages)) {
        foreach ($chartImages as $imagePath) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
    
    $errorCode = 'UNKNOWN_ERROR';
    $httpStatus = 500;
    
    if (strpos($e->getMessage(), 'permission') !== false) {
        $errorCode = 'PERMISSION_ERROR';
        $httpStatus = 403;
    } elseif (strpos($e->getMessage(), 'memory') !== false) {
        $errorCode = 'MEMORY_ERROR';
        $httpStatus = 507;
    } elseif (strpos($e->getMessage(), 'timeout') !== false) {
        $errorCode = 'TIMEOUT_ERROR';
        $httpStatus = 408;
    } elseif (strpos($e->getMessage(), 'disk') !== false || strpos($e->getMessage(), 'space') !== false) {
        $errorCode = 'DISK_SPACE_ERROR';
        $httpStatus = 507;
    }
    
    if ($downloadMode === 'json') {
        http_response_code($httpStatus);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $errorCode,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        // En mode direct, on ne peut pas renvoyer du JSON après avoir défini les headers PDF
        header('Content-Type: text/plain');
        echo "Erreur lors de la génération du PDF: " . $e->getMessage();
    }
}
    
/**
 * Formater les octets en format lisible
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>