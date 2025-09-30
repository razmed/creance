<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Creance.php';

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? 0;
    $creance = new Creance();
    $data = $creance->getById($id);
    
    if ($data) {
        echo json_encode(['success' => true, 'creance' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Créance introuvable']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}