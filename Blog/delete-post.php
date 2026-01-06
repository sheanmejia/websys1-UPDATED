<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireRole(['Admin', 'Writer']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage-posts.php');
    exit();
}

$post_id = (int)$_GET['id'];
$post = getPostById($post_id);

if (!$post) {
    header('Location: manage-posts.php');
    exit();
}

// Check if user owns the post or is admin
if ($post['author_id'] != $_SESSION['user_id'] && $_SESSION['user_role'] != 'Admin') {
    header('Location: manage-posts.php');
    exit();
}

$conn = getDBConnection();
$stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->close();
$conn->close();

header('Location: manage-posts.php');
exit();
?>