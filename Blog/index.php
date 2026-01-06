<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : null;
$posts = getAllPosts(null, $search);
$recent_posts = getAllPosts(5);

$page_title = 'Home';
include 'includes/header.php';
?>

<div class="fade-in">
    <h1>✨ Latest Blog Posts</h1>
    
    <div class="search-box">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="🔍 Search posts by title or content..." 
                   value="<?php echo $search ? htmlspecialchars($search) : ''; ?>" 
                   class="form-control">
        </form>
    </div>
    
    <div class="layout-grid">
        <div>
            <?php if (empty($posts)): ?>
                <div class="card text-center">
                    <p style="font-size: 1.2rem; color: var(--gray-500);">
                        📭 No posts found. 
                        <?php if (isLoggedIn() && hasRole(['Admin', 'Writer'])): ?>
                            <a href="add-post.php" style="color: var(--primary);">Create the first post!</a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): 
                        $reactions = getPostReactions($post['id']);
                        $shareCount = getShareCount($post['id']);
                    ?>
                        <div class="post-card card-glow hover-lift">
                            <div class="post-image-wrapper">
                                <?php if ($post['image']): ?>
                                    <img src="uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image"
                                         onerror="this.parentElement.innerHTML='<div style=\'height: 220px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;\'>📝</div>'">
                                <?php else: ?>
                                    <div style="height: 220px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                        📝
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <div class="post-meta">
                                    <?php echo htmlspecialchars($post['full_name']); ?> • 
                                    <?php echo timeAgo($post['created_at']); ?>
                                </div>
                                <p class="post-excerpt">
                                    <?php echo substr(strip_tags($post['content']), 0, 150) . '...'; ?>
                                </p>
                                
                                <!-- Quick Stats -->
                                <div style="display: flex; gap: 1rem; margin-bottom: 1rem; color: var(--gray-500); font-size: 0.9rem;">
                                    <?php if ($reactions['total'] > 0): ?>
                                        <span>❤️ <?php echo $reactions['total']; ?></span>
                                    <?php endif; ?>
                                    <span>💬 <?php echo count(getCommentsByPost($post['id'])); ?></span>
                                </div>
                                
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-small">Read More →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <aside>
            <div class="sidebar-wrapper">
                <div class="sidebar">
                    <h3>📌 Recent Posts</h3>
                    <?php if (empty($recent_posts)): ?>
                        <p style="color: var(--gray-500);">No recent posts</p>
                    <?php else: ?>
                        <ul class="sidebar-list">
                            <?php foreach ($recent_posts as $post): ?>
                                <li>
                                    <a href="post.php?id=<?php echo $post['id']; ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                    <small>
                                        <?php echo timeAgo($post['created_at']); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php include 'includes/footer.php'; ?>