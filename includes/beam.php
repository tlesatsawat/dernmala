<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Create a PromptPay payment intent via Beam API.
 *
 * @param int    $orderId
 * @param float  $amount     Total amount in Baht
 * @return array|false       Returns payment info including provider_ref and qr_data on success or false on failure
 */
function createBeamPayment(int $orderId, float $amount)
{
    // Amount in satang (minor units)
    $amountSatang = (int)round($amount * 100);
    $payload = [
        'method'       => 'promptpay',
        'amount'       => $amountSatang,
        'currency'     => 'THB',
        'reference'    => 'ORDER-' . $orderId,
        'description'  => 'Table order #' . $orderId,
        'callback_url' => SITE_URL . '/pos_mala/api/webhook.php',
        'metadata'     => ['order_id' => $orderId],
    ];
    $ch = curl_init('https://api.beamcheckout.com/v1/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . BEAM_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        curl_close($ch);
        return false;
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status >= 200 && $status < 300) {
        $data = json_decode($response, true);
        return $data;
    }
    return false;
}

/**
 * Verify webhook signature from Beam.
 *
 * @param string $signature Base64 encoded HMAC signature from header
 * @param string $payload   Raw payload
 * @return bool
 */
function verifyBeamSignature(string $signature, string $payload): bool
{
    $expected = base64_encode(hash_hmac('sha256', $payload, BEAM_WEBHOOK_SECRET, true));
    // Timing-safe comparison
    if (function_exists('hash_equals')) {
        return hash_equals($expected, $signature);
    }
    return $expected === $signature;
}