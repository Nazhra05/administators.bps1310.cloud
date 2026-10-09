<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

$url = defined('API_URL') ? API_URL : 'http://localhost/administators.bps1310.cloud/public/api/layanan.php';
$apiKey = defined('API_KEY') ? API_KEY : '';

$options = [
    'http' => [
        'method'        => 'GET',
        'header'        => "X-API-KEY: {$apiKey}\r\n",
        'ignore_errors' => true,
        'timeout'       => 10,
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Test API Layanan</title>
    <style>
        body { font-family: monospace; background: #f8fafc; padding: 20px; color: #1e293b; }
        pre { background: white; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; overflow: auto; }
        .meta { margin-bottom: 12px; font-weight: bold; color: #0066b3; }
    </style>
</head>
<body>
    <div class="meta">Menguji Endpoint: <?= htmlspecialchars($url); ?></div>
    <pre><?= htmlspecialchars((string) $response); ?></pre>
</body>
</html>