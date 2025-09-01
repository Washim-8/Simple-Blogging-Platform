<?php
include '../include/db.php';

header('Content-Type: application/json');

// Check for delete action
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    try {
        // Delete post
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Retrieve POST data for create/update
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title = isset($_POST['title']) ? $_POST['title'] : '';
$content = isset($_POST['content']) ? $_POST['content'] : '';

// Validate data
if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
    exit;
}

try {
    if ($id > 0) {
        // Update existing post
        $stmt = $pdo->prepare('UPDATE posts SET title = ?, content = ? WHERE id = ?');
        $stmt->execute([$title, $content, $id]);
    } else {
        // Insert new post
        $stmt = $pdo->prepare('INSERT INTO posts (title, content) VALUES (?, ?)');
        $stmt->execute([$title, $content]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
