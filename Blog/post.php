<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$post_id = (int)$_GET['id'];
$post = getPostById($post_id);

if (!$post) {
    header('Location: index.php');
    exit();
}

$comments = getCommentsByPost($post_id);
$reactions = getPostReactions($post_id);
$userReaction = isLoggedIn() ? getUserReaction($post_id, $_SESSION['user_id']) : null;
$shareCount = getShareCount($post_id);

$error = '';
$success = '';

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isLoggedIn()) {
    if (isset($_POST['submit_comment'])) {
        $comment_text = sanitize($_POST['comment']);
        
        if (empty($comment_text)) {
            $error = 'Comment cannot be empty';
        } else {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT id FROM comments WHERE user_id = ? AND post_id = ? AND comment = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $stmt->bind_param("iis", $_SESSION['user_id'], $post_id, $comment_text);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = 'Duplicate comment detected. Please wait before commenting again.';
            } else {
                $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $post_id, $_SESSION['user_id'], $comment_text);
                
                if ($stmt->execute()) {
                    $success = 'Comment posted successfully!';
                    $comments = getCommentsByPost($post_id);
                } else {
                    $error = 'Failed to post comment';
                }
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}

$page_title = $post['title'];
include 'includes/header.php';
?>

<div class="layout-grid fade-in">
    <div>
        <div class="card card-glow">
            <?php if ($post['image']): ?>
                <img src="uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                     alt="Post Image" style="width: 100%; max-height: 500px; object-fit: cover; border-radius: var(--border-radius); margin-bottom: 2rem;"
                     onerror="this.style.display='none'">
            <?php endif; ?>
            
            <h1 style="color: var(--gray-900); margin-bottom: 1rem; font-size: 2.5rem; line-height: 1.2;">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>
            
            <div class="post-meta" style="margin-bottom: 1.5rem;">
                By <strong><?php echo htmlspecialchars($post['full_name']); ?></strong> • 
                <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
            </div>
            
            <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 1.5rem 0;">
            
            <div style="line-height: 1.8; font-size: 1.1rem; color: var(--gray-700);">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
            
            <!-- Reactions and Share Bar -->
            <div class="reactions-bar">
                <div class="reactions-container">
                    <?php
                    $reactionEmojis = [
                        'like' => '👍',
                        'love' => '❤️',
                        'laugh' => '😂',
                        'wow' => '😮',
                        'sad' => '😢',
                        'angry' => '😠'
                    ];
                    
                    foreach ($reactionEmojis as $type => $emoji):
                        $isActive = ($userReaction === $type);
                    ?>
                        <button class="reaction-btn <?php echo $isActive ? 'active' : ''; ?>" 
                                data-reaction="<?php echo $type; ?>"
                                data-post-id="<?php echo $post_id; ?>"
                                onclick="handleReaction(this, '<?php echo $type; ?>', <?php echo $post_id; ?>)"
                                <?php if (!isLoggedIn()): ?>
                                    onclick="alert('Please login to react'); return false;"
                                <?php endif; ?>>
                            <span class="reaction-emoji"><?php echo $emoji; ?></span>
                            <span class="reaction-count" id="count-<?php echo $type; ?>">
                                <?php echo $reactions[$type] > 0 ? $reactions[$type] : ''; ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Reaction Summary -->
            <?php if ($reactions['total'] > 0): ?>
                <div class="reaction-summary">
                    <span style="color: var(--gray-600); font-weight: 500; margin-right: 0.5rem;">
                        <?php echo $reactions['total']; ?> <?php echo $reactions['total'] == 1 ? 'reaction' : 'reactions'; ?>
                    </span>
                    <?php foreach ($reactionEmojis as $type => $emoji): ?>
                        <?php if ($reactions[$type] > 0): ?>
                            <div class="reaction-summary-item">
                                <span class="reaction-summary-emoji"><?php echo $emoji; ?></span>
                                <span class="reaction-summary-count"><?php echo $reactions[$type]; ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isLoggedIn() && hasRole(['Admin', 'Writer']) && $post['author_id'] == $_SESSION['user_id']): ?>
                <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 1.5rem 0;">
                <div style="display: flex; gap: 1rem;">
                    <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-small">✏️ Edit Post</a>
                    <a href="delete-post.php?id=<?php echo $post['id']; ?>" class="btn btn-danger btn-small" 
                       onclick="return confirm('Are you sure you want to delete this post?')">🗑️ Delete Post</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="comments-section">
            <h3>💬 Comments (<?php echo count($comments); ?>)</h3>
            
            <?php if (isLoggedIn()): ?>
                <div class="card">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="comment">Leave a comment</label>
                            <textarea name="comment" id="comment" class="form-control" rows="4" 
                                      placeholder="Share your thoughts..." required></textarea>
                        </div>
                        <button type="submit" name="submit_comment" class="btn">Post Comment</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Please <a href="login.php" style="color: var(--primary); font-weight: 600;">login</a> to leave a comment.
                </div>
            <?php endif; ?>
            
            <?php if (empty($comments)): ?>
                <p style="text-align: center; color: var(--gray-500); padding: 2rem;">
                    No comments yet. Be the first to comment!
                </p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-author">
                            <?php echo htmlspecialchars($comment['full_name']); ?>
                            <span class="comment-time"> • <?php echo timeAgo($comment['created_at']); ?></span>
                        </div>
                        <div class="comment-text">
                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <aside>
        <div class="sidebar">
            <h3>👤 About Author</h3>
            <p><strong><?php echo htmlspecialchars($post['full_name']); ?></strong></p>
            <p style="color: var(--gray-600);">@<?php echo htmlspecialchars($post['username']); ?></p>
        </div>
    </aside>
</div>

<script>
// Reaction handling
function handleReaction(button, reactionType, postId) {
    <?php if (!isLoggedIn()): ?>
        alert('Please login to react to posts');
        return;
    <?php endif; ?>
    
    const isActive = button.classList.contains('active');
    const action = isActive ? 'remove' : 'add';
    
    fetch('react.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&reaction_type=${reactionType}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update all reaction buttons
            document.querySelectorAll('.reaction-btn').forEach(btn => {
                btn.classList.remove('active');
                const type = btn.dataset.reaction;
                const count = data.reactions[type];
                const countSpan = btn.querySelector('.reaction-count');
                countSpan.textContent = count > 0 ? count : '';
            });
            
            // Set active state
            if (data.userReaction) {
                const activeBtn = document.querySelector(`[data-reaction="${data.userReaction}"]`);
                if (activeBtn) {
                    activeBtn.classList.add('active');
                }
            }
            
            // Update summary
            updateReactionSummary(data.reactions);
        } else {
            alert(data.message || 'Failed to update reaction');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Update reaction summary
function updateReactionSummary(reactions) {
    const summary = document.querySelector('.reaction-summary');
    if (!summary) return;
    
    if (reactions.total === 0) {
        summary.style.display = 'none';
        return;
    }
    
    summary.style.display = 'flex';
    
    const emojis = {
        'like': '👍',
        'love': '❤️',
        'laugh': '😂',
        'wow': '😮',
        'sad': '😢',
        'angry': '😠'
    };
    
    let html = `<span style="color: var(--gray-600); font-weight: 500; margin-right: 0.5rem;">
        ${reactions.total} ${reactions.total === 1 ? 'reaction' : 'reactions'}
    </span>`;
    
    for (const [type, emoji] of Object.entries(emojis)) {
        if (reactions[type] > 0) {
            html += `<div class="reaction-summary-item">
                <span class="reaction-summary-emoji">${emoji}</span>
                <span class="reaction-summary-count">${reactions[type]}</span>
            </div>`;
        }
    }
    
    summary.innerHTML = html;
}

// Share dropdown toggle
function toggleShareDropdown() {
    const dropdown = document.getElementById('shareDropdown');
    dropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const shareBtn = document.getElementById('shareBtn');
    const dropdown = document.getElementById('shareDropdown');
    
    if (!shareBtn.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Share post
function sharePost(platform, postId) {
    event.preventDefault();
    
    const url = window.location.href;
    const title = document.querySelector('h1').textContent;
    let shareUrl = '';
    
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
        trackShare(postId, platform);
    }
    
    toggleShareDropdown();
}

// Copy link
function copyLink(postId) {
    event.preventDefault();
    
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        alert('Link copied to clipboard!');
        trackShare(postId, 'copy');
        toggleShareDropdown();
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// Track share
function trackShare(postId, platform) {
    <?php if (!isLoggedIn()): ?>
        return;
    <?php endif; ?>
    
    fetch('share.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&platform=${platform}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const shareCountSpan = document.getElementById('shareCount');
            if (shareCountSpan && data.shareCount > 0) {
                shareCountSpan.textContent = data.shareCount;
            }
        }
    })
    .catch(error => console.error('Error tracking share:', error));
}
</script>

<?php include 'includes/footer.php'; ?>