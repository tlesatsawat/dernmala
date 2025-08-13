<?php
// Webhook endpoint for Beam payment notifications
require_once __DIR__ . '/../includes/beam.php';
require_once __DIR__ . '/../includes/functions.php';

// Beam sends JSON payload with signature header. Adjust header name if Beam uses a different one.
$signature = $_SERVER['HTTP_X_BEAM_SIGNATURE'] ?? '';
$payload   = file_get_contents('php://input');

if (!$payload) {
    http_response_code(400);
    echo 'missing payload';
    exit;
}

// Verify signature
if (!verifyBeamSignature($signature, $payload)) {
    http_response_code(401);
    echo 'invalid signature';
    exit;
}

$data = json_decode($payload, true);
if (!$data) {
    http_response_code(400);
    echo 'invalid JSON';
    exit;
}

// Process event
$eventType = $data['type'] ?? '';
if ($eventType === 'payment.succeeded' || $eventType === 'payment.succeed' || $eventType === 'payment.succeeded') {
    // Payment succeeded
    $paymentData = $data['data'] ?? [];
    $providerRef = $paymentData['id'] ?? ($data['id'] ?? null);
    $amount      = isset($paymentData['amount']) ? ((float)$paymentData['amount']) / 100 : null;
    if ($providerRef) {
        try {
            $pdo = getPDO();
            // Find payment by provider_ref
            $stmt = $pdo->prepare('SELECT id, order_id, status FROM payments WHERE provider_ref=?');
            $stmt->execute([$providerRef]);
            $payment = $stmt->fetch();
            if ($payment && $payment['status'] !== 'SUCCEEDED') {
                $pdo->prepare('UPDATE payments SET status="SUCCEEDED", paid_at=NOW() WHERE id=?')->execute([$payment['id']]);
                $orderId = (int)$payment['order_id'];
                markOrderPaid($orderId);
            }
        } catch (Exception $e) {
            // Log error but still return 200
        }
    }
}

// For other event types we simply acknowledge
http_response_code(200);
echo 'ok';