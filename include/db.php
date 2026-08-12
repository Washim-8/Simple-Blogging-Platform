<?php
/**
 * Database Connection and Functions
 * 
 * This file handles database connections with automatic fallback:
 * - Production/PostgreSQL: Uses PDO with PostgreSQL
 * - Local Development: Falls back to JSON file storage if PostgreSQL unavailable
 */

// Suppress any warnings/notices
error_reporting(E_ERROR | E_PARSE);

// Global variables
$pdo = null;
$useJsonFallback = false;
$dbPath = __DIR__ . '/../db/blog_data.json';

/**
 * Get database connection using PDO
 * Automatically falls back to JSON if PostgreSQL is unavailable
 * 
 * @return PDO|null Database connection or null if using JSON fallback
 */
function getConnection() {
    global $pdo, $useJsonFallback;
    
    // Return existing connection if available
    if ($pdo !== null) {
        return $pdo;
    }
    
    // If already using JSON fallback, return null
    if ($useJsonFallback) {
        return null;
    }
    
    try {
        // Read DATABASE_URL from environment
        $databaseUrl = getenv('DATABASE_URL');
        
        if ($databaseUrl) {
            // Parse Render PostgreSQL connection string
            // Format: postgres://user:password@host:port/database
            $dbParts = parse_url($databaseUrl);
            
            $host = $dbParts['host'];
            $port = isset($dbParts['port']) ? $dbParts['port'] : 5432;
            $dbname = ltrim($dbParts['path'], '/');
            $user = $dbParts['user'];
            $password = $dbParts['pass'];
            
            // Create DSN
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
            
            // Create PDO instance with proper options
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            return $pdo;
        } else {
            // No DATABASE_URL - use JSON fallback for local development
            $useJsonFallback = true;
            initializeJsonDatabase();
            return null;
        }
        
    } catch (PDOException $e) {
        // PostgreSQL connection failed - fall back to JSON
        $useJsonFallback = true;
        initializeJsonDatabase();
        return null;
    }
}

/**
 * Initialize JSON database file if it doesn't exist
 */
function initializeJsonDatabase() {
    global $dbPath;
    
    $dbDir = dirname($dbPath);
    
    // Ensure the directory exists
    if (!is_dir($dbDir)) {
        @mkdir($dbDir, 0777, true);
    }
    
    // Initialize the database if it doesn't exist
    if (!file_exists($dbPath)) {
        @file_put_contents($dbPath, json_encode([], JSON_PRETTY_PRINT));
    }
}

/**
 * Get all posts from the database
 * @return array Array of posts sorted by created_at (newest first)
 */
function getAllPosts() {
    global $useJsonFallback, $dbPath;
    
    // Initialize connection to set useJsonFallback flag
    getConnection();
    
    // Use JSON fallback if PostgreSQL unavailable
    if ($useJsonFallback) {
        if (!file_exists($dbPath)) {
            return [];
        }
        
        $jsonContent = @file_get_contents($dbPath);
        if ($jsonContent === false) {
            return [];
        }
        
        $posts = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($posts)) {
            return [];
        }
        
        // Sort by created_at (newest first)
        usort($posts, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $posts;
    }
    
    // Use PostgreSQL
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("
            SELECT id, title, content, created_at 
            FROM posts 
            ORDER BY created_at DESC
        ");
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get a single post by ID
 * @param int $id Post ID
 * @return array|null Post data or null if not found
 */
function getPostById($id) {
    global $useJsonFallback, $dbPath;
    
    $id = intval($id);
    
    // Initialize connection to set useJsonFallback flag
    getConnection();
    
    // Use JSON fallback if PostgreSQL unavailable
    if ($useJsonFallback) {
        $posts = getAllPosts();
        
        foreach ($posts as $post) {
            if (intval($post['id']) === $id) {
                return $post;
            }
        }
        
        return null;
    }
    
    // Use PostgreSQL
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            SELECT id, title, content, created_at 
            FROM posts 
            WHERE id = :id
        ");
        
        $stmt->execute(['id' => $id]);
        
        $post = $stmt->fetch();
        
        return $post ?: null;
        
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Create a new post
 * @param string $title Post title
 * @param string $content Post content
 * @return bool Success status
 */
function createPost($title, $content) {
    global $useJsonFallback, $dbPath;
    
    // Initialize connection to set useJsonFallback flag
    getConnection();
    
    // Use JSON fallback if PostgreSQL unavailable
    if ($useJsonFallback) {
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
        return @file_put_contents($dbPath, json_encode($posts, JSON_PRETTY_PRINT)) !== false;
    }
    
    // Use PostgreSQL
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO posts (title, content, created_at) 
            VALUES (:title, :content, NOW())
        ");
        
        $result = $stmt->execute([
            'title' => $title,
            'content' => $content
        ]);
        
        return $result;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Update an existing post
 * @param int $id Post ID
 * @param string $title New title
 * @param string $content New content
 * @return bool Success status
 */
function updatePost($id, $title, $content) {
    global $useJsonFallback, $dbPath;
    
    $id = (int)$id;
    
    // Initialize connection to set useJsonFallback flag
    getConnection();
    
    // Use JSON fallback if PostgreSQL unavailable
    if ($useJsonFallback) {
        $posts = getAllPosts();
        $updated = false;
        
        foreach ($posts as $key => $post) {
            if ((int)$post['id'] === $id) {
                $posts[$key]['title'] = $title;
                $posts[$key]['content'] = $content;
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            return @file_put_contents($dbPath, json_encode($posts, JSON_PRETTY_PRINT)) !== false;
        }
        
        return false;
    }
    
    // Use PostgreSQL
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE posts 
            SET title = :title, content = :content 
            WHERE id = :id
        ");
        
        $result = $stmt->execute([
            'id' => $id,
            'title' => $title,
            'content' => $content
        ]);
        
        // Check if any rows were affected
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete a post
 * @param int $id Post ID
 * @return bool Success status
 */
function deletePost($id) {
    global $useJsonFallback, $dbPath;
    
    $id = intval($id);
    
    // Initialize connection to set useJsonFallback flag
    getConnection();
    
    // Use JSON fallback if PostgreSQL unavailable
    if ($useJsonFallback) {
        $posts = getAllPosts();
        $initialCount = count($posts);
        
        // Filter out the post to delete
        $posts = array_filter($posts, function($post) use ($id) {
            return intval($post['id']) != $id;
        });
        
        // Reindex array
        $posts = array_values($posts);
        
        if (count($posts) < $initialCount) {
            return @file_put_contents($dbPath, json_encode($posts, JSON_PRETTY_PRINT)) !== false;
        }
        
        return false;
    }
    
    // Use PostgreSQL
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        
        $result = $stmt->execute(['id' => $id]);
        
        // Check if any rows were affected
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        return false;
    }
}
?>