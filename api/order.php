<?php
// API endpoint for order operations
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'POST' && $action === 'create') {
        // Handle order creation
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        $table = isset($data['table']) ? (int)$data['table'] : 0;
        $items = isset($data['items']) ? $data['items'] : [];
        if ($table <= 0 || empty($items)) {
            throw new Exception('Missing table or items');
        }
        $orderId = createOrder($table, $items);
        echo json_encode(['success' => true, 'order_id' => $orderId]);
        exit;
    }
    if ($method === 'GET' && $action === 'get') {
        $table = isset($_GET['table']) ? (int)$_GET['table'] : 0;
        if ($table <= 0) {
            throw new Exception('Invalid table');
        }
        $order = getOpenOrderByTable($table);
        echo json_encode(['order' => $order]);
        exit;
    }
    // Unknown endpoint
    http_response_code(400);
    echo json_encode(['error' => 'Bad request']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}