<?php
// investor_dashboard.php - Dashboard for Investors
session_start();
include 'db.php';
include 'header.php';
include 'sidebar.php';

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Check if user is Investor
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if($user_role !== 'investor'){
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Check if new tables exist
$investor_reviews_exists = $conn->query("SHOW TABLES LIKE 'investor_reviews'")->num_rows > 0;
$status_column_exists = $conn->query("SHOW COLUMNS FROM posts LIKE 'status'")->num_rows > 0;

// Handle review submission
if(isset($_POST['submit_review'])){
    $idea_id = $_POST['idea_id'];
    $expected_return = trim($_POST['expected_return']);
    $cost_estimate = trim($_POST['cost_estimate']);
    $decision = $_POST['decision'];
    $comment = trim($_POST['comment']);

    if ($investor_reviews_exists) {
        // Check if already reviewed
        $check_query = "SELECT id FROM investor_reviews WHERE idea_id = ? AND investor_id = ?";
        $stmt = $conn->prepare($check_query);
        if ($stmt) {
            $stmt->bind_param("ii", $idea_id, $user_id);
            $stmt->execute();
            if($stmt->get_result()->num_rows == 0){
                // Insert review
                $insert_query = "INSERT INTO investor_reviews (idea_id, investor_id, expected_return, cost_estimate, decision, comment) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                if ($stmt) {
                    $stmt->bind_param("iissss", $idea_id, $user_id, $expected_return, $cost_estimate, $decision, $comment);
                    $stmt->execute();

                    // Update idea status if status column exists
                    if ($status_column_exists) {
                        $update_query = "UPDATE posts SET status = ? WHERE post_id = ?";
                        $stmt = $conn->prepare($update_query);
                        if ($stmt) {
                            $stmt->bind_param("si", $decision, $idea_id);
                            $stmt->execute();
                        }
                    }
                }
            }
        }
    }
}

// Get all ideas for review
$status_column_exists = $conn->query("SHOW COLUMNS FROM posts LIKE 'status'")->num_rows > 0;

if ($investor_reviews_exists) {
    $ideas_query = "SELECT p.post_id, p.title, p.problem_statement, p.status, p.created_at,
                           u.full_name as entrepreneur_name,
                           ir.expected_return, ir.cost_estimate, ir.decision, ir.comment, ir.created_at as review_date
                    FROM posts p
                    JOIN users u ON p.user_id = u.user_id
                    LEFT JOIN investor_reviews ir ON p.post_id = ir.idea_id AND ir.investor_id = ?
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
    // Fallback: show all posts without reviews
    $ideas_query = "SELECT p.post_id, p.title, p.description as problem_statement, 'pending' as status, p.created_at,
                           u.full_name as entrepreneur_name,
                           NULL as expected_return, NULL as cost_estimate, NULL as decision, NULL as comment, NULL as review_date
                    FROM posts p
                    JOIN users u ON p.user_id = u.user_id
                    ORDER BY p.created_at DESC";
    $ideas_result = $conn->query($ideas_query);
    $ideas = [];
    if ($ideas_result) {
        while($row = $ideas_result->fetch_assoc()){
            $ideas[] = $row;
        }
    }
}
?>

<div class="main">
    <div class="welcome">
        <h2>Welcome, <?php echo htmlspecialchars($full_name); ?> - Investor Dashboard</h2>
        <p>Review submitted ideas and make investment decisions.</p>
    </div>

    <div class="dashboard-grid">
        <!-- Ideas for Review -->
        <div class="dashboard-card full-width">
            <h3>💼 Ideas for Review</h3>
            <?php if(empty($ideas)): ?>
                <p>No ideas available for review.</p>
            <?php else: ?>
                <div class="ideas-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Idea Title</th>
                                <th>Entrepreneur</th>
                                <th>Problem Statement</th>
                                <th>Status</th>
                                <th>Your Review</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ideas as $idea): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($idea['title']); ?></td>
                                    <td><?php echo htmlspecialchars($idea['entrepreneur_name']); ?></td>
                                    <td><?php echo substr(htmlspecialchars($idea['problem_statement']), 0, 100) . '...'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $idea['status']; ?>">
                                            <?php echo ucfirst($idea['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($idea['decision']): ?>
                                            <div class="review-summary">
                                                <strong><?php echo ucfirst($idea['decision']); ?></strong><br>
                                                <small>Return: <?php echo htmlspecialchars($idea['expected_return']); ?></small><br>
                                                <small>Cost: <?php echo htmlspecialchars($idea['cost_estimate']); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <em>Not reviewed</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!$idea['decision']): ?>
                                            <button onclick="openReviewModal(<?php echo $idea['post_id']; ?>, '<?php echo htmlspecialchars($idea['title']); ?>')" class="btn-review">Review</button>
                                        <?php else: ?>
                                            <button onclick="openReviewModal(<?php echo $idea['post_id']; ?>, '<?php echo htmlspecialchars($idea['title']); ?>')" class="btn-update">Update</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeReviewModal()">&times;</span>
        <h2>Review Idea: <span id="modalIdeaTitle"></span></h2>
        <form method="POST">
            <input type="hidden" name="idea_id" id="modalIdeaId">
            
            <label>Expected Return:</label>
            <input type="text" name="expected_return" placeholder="e.g., 25% ROI in 3 years" required>
            
            <label>Cost Estimate:</label>
            <input type="text" name="cost_estimate" placeholder="e.g., $50,000 - $100,000" required>
            
            <label>Decision:</label>
            <select name="decision" required>
                <option value="">Select Decision</option>
                <option value="approved">Approve</option>
                <option value="rejected">Reject</option>
            </select>
            
            <label>Comments:</label>
            <textarea name="comment" placeholder="Additional comments or requirements..." required></textarea>
            
            <button type="submit" name="submit_review" class="btn-primary">Submit Review</button>
        </form>
    </div>
</div>

<script>
function openReviewModal(ideaId, title) {
    document.getElementById('modalIdeaId').value = ideaId;
    document.getElementById('modalIdeaTitle').textContent = title;
    document.getElementById('reviewModal').style.display = 'block';
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('reviewModal')) {
        closeReviewModal();
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

.ideas-table table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.ideas-table th, .ideas-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.ideas-table th {
    background: #f8f9fa;
    font-weight: bold;
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

.btn-review, .btn-update {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.btn-review { background: #007bff; color: white; }
.btn-update { background: #ffc107; color: black; }

.review-summary {
    font-size: 12px;
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
    margin: 5% auto;
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
    margin-top: 15px;
    font-weight: bold;
    color: #203a43;
}

.modal-content input, .modal-content select, .modal-content textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.modal-content textarea {
    height: 80px;
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