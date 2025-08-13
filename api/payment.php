<?php
// API endpoint to handle payments (cash or PromptPay via Beam)
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/beam.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$orderId = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$method  = isset($data['method']) ? $data['method'] : '';

if ($orderId <= 0 || !$method) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Fetch order and total
$order = null;
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, total, status FROM orders WHERE id=?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        throw new Exception('Order not found');
    }
    if ($order['status'] !== 'OPEN') {
        throw new Exception('Order not open');
    }
    $total = (float)$order['total'];
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

if ($method === 'cash') {
    // Insert payment record
    $pdo->prepare('INSERT INTO payments (order_id, method, amount, status, paid_at) VALUES (?,?,?,?,NOW())')
        ->execute([$orderId, 'CASH', $total, 'SUCCEEDED']);
    // Mark order paid
    markOrderPaid($orderId);
    echo json_encode(['success' => true]);
    exit;
} elseif ($method === 'promptpay') {
    // Create Beam payment
    $result = createBeamPayment($orderId, $total);
    if (!$result || !isset($result['id'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create PromptPay QR']);
        exit;
    }
    $providerRef = $result['id'];
    $qrData      = $result['promptpay_qr'] ?? ($result['qr_code'] ?? null);
    $expiresAt   = $result['expires_at'] ?? null;
    // Insert payment record with pending status
    $pdo->prepare('INSERT INTO payments (order_id, method, amount, provider_ref, status) VALUES (?,?,?,?,?)')
        ->execute([$orderId, 'PROMPTPAY', $total, $providerRef, 'PENDING']);
    // Update table status to PAYING
    $stmt = $pdo->prepare('SELECT table_id FROM orders WHERE id=?');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    if ($row) {
        $tableId = (int)$row['table_id'];
        $pdo->prepare('UPDATE tables SET status="PAYING" WHERE id=?')->execute([$tableId]);
    }
    echo json_encode([
        'success' => true,
        'provider_ref' => $providerRef,
        'qr_data' => $qrData,
        'expires_at' => $expiresAt
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid payment method']);