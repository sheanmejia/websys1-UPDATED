<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user = getUserById($_SESSION['user_id']);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    
    if (empty($full_name) || empty($email)) {
        $error = 'All fields are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email format';
    } else {
        $conn = getDBConnection();
        
        // Handle profile picture upload
        $profile_picture = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $upload = uploadFile($_FILES['profile_picture'], 'uploads/profiles/');
            if ($upload['success']) {
                $profile_picture = $upload['filename'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if (!$error) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $profile_picture, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                $success = 'Profile updated successfully!';
                $user = getUserById($_SESSION['user_id']);
            } else {
                $error = 'Update failed';
            }
            
            $stmt->close();
        }
        
        $conn->close();
    }
}

$page_title = 'Profile';
include 'includes/header.php';
?>

<div class="card">
    <div class="profile-header">
        <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
             alt="Profile Picture" class="profile-picture" 
             onerror="this.src='uploads/profiles/default.jpg'">
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p>@<?php echo htmlspecialchars($user['username']); ?></p>
            <span style="display: inline; color: var(--primary);
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.05em; "><?php echo $user['role']; ?></span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Edit Profile</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" 
                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" 
                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="profile_picture">Profile Picture</label>
            <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/*">
        </div>
        
        <button type="submit" class="btn btn-success">Update Profile</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>