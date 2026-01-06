<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to share']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $platform = isset($_POST['platform']) ? sanitize($_POST['platform']) : '';
    
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit();
    }
    
    if (trackShare($post_id, $_SESSION['user_id'], $platform)) {
        $shareCount = getShareCount($post_id);
        echo json_encode([
            'success' => true,
            'shareCount' => $shareCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to track share']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>