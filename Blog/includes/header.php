<?php 
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Simple Blog</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">Simple Blog</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <?php if (hasRole(['Writer'])): ?>
                        <li><a href="add-post.php">New Post</a></li>
                        <li><a href="manage-posts.php">Manage Posts</a></li>
                    <?php endif; ?>
                    <?php if (hasRole(['Admin'])): ?>
                        <li><a href="manage-posts.php">Manage Posts</a></li>
                        <li><a href="admin-users.php">Users</a></li>
                        <li><a href="role-requests.php">Requests</a></li>
                    <?php endif; ?>
                    <?php if (hasRole(['Reader'])): ?>
                        <li><a href="request-writer.php" style="background: var(--success); color: white; padding: 0.6rem 1.2rem; border-radius: var(--border-radius-sm);">Become Writer</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <main class="container">