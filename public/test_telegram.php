<?php
// Script to test Telegram Bot API connectivity
// Upload this to your public_html or public folder and access via browser

header('Content-Type: text/plain');

$botToken = 'YOUR_BOT_TOKEN_HERE'; // Replace if needed, or read from cache check
$chatId = 'YOUR_CHAT_ID_HERE'; // Optional

echo "--- TELEGRAM CONNECTIVITY TEST ---\n";
echo "PHP Version: " . phpversion() . "\n";
echo "cURL Version: " . curl_version()['version'] . "\n";
echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "\n\n";

$endpoints = [
    'Domain (api.telegram.org)' => 'https://api.telegram.org/bot<TOKEN>/getMe',
    'Direct IP (149.154.167.220)' => 'https://149.154.167.220/bot<TOKEN>/getMe'
];

// Try to read token from config if available (simulate basic environment)
if (file_exists('../config/services.php')) {
    $config = require '../config/services.php';
    $tokenFromConfig = $config['telegram']['bot_token'] ?? null;
    if ($tokenFromConfig) {
        $botToken = $tokenFromConfig;
        echo "Found token in config/services.php\n";
    }
}

// Check .env as fallback
if ($botToken === 'YOUR_BOT_TOKEN_HERE' && file_exists('../.env')) {
    $env = file_get_contents('../.env');
    if (preg_match('/TELEGRAM_BOT_TOKEN=(.+)/', $env, $matches)) {
        $botToken = trim($matches[1]);
        echo "Found token in .env\n";
    }
}

if ($botToken === 'YOUR_BOT_TOKEN_HERE') {
    die("ERROR: Could not find TELEGRAM_BOT_TOKEN. Please edit this file and paste it manually or ensure files are uploaded correctly.");
}

$maskedToken = substr($botToken, 0, 5) . '...' . substr($botToken, -5);
echo "Using Token: {$maskedToken}\n\n";

foreach ($endpoints as $label => $urlTemplate) {
    echo "Testing {$label}...\n";

    $url = str_replace('<TOKEN>', $botToken, $urlTemplate);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // Mimic the helper settings
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if (str_contains($label, 'Direct IP')) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: api.telegram.org']);
    }

    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        echo "❌ FAILED ({$duration}ms)\nError: {$error}\n";
    } else {
        echo "✅ SUCCESS ({$duration}ms)\nHTTP Code: {$httpCode}\nResponse: {$response}\n";
    }
    echo "--------------------------------------------------\n\n";
}
