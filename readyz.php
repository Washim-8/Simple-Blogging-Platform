<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$ready = true;
$checks = [];

try {
    require_once __DIR__ . '/include/db.php';

    $pdo = getConnection();

    if ($pdo !== null) {
        $stmt = $pdo->query('SELECT 1');
        $result = $stmt->fetchColumn();
        $checks['database'] = ($result === '1' || $result === 1) ? 'ok' : 'fail';
        if ($checks['database'] !== 'ok') {
            $ready = false;
        }
    } else {
        global $useJsonFallback, $dbPath;
        $checks['database'] = $useJsonFallback ? 'json_fallback' : 'unavailable';
        if ($useJsonFallback) {
            $checks['json_storage'] = file_exists($dbPath) ? 'ok' : 'missing';
            if ($checks['json_storage'] !== 'ok') {
                $ready = false;
            }
        } else {
            $ready = false;
        }
    }
} catch (Exception $e) {
    $checks['database'] = 'error';
    $checks['error'] = $e->getMessage();
    $ready = false;
}

http_response_code($ready ? 200 : 503);

echo json_encode([
    'status' => $ready ? 'ready' : 'not_ready',
    'checks' => $checks
]);
exit;
