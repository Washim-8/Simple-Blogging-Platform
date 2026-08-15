<?php
http_response_code(200);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
echo json_encode([
    'status' => 'ok'
]);
exit;
