<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/razorpay.php';

/**
 * @return array{ok:bool,data?:array,error?:string,status?:int}
 */
function razorpayCreateOrder(int $amountPaise, string $currency, string $receipt, array $notes = []): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL extension is not enabled. Enable php_curl in php.ini.'];
    }

    if (RAZORPAY_KEY_ID === 'rzp_test_your_key_id' || RAZORPAY_KEY_SECRET === 'your_key_secret') {
        return ['ok' => false, 'error' => 'Razorpay not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in includes/config/razorpay.php (or env vars).'];
    }

    $payload = [
        'amount' => $amountPaise,
        'currency' => $currency,
        'receipt' => $receipt,
        'payment_capture' => 1,
        'notes' => $notes,
    ];

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_TIMEOUT => 20,
    ]);

    $body = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'Curl error: ' . $err, 'status' => $status];
    }

    $data = json_decode((string) $body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Invalid Razorpay response', 'status' => $status];
    }

    if ($status < 200 || $status >= 300) {
        $msg = isset($data['error']['description']) ? (string) $data['error']['description'] : 'Razorpay order create failed';
        return ['ok' => false, 'error' => $msg, 'status' => $status, 'data' => $data];
    }

    return ['ok' => true, 'data' => $data, 'status' => $status];
}

function razorpayVerifySignature(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): bool
{
    $payload = $razorpayOrderId . '|' . $razorpayPaymentId;
    $expected = hash_hmac('sha256', $payload, RAZORPAY_KEY_SECRET);
    return hash_equals($expected, $razorpaySignature);
}

