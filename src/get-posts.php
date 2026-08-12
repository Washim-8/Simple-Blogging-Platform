<?php
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