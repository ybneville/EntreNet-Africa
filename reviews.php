<?php
// reviews.php shows all project reviews provided by investors/mentors.

// ensure session is active to know who is viewing
session_start();
include 'db.php'; // database connection

// if the user is not logged in, redirect to login page
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// common UI elements
include 'header.php';
include 'sidebar.php';

// placeholders for any error/success messages
$error = '';
$success = '';

$userId = $_SESSION['user_id'];
$userRole = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
$canSubmitReview = in_array($userRole, ['investor', 'mentor'], true) || (isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $reviewText = trim($_POST['review_text']);

        if ($postId <= 0 || $rating < 1 || $rating > 5 || $reviewText === '') {
            $error = 'Please provide a valid project, rating and review text.';
        } else {
            $postStmt = $conn->prepare("SELECT post_id FROM posts WHERE post_id = ? LIMIT 1");
            $postStmt->bind_param('i', $postId);
            $postStmt->execute();
            $postStmt->store_result();

            if ($postStmt->num_rows === 0) {
                $error = 'The selected project could not be found.';
            } else {
                $duplicateStmt = $conn->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND post_id = ? LIMIT 1");
                $duplicateStmt->bind_param('ii', $userId, $postId);
                $duplicateStmt->execute();
                $duplicateStmt->store_result();

                if ($duplicateStmt->num_rows > 0) {
                    $error = 'You have already submitted a review for this project.';
                } else {
                    $insertStmt = $conn->prepare("INSERT INTO reviews (user_id, post_id, rating, review_text, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $insertStmt->bind_param('iiis', $userId, $postId, $rating, $reviewText);

                    if ($insertStmt->execute()) {
                        $success = 'Review added successfully! Thank you for your feedback.';
                    } else {
                        $error = 'Failed to save review: ' . htmlspecialchars($conn->error);
                    }
                }
            }
        }
    }
}

$reviewed_posts = [];
$reviewedStmt = $conn->prepare("SELECT post_id FROM reviews WHERE user_id = ?");
if ($reviewedStmt) {
    $reviewedStmt->bind_param('i', $userId);
    $reviewedStmt->execute();
    $reviewedResult = $reviewedStmt->get_result();
    while ($row = $reviewedResult->fetch_assoc()) {
        $reviewed_posts[(int)$row['post_id']] = true;
    }
}

// Get ideas with their review status
$investor_reviews_exists = $conn->query("SHOW TABLES LIKE 'investor_reviews'")->num_rows > 0;
$mentor_feedback_exists = $conn->query("SHOW TABLES LIKE 'mentor_feedback'")->num_rows > 0;
$status_column_exists = $conn->query("SHOW COLUMNS FROM posts LIKE 'status'")->num_rows > 0;

if ($investor_reviews_exists && $mentor_feedback_exists && $status_column_exists) {
    $projects_sql = "SELECT p.post_id, p.title, p.problem_statement, p.status, p.created_at,
                           u.full_name as entrepreneur_name,
                           ir.expected_return, ir.cost_estimate, ir.decision as investor_decision, ir.comment as investor_comment,
                           mf.feedback_text, mf.created_at as feedback_date,
                           m.full_name as mentor_name
                    FROM posts p
                    JOIN users u ON p.user_id = u.user_id
                    LEFT JOIN investor_reviews ir ON p.post_id = ir.idea_id
                    LEFT JOIN mentor_feedback mf ON p.post_id = mf.idea_id
                    LEFT JOIN users m ON mf.mentor_id = m.user_id
                    ORDER BY p.created_at DESC";
    $projects_result = $conn->query($projects_sql);
    $projects = [];
    if ($projects_result) {
        while ($row = $projects_result->fetch_assoc()) {
            $projects[] = $row;
        }
    }

    // Get statistics
    $total_ideas = count($projects);
    $pending_reviews = count(array_filter($projects, function($p) { return $p['status'] === 'pending'; }));
    $approved_ideas = count(array_filter($projects, function($p) { return $p['status'] === 'approved'; }));
    $investor_reviews_count = count(array_filter($projects, function($p) { return !empty($p['investor_decision']); }));
    $mentor_feedbacks_count = count(array_filter($projects, function($p) { return !empty($p['feedback_text']); }));
} else {
    // Fallback: show basic posts info
    $projects_sql = "SELECT p.post_id, p.title, p.description as problem_statement, 'pending' as status, p.created_at,
                           u.full_name as entrepreneur_name,
                           NULL as expected_return, NULL as cost_estimate, NULL as investor_decision, NULL as investor_comment,
                           NULL as feedback_text, NULL as feedback_date, NULL as mentor_name
                    FROM posts p
                    JOIN users u ON p.user_id = u.user_id
                    ORDER BY p.created_at DESC";
    $projects_result = $conn->query($projects_sql);
    $projects = [];
    if ($projects_result) {
        while ($row = $projects_result->fetch_assoc()) {
            $projects[] = $row;
        }
    }

    // Basic statistics
    $total_ideas = count($projects);
    $pending_reviews = $total_ideas;
    $approved_ideas = 0;
    $investor_reviews_count = 0;
    $mentor_feedbacks_count = 0;
}
?>



<div class="main">
    <div class="reviews-container">
        <!-- Stats Header -->
        <section class="stats-section">
            <div class="reviews-stats-header">
                <h2>💬 Community Feedback & Reviews</h2>
                <p class="stats-description">Investors and Mentors Evaluating & Supporting African Startups</p>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $total_ideas; ?></div>
                        <div class="stat-box-label">Total Ideas</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $pending_reviews; ?></div>
                        <div class="stat-box-label">Pending Review</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $approved_ideas; ?></div>
                        <div class="stat-box-label">Approved Ideas</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $investor_reviews_count; ?></div>
                        <div class="stat-box-label">Investor Reviews</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-number"><?php echo $mentor_feedbacks_count; ?></div>
                        <div class="stat-box-label">Mentor Feedback</div>
                    </div>
                </div>
            </div>
        </section>

        <style>
            .reviews-add-section { margin-top: 40px; }
            .reviews-add-section h3 { margin-bottom: 20px; color: #203a43; }
            .projects-grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); margin-bottom: 30px; }
            .review-project-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 22px; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.04); }
            .project-meta { display: flex; flex-wrap: wrap; gap: 10px; color: #475569; margin: 14px 0 0; font-size: 13px; }
            .project-meta span { background: #f8fafc; border-radius: 9999px; padding: 6px 12px; }
            .review-project-card h4 { margin: 0 0 10px; font-size: 18px; color: #111827; }
            .review-project-card p { color: #4b5563; line-height: 1.6; margin: 0 0 14px; }
            .review-form label { display: block; margin: 12px 0 6px; font-weight: 700; color: #0f172a; }
            .review-form textarea, .review-form select { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px 12px; font-family: inherit; color: #111827; }
            .review-form textarea { min-height: 90px; resize: vertical; }
            .review-form button { margin-top: 12px; padding: 10px 16px; background: #1f2937; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; }
            .review-form button:hover { background: #111827; }
            .project-reviewed { margin-top: 12px; padding: 12px; background: #ecfdf5; color: #166534; border-radius: 10px; font-weight: 700; }
            .review-note { margin-bottom: 24px; padding: 16px; border: 1px solid #f8e3a2; background: #fffbeb; color: #92400e; border-radius: 12px; }
            .idea-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-approved { background: #d4edda; color: #155724; }
            .status-rejected { background: #f8d7da; color: #721c24; }
            .problem-statement { color: #555; margin-bottom: 10px; line-height: 1.5; }
            .entrepreneur-info { color: #666; font-size: 14px; margin-bottom: 15px; }
            .review-status { border-top: 1px solid #e0e0e0; padding-top: 15px; }
            .investor-review-section, .mentor-feedback-section { margin-bottom: 15px; }
            .investor-review-section h5, .mentor-feedback-section h5 { margin: 0 0 8px 0; color: #203a43; font-size: 14px; }
            .review-info, .feedback-info { background: #f8f9fa; padding: 10px; border-radius: 8px; }
            .decision-badge { padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; display: inline-block; }
            .decision-approved { background: #d4edda; color: #155724; }
            .decision-rejected { background: #f8d7da; color: #721c24; }
            .pending-badge { color: #ffc107; font-style: italic; }
            .review-comment, .feedback-text { margin: 8px 0 0 0; color: #555; line-height: 1.5; }
            .mentor-info { display: flex; justify-content: space-between; margin-bottom: 8px; }
            .mentor-info strong { color: #203a43; }
            .mentor-info small { color: #666; }
            @media (max-width: 768px) {
                .projects-grid { grid-template-columns: 1fr; }
            }
        </style>

        <section class="reviews-add-section">
            <h3>� Idea Review Status Overview</h3>
            <?php if (!empty($projects)): ?>
                <div class="projects-grid">
                    <?php foreach ($projects as $project): ?>
                        <div class="review-project-card">
                            <div class="idea-header">
                                <h4><?php echo htmlspecialchars($project['title']); ?></h4>
                                <span class="status-badge status-<?php echo $project['status']; ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                            <p class="problem-statement"><strong>Problem:</strong> <?php echo htmlspecialchars(substr($project['problem_statement'], 0, 120)); ?><?php echo strlen($project['problem_statement']) > 120 ? '...' : ''; ?></p>
                            <div class="entrepreneur-info">by <?php echo htmlspecialchars($project['entrepreneur_name']); ?></div>

                            <div class="review-status">
                                <div class="investor-review-section">
                                    <h5>Investor Review:</h5>
                                    <?php if($project['investor_decision']): ?>
                                        <div class="review-info">
                                            <span class="decision-badge decision-<?php echo $project['investor_decision']; ?>">
                                                <?php echo ucfirst($project['investor_decision']); ?>
                                            </span>
                                            <?php if($project['expected_return']): ?>
                                                <small>Return: <?php echo htmlspecialchars($project['expected_return']); ?></small>
                                            <?php endif; ?>
                                            <?php if($project['cost_estimate']): ?>
                                                <small>Cost: <?php echo htmlspecialchars($project['cost_estimate']); ?></small>
                                            <?php endif; ?>
                                            <?php if($project['investor_comment']): ?>
                                                <p class="review-comment"><?php echo htmlspecialchars($project['investor_comment']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="pending-badge">Pending</span>
                                    <?php endif; ?>
                                </div>

                                <?php if($project['status'] === 'approved'): ?>
                                    <div class="mentor-feedback-section">
                                        <h5>Mentor Feedback:</h5>
                                        <?php if($project['feedback_text']): ?>
                                            <div class="feedback-info">
                                                <div class="mentor-info">
                                                    <strong><?php echo htmlspecialchars($project['mentor_name']); ?></strong>
                                                    <small><?php echo date('M j, Y', strtotime($project['feedback_date'])); ?></small>
                                                </div>
                                                <p class="feedback-text"><?php echo htmlspecialchars($project['feedback_text']); ?></p>
                                            </div>
                                        <?php else: ?>
                                            <span class="pending-badge">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-message">No ideas submitted yet.</p>
            <?php endif; ?>
        </section>

        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <?php if($success) echo "<p class='success'>$success</p>"; ?>
    </div>
</div>

<?php include 'footer.php'; ?>