<?php

$url = 'http://localhost/dashboard_bps/public/api/layanan.php';
$options = [
    'http' => [
        'method'  => 'GET',
        'header' =>
            "X-API-KEY: BPS_SOLSEL_API_2026_GANTI_DENGAN_KEY_RAHASIA\r\n",
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);

$response = file_get_contents($url, false, $context);

echo '<pre>';
echo htmlspecialchars($response);
echo '</pre>';