<?php
/**
 * AJAX - Recherche et filtrage des créances
 * Gestion des Créances - Version Web
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Log pour debug
error_log("SEARCH_FILTER - Démarrage");

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
    
    $creance = new Creance();
    
    // Récupérer les paramètres (POST ou GET)
    $params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    
    // Log des paramètres reçus
    error_log("SEARCH_FILTER - Params reçus: " . json_encode($params));
    
    // Paramètres de recherche et filtrage
    $search = trim($params['search'] ?? '');
    $filters = isset($params['filters']) ? $params['filters'] : [];
    $archived = intval($params['archived'] ?? 0);
    $page = max(1, intval($params['page'] ?? 1));
    $limit = min(MAX_ITEMS_PER_PAGE, max(10, intval($params['limit'] ?? ITEMS_PER_PAGE)));
    
    // Validation des paramètres
    if ($archived !== 0 && $archived !== 1) {
        $archived = 0;
    }
    
    // Traitement des filtres
    if (is_string($filters)) {
        $filters = json_decode($filters, true);
        if ($filters === null) {
            error_log("SEARCH_FILTER - Erreur décodage JSON filters");
            $filters = [];
        }
    }
    
    // Log des filtres décodés
    error_log("SEARCH_FILTER - Filtres décodés: " . json_encode($filters));
    
    // Nettoyage et validation des filtres
    $cleanFilters = [];
    if (is_array($filters)) {
        foreach ($filters as $column => $values) {
            if (in_array($column, COLONNES_FILTRABLES)) {
                if (is_array($values)) {
                    $cleanValues = array_filter(array_map('trim', $values));
                    if (!empty($cleanValues)) {
                        $cleanFilters[$column] = array_values($cleanValues);
                    }
                } elseif (is_string($values) && trim($values) !== '') {
                    $cleanFilters[$column] = [trim($values)];
                }
            }
        }
    }
    
    // Log des filtres nettoyés
    error_log("SEARCH_FILTER - Filtres nettoyés: " . json_encode($cleanFilters));
    
    // Sauvegarder les filtres en session
    $_SESSION['filters'] = $cleanFilters;
    $_SESSION['last_search'] = $search;
    
    // Récupérer les données
    error_log("SEARCH_FILTER - Appel getAll avec filtres: " . json_encode($cleanFilters));
    $donnees = $creance->getAll($cleanFilters, $archived, $page, $limit, $search);
    error_log("SEARCH_FILTER - Données récupérées: " . count($donnees) . " lignes");
    
    $totalCount = $creance->getCount($cleanFilters, $archived, $search);
    $totalPages = ceil($totalCount / $limit);
    
    // Statistiques
    $stats = null;
    if ($archived === 0) {
        $stats = $creance->getStats($cleanFilters);
    }
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'data' => [
            'creances' => $donnees,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalCount,
                'per_page' => $limit,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'start_index' => ($page - 1) * $limit + 1,
                'end_index' => min($page * $limit, $totalCount)
            ],
            'filters' => [
                'active_filters' => $cleanFilters,
                'search' => $search,
                'archived' => $archived
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Ajouter les stats si disponibles
    if ($stats) {
        $response['data']['stats'] = $stats;
    }
    
    error_log("SEARCH_FILTER - Succès, envoi de " . count($donnees) . " créances");
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("SEARCH_FILTER - Erreur: " . $e->getMessage());
    error_log("SEARCH_FILTER - Trace: " . $e->getTraceAsString());
    
    $errorCode = 'UNKNOWN_ERROR';
    $httpStatus = 500;
    
    if (strpos($e->getMessage(), 'validation') !== false) {
        $errorCode = 'VALIDATION_ERROR';
        $httpStatus = 400;
    } elseif (strpos($e->getMessage(), 'database') !== false) {
        $errorCode = 'DATABASE_ERROR';
        $httpStatus = 503;
    }
    
    http_response_code($httpStatus);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $errorCode,
        'timestamp' => date('Y-m-d H:i:s'),
        'trace' => $e->getTraceAsString()
    ]);
}
?>