<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $sql = "SELECT DISTINCT client FROM creances WHERE client IS NOT NULL AND client != '' ORDER BY client";
    $results = $db->select($sql);
    $clients = array_column($results, 'client');
    
    echo json_encode(['success' => true, 'clients' => $clients]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}