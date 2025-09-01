<?php
// Simple file-based database since SQLite driver is not available

// Define the path to our JSON database file
$dbPath = __DIR__ . '/../db/blog_data.json';
$dbDir = dirname($dbPath);

// Ensure the directory exists
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Initialize the database if it doesn't exist
if (!file_exists($dbPath)) {
    // Create sample data
    $initialData = [
        [
            'id' => 1,
            'title' => 'Welcome to Simple Blog Platform',
            'content' => 'This is your first post on the Simple Blog Platform. You can edit or delete this post, or create new ones!',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'title' => 'Getting Started with Blogging',
            'content' => 'Blogging is a great way to share your thoughts and ideas with the world. Start by creating engaging content that resonates with your audience.',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Save to file
    file_put_contents($dbPath, json_encode($initialData, JSON_PRETTY_PRINT));
}

// Database functions

/**
 * Get all posts from the database
 * @return array Array of posts
 */
function getAllPosts() {
    global $dbPath;
    
    if (!file_exists($dbPath)) {
        return [];
    }
    
    // Debug
    error_log("Reading posts from: {$dbPath}");
    $jsonContent = file_get_contents($dbPath);
    error_log("JSON content: {$jsonContent}");
    
    $posts = json_decode($jsonContent, true);
    
    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        return [];
    }
    
    // Ensure $posts is an array
    if (!is_array($posts)) {
        error_log("Posts is not an array: " . gettype($posts));
        return [];
    }
    
    // Sort by created_at (newest first)
    usort($posts, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $posts;
}

/**
 * Get a single post by ID
 * @param int $id Post ID
 * @return array|null Post data or null if not found
 */
function getPostById($id) {
    $posts = getAllPosts();
    
    // Ensure $id is treated as an integer for comparison
    $id = intval($id);
    
    // Debug
    error_log("Looking for post ID: $id");
    error_log("Available posts: " . json_encode($posts));
    
    foreach ($posts as $post) {
        if (intval($post['id']) === $id) {
            error_log("Found post: " . json_encode($post));
            return $post;
        }
    }
    
    error_log("Post ID $id not found");
    return null;
}

/**
 * Create a new post
 * @param string $title Post title
 * @param string $content Post content
 * @return bool Success status
 */
function createPost($title, $content) {
    global $dbPath;
    
    $posts = getAllPosts();
    
    // Find the highest ID
    $maxId = 0;
    foreach ($posts as $post) {
        if ($post['id'] > $maxId) {
            $maxId = $post['id'];
        }
    }
    
    // Create new post
    $newPost = [
        'id' => $maxId + 1,
        'title' => $title,
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Add to array
    $posts[] = $newPost;
    
    // Save to file
    return file_put_contents($dbPath, json_encode($posts, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Update an existing post
 * @param int $id Post ID
 * @param string $title New title
 * @param string $content New content
 * @return bool Success status
 */
function updatePost($id, $title, $content) {
    global $dbPath;
    
    // Convert ID to integer and validate
    $id = (int)$id;
    
    // Debug
    error_log("Attempting to update post with ID: {$id}");
    
    // Get all posts
    $posts = getAllPosts();
    error_log("Found " . count($posts) . " posts total");
    
    $updated = false;
    
    // Loop through posts to find matching ID
    foreach ($posts as $key => $post) {
        $postId = (int)$post['id'];
        error_log("Checking post ID: {$postId} against {$id}");
        
        if ($postId === $id) {
            error_log("Found matching post at index {$key}");
            $posts[$key]['title'] = $title;
            $posts[$key]['content'] = $content;
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        error_log("Updating post with ID: {$id}");
        $result = file_put_contents($dbPath, json_encode($posts, JSON_PRETTY_PRINT));
        error_log("File write result: {$result} bytes");
        return $result !== false;
    }
    
    error_log("Failed to find post with ID: {$id}");
    return false;
}

/**
 * Delete a post
 * @param int $id Post ID
 * @return bool Success status
 */
function deletePost($id) {
    global $dbPath;
    
    $posts = getAllPosts();
    $initialCount = count($posts);
    
    // Filter out the post to delete
    $posts = array_filter($posts, function($post) use ($id) {
        return $post['id'] != $id;
    });
    
    // Reindex array
    $posts = array_values($posts);
    
    if (count($posts) < $initialCount) {
        return file_put_contents($dbPath, json_encode($posts, JSON_PRETTY_PRINT)) !== false;
    }
    
    return false;
}
?>