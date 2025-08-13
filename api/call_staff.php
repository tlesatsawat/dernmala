<?php
// API endpoint for call staff from customer
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    $table = isset($data['table']) ? (int)$data['table'] : 0;
    if ($table <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid table number']);
        exit;
    }
    callStaff($table);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);