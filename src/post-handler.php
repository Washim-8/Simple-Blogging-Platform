<?php
header('Content-Type: application/json');

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
            $title = htmlspecialchars($_POST['title']);
            $content = htmlspecialchars($_POST['content']);

            // Check if it's an update or insert
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Validate and sanitize ID
                $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
                
                if ($id === false) {
                    error_log("Invalid post ID format: {$_POST['id']}");
                    throw new Exception('Invalid post ID format');
                }
                
                // Debug
                error_log("Updating post with ID: {$id}");
                error_log("POST data: " . json_encode($_POST));
                
                // Update existing post
                $result = updatePost($id, $title, $content);

                if ($result) {
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
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}