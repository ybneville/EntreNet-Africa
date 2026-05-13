<?php
// report_issue.php - allow users to flag an idea for review by admins

session_start();
require_once 'db.php';

// require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'header.php';
include 'sidebar.php';

$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

// If post_id provided, attempt to load its title for context
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$post_title = '';
if ($post_id > 0) {
    $stmt = $conn->prepare("SELECT title FROM posts WHERE post_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $post_title = $res->fetch_assoc()['title'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $report_text = trim($_POST['report_text'] ?? '');

        if ($post_id <= 0 || $report_text === '') {
            $error = 'Please select an idea and enter a report description.';
        } else {
            $stmt = $conn->prepare("INSERT INTO reports (user_id, post_id, report_text, reported_at) VALUES (?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('iis', $user_id, $post_id, $report_text);
                if ($stmt->execute()) {
                    $success = 'Thank you. Your report has been submitted and will be reviewed by an administrator.';

                    // Load the title for display in case it wasn't preloaded.
                    $stmt2 = $conn->prepare("SELECT title FROM posts WHERE post_id = ? LIMIT 1");
                    if ($stmt2) {
                        $stmt2->bind_param('i', $post_id);
                        $stmt2->execute();
                        $res2 = $stmt2->get_result();
                        if ($res2 && $res2->num_rows === 1) {
                            $post_title = $res2->fetch_assoc()['title'];
                        }
                    }
                } else {
                    $error = 'Failed to submit report. Please try again later.';
                }
            } else {
                $error = 'Unable to prepare report submission.';
            }
        }
    }
}
?>

<div class="main">
    <div class="report-issue-container" style="max-width:700px; margin: 0 auto;">
        <h2>Report an Idea</h2>
        <p>Please describe the concern you have about this idea. An administrator will review it shortly.</p>

        <?php if ($error): ?>
            <div class="error"><?php echo esc($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo esc($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="post_id" value="<?php echo esc($post_id); ?>">

            <label>Idea</label>
            <input type="text" value="<?php echo esc($post_title ?: 'Unknown idea'); ?>" readonly style="background:#f4f4f4;" />

            <label>Report Details</label>
            <textarea name="report_text" rows="5" required placeholder="Describe the issue clearly (e.g., inappropriate content, spam, inaccurate info)"><?php echo isset($_POST['report_text']) ? esc($_POST['report_text']) : ''; ?></textarea>

            <button type="submit">Submit Report</button>
        </form>

        <div style="margin-top: 20px;">
            <a href="reports.php">Back to Reports</a>
        </div>

    </div>
</div>

</body>
</html>
