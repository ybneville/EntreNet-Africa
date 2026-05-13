<?php
// admin_dashboard.php - Dashboard for Administrators
session_start();
include 'db.php';
include 'header.php';
include 'sidebar.php';

// Redirect if not logged in or not admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Handle user management actions
$action_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_user') {
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $newRole = isset($_POST['role']) ? trim($_POST['role']) : '';
        $allowedRoles = ['Entrepreneur', 'Investor', 'Mentor'];
        if (!in_array($newRole, $allowedRoles, true)) {
            $newRole = 'Entrepreneur';
        }
        $newIsAdmin = isset($_POST['is_admin']) ? 1 : 0;
        $newIsActive = isset($_POST['is_active']) ? 1 : 0;

        if ($targetUserId > 0) {
            $stmt = $conn->prepare("UPDATE users SET role = ?, is_admin = ?, is_active = ? WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param('siii', $newRole, $newIsAdmin, $newIsActive, $targetUserId);
                if ($stmt->execute()) {
                    $action_message = 'User updated successfully.';
                } else {
                    $action_message = 'Failed to update user: ' . htmlspecialchars($conn->error);
                }
            }
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($targetUserId > 0 && $targetUserId != $user_id) { // Can't delete self
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $targetUserId);
                if ($stmt->execute()) {
                    $action_message = 'User deleted successfully.';
                } else {
                    $action_message = 'Failed to delete user: ' . htmlspecialchars($conn->error);
                }
            }
        } else {
            $action_message = 'Cannot delete this user.';
        }
    }
}

// Get system statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_ideas = $conn->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];

// Check if new columns/tables exist
$status_column_exists = $conn->query("SHOW COLUMNS FROM posts LIKE 'status'")->num_rows > 0;
$investor_reviews_exists = $conn->query("SHOW TABLES LIKE 'investor_reviews'")->num_rows > 0;
$mentor_feedback_exists = $conn->query("SHOW TABLES LIKE 'mentor_feedback'")->num_rows > 0;

$pending_ideas = $status_column_exists ? $conn->query("SELECT COUNT(*) as count FROM posts WHERE status = 'pending'")->fetch_assoc()['count'] : 0;
$approved_ideas = $status_column_exists ? $conn->query("SELECT COUNT(*) as count FROM posts WHERE status = 'approved'")->fetch_assoc()['count'] : 0;
$investor_reviews = $investor_reviews_exists ? $conn->query("SELECT COUNT(*) as count FROM investor_reviews")->fetch_assoc()['count'] : 0;
$mentor_feedbacks = $mentor_feedback_exists ? $conn->query("SELECT COUNT(*) as count FROM mentor_feedback")->fetch_assoc()['count'] : 0;

// Get recent users
$recent_users_query = "SELECT user_id, full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5";
$recent_users = $conn->query($recent_users_query);

// Get all users for management
$users_query = "SELECT u.user_id, u.full_name, u.email, u.role, u.is_admin, u.is_active, u.created_at,
                       COUNT(DISTINCT p.post_id) as ideas_count
                FROM users u
                LEFT JOIN posts p ON u.user_id = p.user_id
                GROUP BY u.user_id
                ORDER BY u.created_at DESC";
$users_result = $conn->query($users_query);
$users = [];
while($row = $users_result->fetch_assoc()){
    $users[] = $row;
}
?>

<div class="main">
    <div class="welcome">
        <h2>Welcome, <?php echo htmlspecialchars($full_name); ?> - Admin Dashboard</h2>
        <p>Manage the EntreNet Africa platform and oversee all operations.</p>
    </div>

    <?php if($action_message): ?>
        <div class="message <?php echo strpos($action_message, 'success') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($action_message); ?>
        </div>
    <?php endif; ?>

    <!-- System Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>Total Users</h3>
                <p class="stat-number"><?php echo $total_users; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💡</div>
            <div class="stat-content">
                <h3>Total Ideas</h3>
                <p class="stat-number"><?php echo $total_ideas; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3>Pending Reviews</h3>
                <p class="stat-number"><?php echo $pending_ideas; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3>Approved Ideas</h3>
                <p class="stat-number"><?php echo $approved_ideas; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>Investor Reviews</h3>
                <p class="stat-number"><?php echo $investor_reviews; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-content">
                <h3>Mentor Feedback</h3>
                <p class="stat-number"><?php echo $mentor_feedbacks; ?></p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Recent Users -->
        <div class="dashboard-card">
            <h3>🆕 Recent Users</h3>
            <div class="recent-users">
                <?php while($user = $recent_users->fetch_assoc()): ?>
                    <div class="user-item">
                        <div class="user-info">
                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                            <span class="user-role"><?php echo htmlspecialchars($user['role']); ?></span>
                        </div>
                        <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- User Management -->
        <div class="dashboard-card full-width">
            <h3>👥 User Management</h3>
            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Ideas</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo (int)$user['ideas_count']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <?php if($user['is_admin']): ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="editUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', '<?php echo htmlspecialchars($user['role']); ?>', <?php echo $user['is_admin']; ?>, <?php echo $user['is_active']; ?>)" class="btn-edit">Edit</button>
                                    <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" class="btn-delete">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <h3>⚡ Quick Actions</h3>
            <div class="action-buttons">
                <a href="reports.php" class="action-btn">📊 View Reports</a>
                <a href="seed_data.php" class="action-btn">🌱 Seed Data</a>
                <a href="database_migration.php" class="action-btn">🛠️ Run Migration</a>
                <a href="admin_setup.php" class="action-btn">👑 Admin Setup</a>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit User: <span id="editUserName"></span></h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="editUserId">
            
            <label>Role:</label>
            <select name="role" id="editUserRole" required>
                <option value="Entrepreneur">Entrepreneur</option>
                <option value="Investor">Investor</option>
                <option value="Mentor">Mentor</option>
            </select>
            
            <label>
                <input type="checkbox" name="is_admin" id="editIsAdmin"> Administrator
            </label>
            
            <label>
                <input type="checkbox" name="is_active" id="editIsActive"> Active Account
            </label>
            
            <button type="submit" class="btn-primary">Update User</button>
        </form>
    </div>
</div>

<!-- Delete User Modal -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2>Delete User</h2>
        <p>Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" id="deleteUserId">
            <button type="submit" class="btn-delete">Yes, Delete User</button>
            <button type="button" onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
        </form>
    </div>
</div>

<script>
function editUser(userId, name, role, isAdmin, isActive) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editUserName').textContent = name;
    document.getElementById('editUserRole').value = role;
    document.getElementById('editIsAdmin').checked = isAdmin == 1;
    document.getElementById('editIsActive').checked = isActive == 1;
    document.getElementById('editUserModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

function deleteUser(userId, name) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteUserModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteUserModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-top: 4px solid #2c5364;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 2em;
}

.stat-content h3 {
    margin: 0 0 5px 0;
    color: #203a43;
    font-size: 14px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #2c5364;
    margin: 0;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.dashboard-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-top: 4px solid #2c5364;
}

.full-width {
    grid-column: 1 / -1;
}

.dashboard-card h3 {
    color: #203a43;
    margin-top: 0;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}

.recent-users {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-role {
    color: #666;
    font-size: 12px;
}

.users-table table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.users-table th, .users-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.users-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #203a43;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.admin-badge {
    background: #fff3cd;
    color: #856404;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    margin-left: 5px;
}

.btn-edit, .btn-delete {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    margin-right: 5px;
}

.btn-edit { background: #ffc107; color: black; }
.btn-delete { background: #dc3545; color: white; }

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    display: inline-block;
    padding: 10px 15px;
    background: #2c5364;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    text-align: center;
    transition: background 0.3s;
}

.action-btn:hover {
    background: #203a43;
}

.message {
    padding: 12px;
    margin: 15px 0;
    border-radius: 5px;
}

.message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
}

.modal-content h2 {
    color: #203a43;
    margin-top: 0;
}

.modal-content label {
    display: block;
    margin: 15px 0 5px 0;
    font-weight: bold;
    color: #203a43;
}

.modal-content input[type="checkbox"] {
    margin-right: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #203a43 0%, #2c5364 100%);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 20px;
    width: 100%;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 20px;
    margin-left: 10px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #203a43;
}
</style>