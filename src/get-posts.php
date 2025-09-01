<?php
header('Content-Type: application/json');

try {
    // Include database connection
    require_once '../include/db.php';
    
    // Get post ID if provided (for single post retrieval)
    $postId = isset($_GET['id']) ? $_GET['id'] : null;
    
    if ($postId) {
        // Get a single post
        $post = getPostById($postId);
        
        if (!$post) {
            echo json_encode(['error' => true, 'message' => 'Post not found']);
            exit;
        }
        
        // Ensure we have valid data before encoding
        if (!is_array($post)) {
            error_log('Invalid post data: ' . print_r($post, true));
            echo json_encode(['error' => true, 'message' => 'Invalid post data format']);
            exit;
        }
        
        echo json_encode($post);
    } else {
        // Get all posts (already sorted by created_at in getAllPosts function)
        $posts = getAllPosts();
        
        echo json_encode($posts);
    }
} catch (Exception $e) {
    // Return error message
    echo json_encode([
        'error' => true,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}