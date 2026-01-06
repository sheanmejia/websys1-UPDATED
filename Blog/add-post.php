<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireRole(['Admin', 'Writer']);

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
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload = uploadFile($_FILES['image'], 'uploads/posts/');
            if ($upload['success']) {
                $image = $upload['filename'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO posts (title, content, image, author_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $title, $content, $image, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $post_id = $stmt->insert_id;
                $success = 'Post created successfully!';
                header("Location: post.php?id=$post_id");
                exit();
            } else {
                $error = 'Failed to create post';
            }
            
            $stmt->close();
        }
        
        $conn->close();
    }
}

$page_title = 'Add New Post';
include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Create New Post</h2>
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
                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="content">Post Content</label>
            <textarea name="content" id="content" class="form-control" rows="15" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Featured Image (optional)</label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*">
        </div>
        
        <button type="submit" class="btn btn-success">Publish Post</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>