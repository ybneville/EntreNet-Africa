<?php
// users.php displays all users and their activity; restricted to admins

// ensure session available
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

// use output buffering so we can safely redirect after processing POST actions
ob_start();

include 'db.php'; // database connection

// if nobody is logged in redirect to login page
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// only administrators should reach this page
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1){
    echo "<p style='color:red; font-family:Times New Roman;'>Access denied. Admins only.</p>";
    exit();
}

include 'header.php';

// Handle admin actions (change role, toggle admin/active status)
$action_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $action_message = 'Invalid request. Please try again.';
    } else {
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
            } else {
                $action_message = 'Failed to prepare update statement.';
            }
        }
    }
}

// UI components for navigation
include 'sidebar.php';

// placeholders for messages to display later
$error = '';
$success = '';

// determine whether the users table tracks last login times; if so, only show users who have logged in before
$hasLastLoginColumn = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'last_login'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasLastLoginColumn = true;
}

// determine if the users table supports an is_active flag (for disabling accounts)
$hasIsActiveColumn = false;
$activeCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
if ($activeCheck && $activeCheck->num_rows > 0) {
    $hasIsActiveColumn = true;
}

$whereClause = $hasLastLoginColumn ? "WHERE u.last_login IS NOT NULL" : "";
$orderClause = $hasLastLoginColumn ? "u.last_login DESC" : "u.created_at DESC";

// query to fetch each user along with counts of ideas and reviews they created
$sql = "SELECT u.user_id, u.full_name, u.email, u.role, u.created_at, u.is_admin, "
     . ($hasIsActiveColumn ? "u.is_active" : "1 AS is_active") . ",
               COUNT(DISTINCT p.post_id) as ideas_count,
               COUNT(DISTINCT r.review_id) as reviews_count,
               COALESCE(AVG(r.rating), 0) as avg_rating
        FROM users u
        LEFT JOIN posts p ON u.user_id = p.user_id
        LEFT JOIN reviews r ON u.user_id = r.user_id
        " . $whereClause . "
        GROUP BY u.user_id
        ORDER BY " . $orderClause;

$result = $conn->query($sql);
if(!$result){
    $error = "Failed to fetch users: ".$conn->error;
}

// gather some overall user statistics for header display using a single aggregated query
$stats_sql = "SELECT 
           COUNT(*) AS total,
           SUM(role='Entrepreneur') AS entrepreneurs,
           SUM(role='Investor') AS investors,
           SUM(role='Mentor') AS mentors
       FROM users" . ($hasLastLoginColumn ? " WHERE last_login IS NOT NULL" : "");

$stats_row = $conn->query($stats_sql)->fetch_assoc();

$user_stats = [
    'total' => $stats_row['total'],
    'entrepreneurs' => $stats_row['entrepreneurs'],
    'investors' => $stats_row['investors'],
    'mentors' => $stats_row['mentors'],
];
?>

<div class="main">
    <div class="users-container">
        <!-- Header with Statistics -->
        <section class="stats-section">
            <div class="users-stats-header">
                <h2>👥 Community Members & Ecosystem</h2>
                <p class="stats-description">Entrepreneurs, Investors & Mentors Building African Startups</p>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $user_stats['total']; ?></div>
                        <div class="stat-box-label">Total Users</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $user_stats['entrepreneurs']; ?></div>
                        <div class="stat-box-label">Entrepreneurs</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $user_stats['investors']; ?></div>
                        <div class="stat-box-label">Investors</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $user_stats['mentors']; ?></div>
                        <div class="stat-box-label">Mentors</div>
                    </div>
                </div>
            </div>
        </section>

        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <?php if($action_message) echo "<p class='success'>$action_message</p>"; ?>

        <!-- Users Grid -->
        <section class="users-table-section">
            <h3 class="users-section-title">🌐 Community Profiles</h3>
            <?php if($result && $result->num_rows > 0): ?>
            <div class="users-grid">
                <?php while($row = $result->fetch_assoc()): ?>
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($row['full_name'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars($row['full_name']); ?>
                                <?php if($row['is_admin']==1) echo '<span class="admin-badge">ADMIN</span>'; ?>
                            </h4>
                            <p><?php echo htmlspecialchars($row['email']); ?></p>
                            <p class="user-role-label">Role: <?php echo htmlspecialchars($row['role'] ?: 'Entrepreneur'); ?></p>
                        </div>
                    </div>
                    <div class="user-details">
                        <div class="user-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo (int)$row['ideas_count']; ?></span>
                                <span class="stat-label">Ideas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo (int)$row['reviews_count']; ?></span>
                                <span class="stat-label">Reviews</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $row['reviews_count'] > 0 ? round($row['avg_rating'],1) : '-'; ?></span>
                                <span class="stat-label">Avg Rating</span>
                            </div>
                        </div>
                        <div class="user-role">
                            <form method="POST" style="margin:0; padding:0;">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="action" value="update_user">
                                <input type="hidden" name="user_id" value="<?php echo (int)$row['user_id']; ?>">

                                <label style="font-weight:600; font-size:12px;">Role</label>
                                <select name="role" style="width:100%; padding:6px; margin-bottom:8px;">
                                    <option value="Entrepreneur" <?php echo $row['role'] === 'Entrepreneur' ? 'selected' : ''; ?>>Entrepreneur</option>
                                    <option value="Investor" <?php echo $row['role'] === 'Investor' ? 'selected' : ''; ?>>Investor</option>
                                    <option value="Mentor" <?php echo $row['role'] === 'Mentor' ? 'selected' : ''; ?>>Mentor</option>
                                </select>

                                <label style="font-size:12px; display:block; margin-bottom:6px;">
                                    <input type="checkbox" name="is_admin" value="1" <?php echo $row['is_admin'] == 1 ? 'checked' : ''; ?>> Admin
                                </label>
                                <label style="font-size:12px; display:block; margin-bottom:10px;">
                                    <input type="checkbox" name="is_active" value="1" <?php echo isset($row['is_active']) && $row['is_active'] == 1 ? 'checked' : ''; ?>> Active
                                </label>

                                <button type="submit" style="padding:6px 12px; font-size:12px;">Save</button>
                            </form>
                        </div>
                        <div class="user-joined">
                            <span class="text-muted-small">Joined <?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
                <p class="empty-message">No users found.</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include 'footer.php'; ?>
