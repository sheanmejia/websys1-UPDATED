<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$success = '';
$error = '';

// Check if user is already Writer or Admin
if (hasRole(['Admin', 'Writer'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reason = sanitize($_POST['reason']);
    
    if (empty($reason)) {
        $error = "Please provide a reason for your request.";
    } else {
        $conn = getDBConnection();
        
        // Check if request already exists
        $stmt = $conn->prepare("SELECT id FROM role_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "You already have a pending request.";
        } else {
            // Create role request
            $stmt = $conn->prepare("INSERT INTO role_requests (user_id, requested_role, reason) VALUES (?, 'Writer', ?)");
            $stmt->bind_param("is", $_SESSION['user_id'], $reason);
            
            if ($stmt->execute()) {
                $success = "Your request has been submitted! An admin will review it soon.";
            } else {
                $error = "Failed to submit request. Please try again.";
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}

$page_title = 'Request Writer Access';
include 'includes/header.php';
?>

<div class="fade-in">
    <div class="card" style="max-width: 700px; margin: 2rem auto;">
        <div class="card-header">
            <h2 class="card-title">✍️ Request Writer Access</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <div style="background: var(--gray-50); padding: 1.5rem; border-radius: var(--border-radius-sm); margin-bottom: 1.5rem;">
                <h3 style="margin-top: 0; color: var(--gray-900);">Why become a Writer?</h3>
                <ul style="color: var(--gray-700); line-height: 1.8;">
                    <li>📝 Create and publish blog posts</li>
                    <li>✏️ Edit your own posts</li>
                    <li>📊 Manage your content</li>
                    <li>👥 Engage with readers through your content</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="reason">Why do you want to become a Writer?</label>
                    <textarea name="reason" id="reason" class="form-control" rows="6" 
                              placeholder="Tell us about your writing interests, topics you'd like to cover, or your experience..." required></textarea>
                    <small style="color: var(--gray-500); display: block; margin-top: 0.5rem;">
                        Minimum 50 characters
                    </small>
                </div>
                
                <button type="submit" class="btn btn-success">Submit Request</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>