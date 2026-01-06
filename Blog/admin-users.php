<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireRole(['Admin']);

$success = '';
$error = '';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = sanitize($_POST['role']);
    
    if (in_array($new_role, ['Admin', 'Writer', 'Reader'])) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        
        if ($stmt->execute()) {
            $success = "User role updated successfully!";
        } else {
            $error = "Failed to update role.";
        }
        
        $stmt->close();
        $conn->close();
    }
}

// Get all users
$conn = getDBConnection();
$result = $conn->query("SELECT id, username, email, full_name, role, created_at FROM users ORDER BY created_at DESC");
$users = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

$page_title = 'Manage Users';
include 'includes/header.php';
?>

<div class="fade-in">
    <div style="background: white; border-radius: var(--border-radius); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-lg);">
        <h1 style="color: var(--gray-900); margin: 0;">👥 User Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Manage user roles and permissions</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card card-glow">
        <div class="card-header">
            <h2 class="card-title">All Users (<?php echo count($users); ?>)</h2>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $user['role'] == 'Admin' ? 'primary' : 
                                        ($user['role'] == 'Writer' ? 'success' : ''); 
                                ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button onclick="openRoleModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo $user['role']; ?>')" 
                                        class="btn btn-small">
                                    Change Role
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Role Change Modal -->
<div id="roleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: var(--border-radius); padding: 2rem; max-width: 500px; width: 90%; box-shadow: var(--shadow-xl);">
        <h3 style="margin-top: 0; color: var(--gray-900);">Change User Role</h3>
        <p style="color: var(--gray-600);">User: <strong id="modalUsername"></strong></p>
        
        <form method="POST" action="">
            <input type="hidden" name="user_id" id="modalUserId">
            
            <div class="form-group">
                <label for="role">Select New Role</label>
                <select name="role" id="modalRole" class="form-control" required>
                    <option value="Reader">Reader - Can read and comment</option>
                    <option value="Writer">Writer - Can create, edit posts</option>
                    <option value="Admin">Admin - Full access</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" name="update_role" class="btn btn-success">Update Role</button>
                <button type="button" onclick="closeRoleModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRoleModal(userId, username, currentRole) {
    document.getElementById('roleModal').style.display = 'flex';
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUsername').textContent = username;
    document.getElementById('modalRole').value = currentRole;
}

function closeRoleModal() {
    document.getElementById('roleModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('roleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRoleModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>