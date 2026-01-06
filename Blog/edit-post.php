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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    
    if (empty($title) || empty($content)) {
        $error = 'Title and content are required';
    } else {
        $conn = getDBConnection();
        
        // Handle image upload
        $image = $post['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload = uploadFile($_FILES['image'], 'uploads/posts/');
            if ($upload['success']) {
                $image = $upload['filename'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if (!$error) {
            $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, image = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $content, $image, $post_id);
            
            if ($stmt->execute()) {
                $success = 'Post updated successfully!';
                $post = getPostById($post_id);
            } else {
                $error = 'Failed to update post';
            }
            
            $stmt->close();
        }
        
        $conn->close();
    }
}

$page_title = 'Edit Post';
include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Edit Post</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Post Title</label>
            <input type="text" name="title" id="title" class="form-control" 
                   value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="content">Post Content</label>
            <textarea name="content" id="content" class="form-control" rows="15" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Featured Image</label>
            <?php if ($post['image']): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                         alt="Current image" style="max-width: 200px; border-radius: 4px;"
                         onerror="this.style.display='none'">
                </div>
            <?php endif; ?>
            <input type="file" name="image" id="image" class="form-control" accept="image/*">
        </div>
        
        <button type="submit" class="btn btn-success">Update Post</button>
        <a href="post.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>