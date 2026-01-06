<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireRole(['Admin', 'Writer']);

$conn = getDBConnection();

// Get posts based on role
if ($_SESSION['user_role'] == 'Admin') {
    $posts = getAllPosts();
} else {
    $stmt = $conn->prepare("SELECT p.*, u.username, u.full_name 
                           FROM posts p 
                           JOIN users u ON p.author_id = u.id 
                           WHERE p.author_id = ? 
                           ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

$page_title = 'Manage Posts';
include 'includes/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title" style="margin: 0;">Manage Posts</h2>
        <a href="add-post.php" class="btn">Add New Post</a>
    </div>
    
    <?php if (empty($posts)): ?>
        <p>No posts yet. <a href="add-post.php">Create your first post</a></p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Updated at</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                        <td><?php echo htmlspecialchars($post['full_name']); ?></td>
                        <td><?php echo date('M j, Y , H:i:s', strtotime($post['created_at'])); ?></td>
                        <td><?php echo date('M j, Y , H:i:s', strtotime($post['updated_at'])); ?></td>
                        <td>
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-small">View</a>
                            <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-small">Edit</a>
                            <a href="delete-post.php?id=<?php echo $post['id']; ?>" class="btn btn-danger btn-small" 
                               onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>