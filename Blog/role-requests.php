<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireRole(['Admin']);

$success = '';
$error = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $user_id = (int)$_POST['user_id'];
    
    $conn = getDBConnection();
    
    if ($action == 'approve') {
        // Update user role
        $stmt = $conn->prepare("UPDATE users SET role = 'Writer' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Update request status
        $stmt = $conn->prepare("UPDATE role_requests SET status = 'approved', reviewed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        
        $success = "Request approved! User is now a Writer.";
    } elseif ($action == 'reject') {
        // Update request status
        $stmt = $conn->prepare("UPDATE role_requests SET status = 'rejected', reviewed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        
        $success = "Request rejected.";
    }
    
    $stmt->close();
    $conn->close();
}

// Get all pending requests
$conn = getDBConnection();
$result = $conn->query("SELECT rr.*, u.username, u.email, u.full_name 
                       FROM role_requests rr 
                       JOIN users u ON rr.user_id = u.id 
                       WHERE rr.status = 'pending' 
                       ORDER BY rr.created_at DESC");
$pending_requests = $result->fetch_all(MYSQLI_ASSOC);

// Get reviewed requests
$result = $conn->query("SELECT rr.*, u.username, u.email, u.full_name 
                       FROM role_requests rr 
                       JOIN users u ON rr.user_id = u.id 
                       WHERE rr.status != 'pending' 
                       ORDER BY rr.reviewed_at DESC 
                       LIMIT 20");
$reviewed_requests = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();

$page_title = 'Role Requests';
include 'includes/header.php';
?>

<div class="fade-in">
    <div style="background: white; border-radius: var(--border-radius); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-lg);">
        <h1 style="color: var(--gray-900); margin: 0;">📋 Writer Role Requests</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Review and approve/reject writer access requests</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card card-glow">
        <div class="card-header">
            <h2 class="card-title">⏳ Pending Requests (<?php echo count($pending_requests); ?>)</h2>
        </div>
        
        <?php if (empty($pending_requests)): ?>
            <p style="color: var(--gray-500); text-align: center; padding: 2rem;">No pending requests</p>
        <?php else: ?>
            <?php foreach ($pending_requests as $request): ?>
                <div style="border: 2px solid var(--gray-200); border-radius: var(--border-radius-sm); padding: 1.5rem; margin-bottom: 1.5rem; background: var(--gray-50);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0; color: var(--gray-900);">
                                <?php echo htmlspecialchars($request['full_name']); ?>
                                <span style="color: var(--gray-500); font-size: 0.9rem; font-weight: normal;">
                                    (@<?php echo htmlspecialchars($request['username']); ?>)
                                </span>
                            </h3>
                            <p style="margin: 0.25rem 0 0 0; color: var(--gray-600);">
                                <?php echo htmlspecialchars($request['email']); ?>
                            </p>
                            <small style="color: var(--gray-500);">
                                Requested <?php echo timeAgo($request['created_at']); ?>
                            </small>
                        </div>
                        <span class="badge" style="background: var(--warning); color: white;">Pending</span>
                    </div>
                    
                    <div style="background: white; padding: 1.5rem; border-radius: var(--border-radius-sm); margin-bottom: 1rem;">
                        <strong style="color: var(--gray-700); display: block; margin-bottom: 0.5rem;">Reason:</strong>
                        <p style="margin: 0; color: var(--gray-800); line-height: 1.7;">
                            <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                        </p>
                    </div>
                    
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success btn-small">✓ Approve</button>
                    </form>
                    
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger btn-small" 
                                onclick="return confirm('Are you sure you want to reject this request?')">
                            ✗ Reject
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($reviewed_requests)): ?>
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h2 class="card-title">📝 Recent Decisions</h2>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviewed_requests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['full_name']); ?></strong><br>
                                    <small style="color: var(--gray-500);">@<?php echo htmlspecialchars($request['username']); ?></small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $request['status'] == 'approved' ? 'badge-success' : ''; ?>" 
                                          style="<?php echo $request['status'] == 'rejected' ? 'background: var(--danger); color: white;' : ''; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo timeAgo($request['created_at']); ?></td>
                                <td><?php echo timeAgo($request['reviewed_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>