<?php
// Currency configuration
// If your DB prices were in USD, you can run the conversion script in tools/convert_usd_to_inr.php.

define('APP_CURRENCY_CODE', getenv('APP_CURRENCY_CODE') ?: 'INR');
define('APP_CURRENCY_LOCALE', getenv('APP_CURRENCY_LOCALE') ?: 'en-IN');

// Used only by the conversion script (run once).
define('USD_TO_INR_RATE', getenv('USD_TO_INR_RATE') ? (float) getenv('USD_TO_INR_RATE') : 83.00);

