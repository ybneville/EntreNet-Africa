<?php
// mentor_dashboard.php - Dashboard for Mentors
session_start();
include 'db.php';
include 'header.php';
include 'sidebar.php';

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Check if user is Mentor
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if($user_role !== 'mentor'){
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Check if new tables exist
$mentor_feedback_exists = $conn->query("SHOW TABLES LIKE 'mentor_feedback'")->num_rows > 0;
$investor_reviews_exists = $conn->query("SHOW TABLES LIKE 'investor_reviews'")->num_rows > 0;
$status_column_exists = $conn->query("SHOW COLUMNS FROM posts LIKE 'status'")->num_rows > 0;

// Handle feedback submission
if(isset($_POST['submit_feedback'])){
    $idea_id = $_POST['idea_id'];
    $feedback_text = trim($_POST['feedback_text']);

    if(!empty($feedback_text)){
        if ($mentor_feedback_exists) {
            // Check if already provided feedback
            $check_query = "SELECT id FROM mentor_feedback WHERE idea_id = ? AND mentor_id = ?";
            $stmt = $conn->prepare($check_query);
            if ($stmt) {
                $stmt->bind_param("ii", $idea_id, $user_id);
                $stmt->execute();
                if($stmt->get_result()->num_rows == 0){
                    // Insert feedback
                    $insert_query = "INSERT INTO mentor_feedback (idea_id, mentor_id, feedback_text) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($insert_query);
                    if ($stmt) {
                        $stmt->bind_param("iis", $idea_id, $user_id, $feedback_text);
                        $stmt->execute();
                    }
                }
            }
        }
    }
}

// Get approved ideas for feedback
if ($mentor_feedback_exists && $investor_reviews_exists && $status_column_exists) {
    $ideas_query = "SELECT p.post_id, p.title, p.problem_statement, p.objectives, p.created_at,
                           u.full_name as entrepreneur_name,
                           ir.expected_return, ir.cost_estimate, ir.decision,
                           mf.feedback_text, mf.created_at as feedback_date
                    FROM posts p
                    JOIN users u ON p.user_id = u.user_id
                    LEFT JOIN investor_reviews ir ON p.post_id = ir.idea_id
                    LEFT JOIN mentor_feedback mf ON p.post_id = mf.idea_id AND mf.mentor_id = ?
                    WHERE p.status = 'approved'
                    ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($ideas_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $ideas_result = $stmt->get_result();
        $ideas = [];
        while($row = $ideas_result->fetch_assoc()){
            $ideas[] = $row;
        }
    } else {
        $ideas = [];
    }
} else {
    // Fallback: show no ideas until migration is run
    $ideas = [];
}
?>

<div class="main">
    <div class="welcome">
        <h2>Welcome, <?php echo htmlspecialchars($full_name); ?> - Mentor Dashboard</h2>
        <p>Provide guidance and feedback to approved entrepreneurial ideas.</p>
    </div>

    <div class="dashboard-grid">
        <!-- Approved Ideas for Feedback -->
        <div class="dashboard-card full-width">
            <h3>✅ Approved Ideas for Feedback</h3>
            <?php if(empty($ideas)): ?>
                <p>No approved ideas available for feedback at this time.</p>
            <?php else: ?>
                <div class="ideas-feedback">
                    <?php foreach($ideas as $idea): ?>
                        <div class="idea-feedback-card">
                            <div class="idea-header">
                                <h4><?php echo htmlspecialchars($idea['title']); ?></h4>
                                <span class="entrepreneur">by <?php echo htmlspecialchars($idea['entrepreneur_name']); ?></span>
                            </div>
                            
                            <div class="idea-content">
                                <div class="problem-statement">
                                    <strong>Problem Statement:</strong>
                                    <p><?php echo htmlspecialchars($idea['problem_statement']); ?></p>
                                </div>
                                
                                <div class="objectives">
                                    <strong>Objectives:</strong>
                                    <p><?php echo htmlspecialchars($idea['objectives']); ?></p>
                                </div>
                                
                                <div class="investor-review">
                                    <strong>Investor Assessment:</strong>
                                    <ul>
                                        <li>Expected Return: <?php echo htmlspecialchars($idea['expected_return'] ?: 'Not specified'); ?></li>
                                        <li>Cost Estimate: <?php echo htmlspecialchars($idea['cost_estimate'] ?: 'Not specified'); ?></li>
                                        <li>Decision: <span class="decision-<?php echo $idea['decision']; ?>"><?php echo ucfirst($idea['decision'] ?: 'Pending'); ?></span></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="feedback-section">
                                <?php if($idea['feedback_text']): ?>
                                    <div class="existing-feedback">
                                        <strong>Your Previous Feedback:</strong>
                                        <p><?php echo htmlspecialchars($idea['feedback_text']); ?></p>
                                        <small>Provided on <?php echo date('M j, Y', strtotime($idea['feedback_date'])); ?></small>
                                        <button onclick="editFeedback(<?php echo $idea['post_id']; ?>, '<?php echo htmlspecialchars($idea['title']); ?>', '<?php echo htmlspecialchars($idea['feedback_text']); ?>')" class="btn-edit">Edit Feedback</button>
                                    </div>
                                <?php else: ?>
                                    <button onclick="openFeedbackModal(<?php echo $idea['post_id']; ?>, '<?php echo htmlspecialchars($idea['title']); ?>')" class="btn-feedback">Provide Feedback</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeFeedbackModal()">&times;</span>
        <h2>Provide Feedback: <span id="modalIdeaTitle"></span></h2>
        <form method="POST">
            <input type="hidden" name="idea_id" id="modalIdeaId">
            
            <label>Your Feedback:</label>
            <textarea name="feedback_text" id="modalFeedbackText" placeholder="Share your insights, recommendations, and suggestions for improvement..." required></textarea>
            
            <button type="submit" name="submit_feedback" class="btn-primary">Submit Feedback</button>
        </form>
    </div>
</div>

<script>
function openFeedbackModal(ideaId, title) {
    document.getElementById('modalIdeaId').value = ideaId;
    document.getElementById('modalIdeaTitle').textContent = title;
    document.getElementById('modalFeedbackText').value = '';
    document.getElementById('feedbackModal').style.display = 'block';
}

function editFeedback(ideaId, title, existingFeedback) {
    document.getElementById('modalIdeaId').value = ideaId;
    document.getElementById('modalIdeaTitle').textContent = title;
    document.getElementById('modalFeedbackText').value = existingFeedback;
    document.getElementById('feedbackModal').style.display = 'block';
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('feedbackModal')) {
        closeFeedbackModal();
    }
}
</script>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
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

.ideas-feedback {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.idea-feedback-card {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 20px;
    background: #fafafa;
}

.idea-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.idea-header h4 {
    margin: 0;
    color: #203a43;
}

.entrepreneur {
    color: #666;
    font-size: 14px;
}

.idea-content {
    margin-bottom: 20px;
}

.problem-statement, .objectives, .investor-review {
    margin-bottom: 15px;
}

.problem-statement p, .objectives p {
    margin: 5px 0 0 0;
    color: #555;
    line-height: 1.6;
}

.investor-review ul {
    margin: 5px 0 0 0;
    padding-left: 20px;
}

.investor-review li {
    margin-bottom: 5px;
    color: #555;
}

.decision-approved { color: #28a745; font-weight: bold; }
.decision-rejected { color: #dc3545; font-weight: bold; }
.decision-pending { color: #ffc107; font-weight: bold; }

.feedback-section {
    border-top: 1px solid #e0e0e0;
    padding-top: 15px;
}

.existing-feedback {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #2c5364;
}

.existing-feedback p {
    margin: 10px 0;
    color: #555;
    line-height: 1.6;
}

.btn-feedback, .btn-edit {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    margin-top: 10px;
}

.btn-feedback { background: #28a745; color: white; }
.btn-edit { background: #ffc107; color: black; }

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
    margin: 5% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
}

.modal-content h2 {
    color: #203a43;
    margin-top: 0;
}

.modal-content label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    color: #203a43;
}

.modal-content textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    height: 120px;
    resize: vertical;
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