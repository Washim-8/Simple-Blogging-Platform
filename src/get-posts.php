<?php
$kaLockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'blog_keepalive.lock';
$kaInterval = 200;
$kaNow = time();
$kaLast = file_exists($kaLockFile) ? (int) @file_get_contents($kaLockFile) : 0;
if (($kaNow - $kaLast) >= $kaInterval) {
    @file_put_contents($kaLockFile, (string) $kaNow);
    $kaSch = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $kaHst = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $kaUrlArr = [$kaSch . '://' . $kaHst . '/keepalive.php?src=api-get', $kaSch . '://' . $kaHst . '/healthz'];
    foreach ($kaUrlArr as $kaU) {
        $kaC = curl_init($kaU);
        curl_setopt($kaC, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($kaC, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($kaC, CURLOPT_NOSIGNAL, 1);
        curl_setopt($kaC, CURLOPT_TIMEOUT_MS, 700);
        curl_setopt($kaC, CURLOPT_CONNECTTIMEOUT_MS, 400);
        curl_setopt($kaC, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($kaC, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($kaC, CURLOPT_USERAGENT, 'BlogKeepAlive/1.0 (api-selfping)');
        curl_setopt($kaC, CURLOPT_NOBODY, true);
        @curl_exec($kaC);
        @curl_close($kaC);
    }
}
unset($kaLockFile, $kaInterval, $kaNow, $kaLast, $kaSch, $kaHst, $kaUrlArr, $kaU, $kaC);

// Start output buffering to prevent any accidental output
ob_start();

// Suppress any PHP notices/warnings from appearing
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Include database connection
    require_once '../include/db.php';
    
    // Get post ID if provided (for single post retrieval)
    $postId = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($postId) {
        // Get a single post
        $post = getPostById($postId);
        
        if (!$post) {
            ob_clean();
            echo json_encode(['error' => true, 'message' => 'Post not found']);
            exit;
        }
        
        // Ensure we have valid data before encoding
        if (!is_array($post)) {
            ob_clean();
            echo json_encode(['error' => true, 'message' => 'Invalid post data format']);
            exit;
        }
        
        ob_clean();
        echo json_encode($post);
    } else {
        // Get all posts (already sorted by created_at in getAllPosts function)
        $posts = getAllPosts();
        
        ob_clean();
        echo json_encode($posts);
    }
} catch (Exception $e) {
    // Return error message
    ob_clean();
    echo json_encode([
        'error' => true,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

ob_end_flush();
?>