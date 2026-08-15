<?php
$kaLockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'blog_keepalive.lock';
$kaInterval = 200;
$kaNow = time();
$kaLast = file_exists($kaLockFile) ? (int) @file_get_contents($kaLockFile) : 0;
if (($kaNow - $kaLast) >= $kaInterval) {
    @file_put_contents($kaLockFile, (string) $kaNow);
    $kaSch = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $kaHst = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $kaUrlArr = [$kaSch . '://' . $kaHst . '/keepalive.php?src=api-post', $kaSch . '://' . $kaHst . '/healthz'];
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

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get action type
    $action = isset($_POST['action']) ? $_POST['action'] : 'save';

    // Handle different actions
    switch ($action) {
        case 'delete':
            // Delete post
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                throw new Exception('Post ID is required');
            }

            $result = deletePost($_POST['id']);

            if ($result) {
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Post deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete post');
            }
            break;

        case 'save':
        default:
            // Validate input
            if (!isset($_POST['title']) || empty($_POST['title'])) {
                throw new Exception('Title is required');
            }

            if (!isset($_POST['content']) || empty($_POST['content'])) {
                throw new Exception('Content is required');
            }

            // Sanitize input
            $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
            $content = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');

            // Check if it's an update or insert
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Validate and sanitize ID
                $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
                
                if ($id === false) {
                    throw new Exception('Invalid post ID format');
                }
                
                // Update existing post
                $result = updatePost($id, $title, $content);

                if ($result) {
                    ob_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Post updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update post');
                }
            } else {
                // Insert new post
                $result = createPost($title, $content);

                if ($result) {
                    ob_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Post created successfully'
                    ]);
                } else {
                    throw new Exception('Failed to create post');
                }
            }
            break;
    }
} catch (Exception $e) {
    // Return error message
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

ob_end_flush();
?>