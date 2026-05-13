<?php
// Reports listing page
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';
include 'header.php';
include 'sidebar.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_id, full_name, role, is_admin FROM users WHERE user_id = ? LIMIT 1");
if ($stmt === false) {
    echo "<p style='color:red;'>Error preparing user query: " . htmlspecialchars($conn->error) . "</p>";
    include 'footer.php';
    exit();
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_res = $stmt->get_result();
if (!$user_res || $user_res->num_rows !== 1) {
    echo "<p style='color:red;'>User not found.</p>";
    include 'footer.php';
    exit();
}
$user = $user_res->fetch_assoc();
$is_admin = isset($user['is_admin']) && (int)$user['is_admin'] === 1;

// Admin actions (resolve reports)
$report_action_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resolve_report') {
    if (!$is_admin) {
        $report_action_error = 'You are not authorized to perform that action.';
    } elseif (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $report_action_error = 'Invalid request. Please try again.';
    } else {
        $report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
        if ($report_id > 0) {
            $stmt = $conn->prepare("UPDATE reports SET status = 'Resolved', resolved_at = NOW() WHERE report_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $report_id);
                if (!$stmt->execute()) {
                    $report_action_error = 'Failed to resolve report: ' . htmlspecialchars($conn->error);
                }
            } else {
                $report_action_error = 'Failed to prepare action.';
            }
        }
    }
}

// Get platform statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_ideas = $conn->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
$entrepreneurs = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='Entrepreneur'")->fetch_assoc()['count'];
$investors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='Investor'")->fetch_assoc()['count'];
$mentors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='Mentor'")->fetch_assoc()['count'];
$avg_rating = $conn->query("SELECT COALESCE(AVG(rating), 0) as avg FROM reviews")->fetch_assoc()['avg'];

// Build queries for reports/activity only if the reports table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'reports'");
if($tableCheck && $tableCheck->num_rows > 0) {
    // determine if the reports table supports status tracking
    $statusCheck = $conn->query("SHOW COLUMNS FROM reports LIKE 'status'");
    $hasStatus = ($statusCheck && $statusCheck->num_rows > 0);

    // the table exists, proceed with normal query construction
    if ($is_admin) {
        $query = "SELECT r.report_id, r.report_text, r.reported_at, u.full_name AS reporter_name, u.role, p.title AS idea_title";
        if ($hasStatus) {
            $query .= ", COALESCE(r.status, 'Open') AS status, r.resolved_at";
        } else {
            $query .= ", 'Open' AS status, NULL AS resolved_at";
        }
        $query .= " FROM reports r
                  JOIN users u ON r.user_id = u.user_id
                  LEFT JOIN posts p ON r.post_id = p.post_id
                  ORDER BY r.reported_at DESC";

        $stmt_reports = $conn->prepare($query);
        if ($stmt_reports === false) {
            echo "<p style='color:red;'>Error preparing reports query: " . htmlspecialchars($conn->error) . "</p>";
            include 'footer.php';
            exit();
        }
        $stmt_reports->execute();
        $reports_result = $stmt_reports->get_result();
    } else {
        $query = "SELECT r.report_id, r.report_text, r.reported_at, u.full_name AS reporter_name, u.role, p.title AS idea_title";
        if ($hasStatus) {
            $query .= ", COALESCE(r.status, 'Open') AS status, r.resolved_at";
        } else {
            $query .= ", 'Open' AS status, NULL AS resolved_at";
        }
        $query .= " FROM reports r
                  JOIN users u ON r.user_id = u.user_id
                  LEFT JOIN posts p ON r.post_id = p.post_id
                  WHERE r.user_id = ?
                  ORDER BY r.reported_at DESC";

        $stmt_reports = $conn->prepare($query);
        if ($stmt_reports === false) {
            error_log('reports.php prepare failed: ' . $conn->error);
            $safe = $conn->real_escape_string($user_id);
            $fallback = "SELECT r.report_id, r.report_text, r.reported_at, u.full_name AS reporter_name, u.role, p.title AS idea_title";
            if ($hasStatus) {
                $fallback .= ", COALESCE(r.status, 'Open') AS status, r.resolved_at";
            } else {
                $fallback .= ", 'Open' AS status, NULL AS resolved_at";
            }
            $fallback .= " FROM reports r JOIN users u ON r.user_id = u.user_id LEFT JOIN posts p ON r.post_id = p.post_id
                         WHERE r.user_id = " . intval($safe) . " ORDER BY r.reported_at DESC";
            $reports_result = $conn->query($fallback);
        } else {
            $stmt_reports->bind_param('i', $user_id);
            $stmt_reports->execute();
            $reports_result = $stmt_reports->get_result();
        }
    }
} else {
    // reports table is missing, skip querying and set result to null
    $reports_result = null;
}

?>



<div class="main">
    <div class="reports-container">
        <!-- Statistics Dashboard (unchanged) -->
        <section class="stats-section">
            <div class="stats-dashboard">
                <h2>📊 Platform Engagement & Activity Report</h2>
                <p class="stats-description">Track community growth, engagement, and ecosystem development</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-trend">Active Community</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💡</div>
                        <div class="stat-number"><?php echo $total_ideas; ?></div>
                        <div class="stat-label">Ideas Submitted</div>
                        <div class="stat-trend">Innovation Pipeline</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-number"><?php echo round($avg_rating, 1); ?>/5</div>
                        <div class="stat-label">Avg Community Rating</div>
                        <div class="stat-trend">Quality Feedback</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💬</div>
                        <div class="stat-number"><?php echo $total_reviews; ?></div>
                        <div class="stat-label">Reviews Given</div>
                        <div class="stat-trend">Engagement Level</div>
                    </div>
                </div>

                <!-- Role Breakdown (same) -->
                <div class="role-breakdown">
                    <h4>🎯 Ecosystem Breakdown</h4>
                    <div class="role-item role-entrepreneur">
                        <span class="role-label">Entrepreneurs</span>
                        <div class="role-bar">
                            <div class="role-bar-fill" style="width: <?php echo $total_users > 0 ? round(($entrepreneurs / $total_users) * 100) : 0; ?>%"></div>
                        </div>
                        <span class="role-count"><?php echo $entrepreneurs; ?></span>
                    </div>
                    <div class="role-item role-investor">
                        <span class="role-label">Investors</span>
                        <div class="role-bar">
                            <div class="role-bar-fill" style="width: <?php echo $total_users > 0 ? round(($investors / $total_users) * 100) : 0; ?>%"></div>
                        </div>
                        <span class="role-count"><?php echo $investors; ?></span>
                    </div>
                    <div class="role-item role-mentor">
                        <span class="role-label">Mentors</span>
                        <div class="role-bar">
                            <div class="role-bar-fill" style="width: <?php echo $total_users > 0 ? round(($mentors / $total_users) * 100) : 0; ?>%"></div>
                        </div>
                        <span class="role-count"><?php echo $mentors; ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Activity Reports as cards -->
        <section class="activity-section">
            <h3 class="section-title">📋 Recent Activity & Engagement</h3>
            <p class="section-description">Monitor community interactions and platform activity</p>
            <?php if (!empty($report_action_error)): ?>
                <div class="error"><?php echo esc($report_action_error); ?></div>
            <?php endif; ?>
            <?php if ($reports_result && $reports_result->num_rows > 0): ?>
                <div class="reports-grid">
                    <?php while ($row = $reports_result->fetch_assoc()): ?>
                        <div class="report-card">
                            <div class="report-header">
                                <div class="reporter-info">
                                    <strong><?php echo htmlspecialchars($row['reporter_name']); ?></strong>
                                    <span class="role-badge <?php echo strtolower($row['role']); ?> small">
                                        <?php echo htmlspecialchars($row['role']); ?>
                                    </span>
                                </div>
                                <div class="report-heading-right">
                                    <span class="status-badge" style="background:#f0f0f0; color:#333; padding:4px 10px; border-radius:12px; font-size:12px;">
                                        <?php echo htmlspecialchars($row['status'] ?? 'Open'); ?>
                                    </span>
                                    <?php if ($is_admin && isset($row['status']) && strtolower($row['status']) === 'open'): ?>
                                        <form method="POST" style="display:inline-block; margin-left:10px;">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="action" value="resolve_report">
                                            <input type="hidden" name="report_id" value="<?php echo (int)$row['report_id']; ?>">
                                            <button type="submit" class="view-btn" style="padding:5px 10px; font-size:12px;">Mark Resolved</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="report-date"><?php echo date('M d, Y H:i', strtotime($row['reported_at'])); ?></div>
                            </div>
                            <div class="report-body">
                                <div class="report-meta">
                                    <span class="report-label">Idea</span>
                                    <span class="report-value"><?php echo htmlspecialchars($row['idea_title'] ?: 'General Activity'); ?></span>
                                </div>
                                <div class="report-meta">
                                    <span class="report-label">Details</span>
                                    <span class="report-value"><?php echo htmlspecialchars($row['report_text']); ?></span>
                                </div>
                                <?php if (!empty($row['status']) && strtolower($row['status']) === 'resolved' && !empty($row['resolved_at'])): ?>
                                <div class="report-meta">
                                    <span class="report-label">Resolved</span>
                                    <span class="report-value"><?php echo date('M d, Y H:i', strtotime($row['resolved_at'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">No activity reports yet. Start engaging with the community! 🚀</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include 'footer.php'; ?>
