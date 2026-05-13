<?php
session_start();
include 'db.php';

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Include header and sidebar
include 'header.php';
include 'sidebar.php';

// Initialize variables
$error = '';
$success = '';

// List of business sectors
$sectors = [
    'Agriculture',
    'Finance',
    'Real Estate',
    'Retail/E-commerce',
    'Technology',
    'Transportation & Logistics'
];

// Handle form submission//SENDS POSTED IDEA TO THE DATABASE
if(isset($_POST['submit'])){
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $problem_statement = trim($_POST['problem_statement']);
    $sector = $_POST['sector'];
    $objectives = trim($_POST['objectives']);
    
    // File upload handling
    $filename = '';
    if(isset($_FILES['attachment']) && $_FILES['attachment']['name'] != ''){
        $target_dir = "uploads/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = time().'_'.basename($_FILES['attachment']['name']);
        $target_file = $target_dir.$filename;
        if(!move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)){
            $error = "Failed to upload file.";
        }
    }

    if(!$error){
        // Insert post into database
        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, problem_statement, sector, objectives, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $user_id, $title, $problem_statement, $sector, $objectives);
        if($stmt->execute()){
            $success = "Idea submitted successfully!";
        } else {
            $error = "Failed to submit idea: ".$conn->error;
        }
    }
}

// Fetch all ideas with statistics
$ideas_query = "SELECT p.post_id, p.title, p.problem_statement, p.sector, p.objectives, p.created_at, 
                       u.user_id, u.full_name, u.role,
                       COUNT(DISTINCT r.review_id) as review_count,
                       COALESCE(AVG(r.rating), 0) as avg_rating
                FROM posts p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN reviews r ON p.post_id = r.post_id
                GROUP BY p.post_id
                ORDER BY p.created_at DESC";

$ideas_result = $conn->query($ideas_query);
$ideas = [];
if($ideas_result){
    while($row = $ideas_result->fetch_assoc()){
        $ideas[] = $row;
    }
}
?>

<style>
/* Form styling */
.idea-container { max-width: 1200px; margin: 0 auto; }
.form-section { background: linear-gradient(135deg, rgba(32, 58, 67, 0.05) 0%, rgba(44, 83, 100, 0.05) 100%); padding: 30px; border-radius: 12px; margin-bottom: 40px; }
.form-section h3 { color: #203a43; margin-top: 0; font-size: 22px; border-left: 4px solid #2c5364; padding-left: 12px; }
form { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
label { font-weight: 600; color: #203a43; display: block; margin-top: 15px; margin-bottom: 6px; }
input[type=text], textarea, select, input[type=file] { width: 100%; padding: 11px; margin-bottom: 0; border: 1px solid #ddd; border-radius: 6px; font-family: Arial, sans-serif; }
textarea { resize: vertical; min-height: 100px; }
button { padding: 12px 25px; background: linear-gradient(135deg, #203a43 0%, #2c5364 100%); color: white; border: none; cursor: pointer; border-radius: 6px; font-weight: 600; margin-top: 20px; transition: all 0.3s ease; }
button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(32, 58, 67, 0.3); }
.error { background: #ffe0e0; color: #b30000; padding: 12px; margin-bottom: 15px; border-radius: 6px; border-left: 4px solid #b30000; }
.success { background: #e6ffe6; color: #006600; padding: 12px; margin-bottom: 15px; border-radius: 6px; border-left: 4px solid #006600; }

/* Ideas Gallery */
.ideas-section { margin-top: 50px; }
.ideas-section h3 { color: #203a43; font-size: 24px; margin-bottom: 30px; border-left: 4px solid #2c5364; padding-left: 12px; }
.ideas-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 25px; }
.idea-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.3s ease; cursor: pointer; border-top: 4px solid #2c5364; }
.idea-card:hover { transform: translateY(-6px); box-shadow: 0 8px 25px rgba(32, 58, 67, 0.2); }
.idea-header { padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #ddd; }
.idea-title { color: #203a43; font-size: 18px; font-weight: 700; margin: 0 0 8px 0; }
.entrepreneur-info { display: flex; align-items: center; gap: 10px; }
.entrepreneur-badge { background: #ffc107; color: #333; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.entrepreneur-name { color: #555; font-size: 14px; }
.idea-body { padding: 20px; }
.sector-badge { display: inline-block; background: #e8f4f8; color: #2c5364; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 12px; }
.idea-description { color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 15px; }
.idea-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee; }
.feedback-stats { display: flex; gap: 20px; font-size: 13px; }
.stat { display: flex; align-items: center; gap: 6px; color: #666; }
.stat-number { color: #2c5364; font-weight: 700; font-size: 16px; }
.stars { color: #ffc107; }
.view-btn { background: #2c5364; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.view-btn:hover { background: #203a43; }

/* Modal */
.modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.modal-content { background-color: white; margin: 5% auto; padding: 40px; border-radius: 12px; width: 85%; max-width: 700px; max-height: 85vh; overflow-y: auto; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3); animation: slideIn 0.3s ease; }
@keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-content h2 { color: #203a43; margin-top: 0; margin-bottom: 20px; border-bottom: 3px solid #2c5364; padding-bottom: 15px; }
.modal-field { margin-bottom: 20px; }
.modal-label { font-weight: 700; color: #2c5364; margin-bottom: 6px; }
.modal-value { color: #555; line-height: 1.7; }
.close { color: #999; float: right; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s ease; }
.close:hover { color: #203a43; }

@media (max-width: 768px) {
    .ideas-gallery { grid-template-columns: 1fr; }
    .form-section { padding: 20px; }
}
</style>

<div class="main">
    <div class="idea-container">
        <!-- Submission Form Section -->
        <div class="form-section">
            <h3>📝 Submit Your Brilliant Idea</h3>
            
            <?php if($error) echo "<p class='error'>$error</p>"; ?>
            <?php if($success) echo "<p class='success'>✓ $success</p>"; ?>

            <form method="POST" enctype="multipart/form-data">
                <label>Idea Title *</label>
                <input type="text" name="title" placeholder="What's your innovative idea?" required>

                <label>Business Sector *</label>
                <select name="sector" required>
                    <option value="">Select Your Sector</option>
                    <?php foreach($sectors as $s): ?>
                        <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Problem Statement *</label>
                <textarea name="problem_statement" placeholder="Describe your idea in detail. What problem does it solve? How will it work?" required></textarea>

                <label>Objectives *</label>
                <textarea name="objectives" placeholder="What are your goals? What impact do you want to create? What milestones are you targeting?" required></textarea>

                <label>Supporting Document (optional)</label>
                <input type="file" name="attachment">

                <button type="submit" name="submit">Submit Idea</button>
            </form>
        </div>

        <!-- Ideas Showcase Section -->
        <div class="ideas-section">
            <h3>💡 Community Ideas & Innovations</h3>
            
            <?php if(!empty($ideas)): ?>
            <div class="ideas-gallery">
                <?php foreach($ideas as $idea): ?>
                <div class="idea-card" onclick="openIdeaModal(<?php echo $idea['post_id']; ?>)">
                    <div class="idea-header">
                        <p class="idea-title"><?php echo htmlspecialchars($idea['title']); ?></p>
                        <div class="entrepreneur-info">
                            <span class="entrepreneur-badge">ENTREPRENEUR</span>
                            <span class="entrepreneur-name"><?php echo htmlspecialchars($idea['full_name']); ?></span>
                        </div>
                    </div>
                    
                    <div class="idea-body">
                        <span class="sector-badge"><?php echo htmlspecialchars($idea['sector']); ?></span>
                        <p class="idea-description"><?php echo substr(htmlspecialchars($idea['problem_statement']), 0, 120) . '...'; ?></p>
                    </div>
                    
                    <div class="idea-footer">
                        <div class="feedback-stats">
                            <div class="stat">
                                <span class="stars">★★★★★</span>
                                <span class="stat-number"><?php echo round($idea['avg_rating'], 1); ?>/5</span>
                            </div>
                            <div class="stat">
                                <span>💬</span>
                                <span class="stat-number"><?php echo (int)$idea['review_count']; ?></span>
                                <span>Reviews</span>
                            </div>
                        </div>
                        <button class="view-btn">View Details →</button>
                    </div>
                </div>

                <!-- Hidden modal content for each idea -->
                <div id="ideaModal<?php echo $idea['post_id']; ?>" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeIdeaModal(<?php echo $idea['post_id']; ?>)">&times;</span>
                        <h2><?php echo htmlspecialchars($idea['title']); ?></h2>
                        
                        <div class="modal-field">
                            <div class="modal-label">👤 Entrepreneur</div>
                            <div class="modal-value"><?php echo htmlspecialchars($idea['full_name']); ?> <span class="entrepreneur-badge margin-left-small">ENTREPRENEUR</span></div>
                        </div>
                        
                        <div class="modal-field">
                            <div class="modal-label">📂 Business Sector</div>
                            <div class="modal-value"><?php echo htmlspecialchars($idea['sector']); ?></div>
                        </div>
                        
                        <div class="modal-field">
                            <div class="modal-label">📋 Full Description</div>
                            <div class="modal-value"><?php echo nl2br(htmlspecialchars($idea['description'])); ?></div>
                        </div>
                        
                        <div class="modal-field">
                            <div class="modal-label">🎯 Goals & Objectives</div>
                            <div class="modal-value"><?php echo nl2br(htmlspecialchars($idea['goals'])); ?></div>
                        </div>
                        
                        <div class="modal-field">
                            <div class="modal-label">⭐ Community Feedback</div>
                            <div class="modal-value">
                                <?php if($idea['review_count'] > 0): ?>
                                    <strong><?php echo round($idea['avg_rating'], 1); ?>/5</strong> (<?php echo (int)$idea['review_count']; ?> reviews from investors & mentors)
                                <?php else: ?>
                                    <em>No reviews yet. Be the first to provide feedback!</em>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="modal-field">
                            <a class="view-btn" href="report_issue.php?post_id=<?php echo $idea['post_id']; ?>">Report Idea</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px; font-size: 16;">No ideas submitted yet. Be the first to share your innovation! 🚀</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function openIdeaModal(ideaId) {
    document.getElementById('ideaModal' + ideaId).style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeIdeaModal(ideaId) {
    document.getElementById('ideaModal' + ideaId).style.display = "none";
    document.body.style.overflow = "auto";
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
        document.body.style.overflow = "auto";
    }
}
</script>

</div> <!-- Close main -->
</div> <!-- Close container -->
</body>
</html>
