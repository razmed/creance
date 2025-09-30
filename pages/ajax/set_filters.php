<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$filters = $input['filters'] ?? [];

$_SESSION['filters'] = $filters;

echo json_encode(['success' => true]);