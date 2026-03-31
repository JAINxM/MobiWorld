<?php

declare(strict_types=1);

// Run once from CLI:
//   C:\xampp\php\php.exe MobiWorld\tools\convert_usd_to_inr.php 83.25
//
// This multiplies existing DB prices (product_master, cart_items, order_items, orders) by USD->INR rate.

require_once __DIR__ . '/../includes/config/db.php';
require_once __DIR__ . '/../includes/config/currency.php';

$marker = dirname(__DIR__) . '/.currency_migrated_to_inr';
if (file_exists($marker)) {
    fwrite(STDERR, "ABORT: Currency conversion already applied (marker exists): $marker\n");
    exit(1);
}

$rate = USD_TO_INR_RATE;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $rate = (float) $argv[1];
}

if ($rate <= 0) {
    fwrite(STDERR, "ABORT: Invalid rate. Provide a positive number.\n");
    exit(1);
}

try {
    $pdo->beginTransaction();

    $pdo->prepare('UPDATE product_master SET regular_price = ROUND(regular_price * ?, 2)')->execute([$rate]);
    $pdo->prepare('UPDATE product_master SET discounted_price = ROUND(discounted_price * ?, 2) WHERE discounted_price IS NOT NULL')->execute([$rate]);

    $pdo->prepare('UPDATE cart_items SET price_at_time = ROUND(price_at_time * ?, 2)')->execute([$rate]);
    $pdo->prepare('UPDATE order_items SET price_at_time = ROUND(price_at_time * ?, 2)')->execute([$rate]);
    $pdo->prepare('UPDATE orders SET total_amount = ROUND(total_amount * ?, 2)')->execute([$rate]);

    $pdo->commit();

    $info = [
        'converted_at' => date('c'),
        'rate' => $rate,
        'note' => 'Converted USD prices to INR by multiplying existing values. Do not run again.',
    ];
    file_put_contents($marker, json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

    fwrite(STDOUT, "OK: Converted prices to INR with rate=$rate. Marker written: $marker\n");
    exit(0);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}

