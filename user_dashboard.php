<?php
// user_dashboard.php - Dashboard for Entrepreneurs
session_start();
include 'db.php';
include 'header.php';
include 'sidebar.php';

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Check if user is Entrepreneur
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if($user_role !== 'entrepreneur'){
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get user's ideas with status
$ideas_query = "SELECT post_id, title, problem_statement, status, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($ideas_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ideas_result = $stmt->get_result();
$ideas = [];
while($row = $ideas_result->fetch_assoc()){
    $ideas[] = $row;
}

// Get feedback for approved ideas
$feedback_query = "SELECT mf.feedback_text, mf.created_at, u.full_name as mentor_name, p.title
                   FROM mentor_feedback mf
                   JOIN users u ON mf.mentor_id = u.user_id
                   JOIN posts p ON mf.idea_id = p.post_id
                   WHERE p.user_id = ? AND p.status = 'approved'
                   ORDER BY mf.created_at DESC";
$stmt = $conn->prepare($feedback_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$feedback_result = $stmt->get_result();
$feedbacks = [];
while($row = $feedback_result->fetch_assoc()){
    $feedbacks[] = $row;
}
?>

<div class="main">
    <div class="welcome">
        <h2>Welcome, <?php echo htmlspecialchars($full_name); ?> - Entrepreneur Dashboard</h2>
        <p>Manage your ideas and track their progress through the review process.</p>
    </div>

    <div class="dashboard-grid">
        <!-- My Ideas Section -->
        <div class="dashboard-card">
            <h3>💡 My Ideas</h3>
            <?php if(empty($ideas)): ?>
                <p>You haven't submitted any ideas yet. <a href="post_idea.php">Submit your first idea</a></p>
            <?php else: ?>
                <div class="ideas-list">
                    <?php foreach($ideas as $idea): ?>
                        <div class="idea-item">
                            <div class="idea-header">
                                <h4><?php echo htmlspecialchars($idea['title']); ?></h4>
                                <span class="status-badge status-<?php echo $idea['status']; ?>">
                                    <?php echo ucfirst($idea['status']); ?>
                                </span>
                            </div>
                            <p><?php echo substr(htmlspecialchars($idea['problem_statement']), 0, 100) . '...'; ?></p>
                            <small>Submitted: <?php echo date('M j, Y', strtotime($idea['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="post_idea.php" class="btn-primary">Submit New Idea</a>
            <?php endif; ?>
        </div>

        <!-- Mentor Feedback Section -->
        <div class="dashboard-card">
            <h3>📝 Mentor Feedback</h3>
            <?php if(empty($feedbacks)): ?>
                <p>No feedback yet. Your approved ideas will receive feedback from mentors.</p>
            <?php else: ?>
                <div class="feedback-list">
                    <?php foreach($feedbacks as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <strong><?php echo htmlspecialchars($feedback['mentor_name']); ?></strong>
                                <small><?php echo date('M j, Y', strtotime($feedback['created_at'])); ?></small>
                            </div>
                            <p><em>Idea: <?php echo htmlspecialchars($feedback['title']); ?></em></p>
                            <p><?php echo htmlspecialchars($feedback['feedback_text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <h3>⚡ Quick Actions</h3>
            <div class="action-buttons">
                <a href="post_idea.php" class="action-btn">📝 Submit New Idea</a>
                <a href="reviews.php" class="action-btn">⭐ View Reviews</a>
                <a href="messages.php" class="action-btn">💬 Messages</a>
            </div>
        </div>
    </div>
</div>

<style>
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

.dashboard-card h3 {
    color: #203a43;
    margin-top: 0;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}

.ideas-list, .feedback-list {
    margin: 15px 0;
}

.idea-item, .feedback-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    background: #fafafa;
}

.idea-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.idea-header h4 {
    margin: 0;
    color: #203a43;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }

.feedback-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

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

.btn-primary {
    display: inline-block;
    padding: 10px 20px;
    background: linear-gradient(135deg, #203a43 0%, #2c5364 100%);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 15px;
}
</style>