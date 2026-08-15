<?php
http_response_code(200);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$source = $_GET['source'] ?? 'unknown';
$response = [
    'status' => 'alive',
    'timestamp' => date('c'),
    'source' => $source,
    'self_ping_triggered' => false
];

$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'blog_keepalive.lock';
$selfPingInterval = 240;
$currentTime = time();

$lastPing = 0;
if (file_exists($lockFile)) {
    $lastPing = (int) @file_get_contents($lockFile);
}

$shouldSelfPing = ($currentTime - $lastPing) >= $selfPingInterval;

if ($shouldSelfPing) {
    @file_put_contents($lockFile, (string) $currentTime);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $selfPingUrls = [
        $scheme . '://' . $host . '/healthz',
        $scheme . '://' . $host . '/index.php?ping=1'
    ];

    foreach ($selfPingUrls as $selfPingUrl) {
        $ch = curl_init($selfPingUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 800);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BlogKeepAlive/1.0 (self-ping)');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        if (function_exists('curl_setopt') && defined('CURLOPT_PIPEWAIT')) {
            curl_setopt($ch, CURLOPT_PIPEWAIT, 1);
        }
        @curl_exec($ch);
        @curl_close($ch);
    }

    $response['self_ping_triggered'] = true;
    $response['self_ping_urls'] = $selfPingUrls;
}

$response['seconds_since_last_ping'] = $currentTime - $lastPing;
$response['self_ping_interval_seconds'] = $selfPingInterval;

echo json_encode($response);
exit;
