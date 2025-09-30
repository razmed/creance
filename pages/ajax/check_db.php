<?php
require_once '../../config/database.php';
require_once '../../classes/Database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $db->connect();
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}