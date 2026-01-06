<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to react']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $reaction_type = isset($_POST['reaction_type']) ? sanitize($_POST['reaction_type']) : '';
    $action = isset($_POST['action']) ? sanitize($_POST['action']) : 'add';
    
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit();
    }
    
    if ($action == 'remove') {
        $result = removeReaction($post_id, $_SESSION['user_id']);
    } else {
        $result = addReaction($post_id, $_SESSION['user_id'], $reaction_type);
    }
    
    if ($result) {
        $reactions = getPostReactions($post_id);
        $userReaction = getUserReaction($post_id, $_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'reactions' => $reactions,
            'userReaction' => $userReaction
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update reaction']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>