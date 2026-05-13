<?php
include 'db.php';

echo "<h1>Manual Database Migration</h1>";

// Check if tables exist
$investor_reviews_exists = $conn->query("SHOW TABLES LIKE 'investor_reviews'")->num_rows > 0;
$mentor_feedback_exists = $conn->query("SHOW TABLES LIKE 'mentor_feedback'")->num_rows > 0;
$status_column_exists = $conn->query("SHOW COLUMNS FROM posts LIKE 'status'")->num_rows > 0;
$problem_statement_exists = $conn->query("SHOW COLUMNS FROM posts LIKE 'problem_statement'")->num_rows > 0;

echo "<p>Investor reviews table exists: " . ($investor_reviews_exists ? 'YES' : 'NO') . "</p>";
echo "<p>Mentor feedback table exists: " . ($mentor_feedback_exists ? 'YES' : 'NO') . "</p>";
echo "<p>Status column exists: " . ($status_column_exists ? 'YES' : 'NO') . "</p>";
echo "<p>Problem statement column exists: " . ($problem_statement_exists ? 'YES' : 'NO') . "</p>";

// Run migrations if needed
if (!$status_column_exists) {
    echo "<p>Adding status column...</p>";
    $conn->query("ALTER TABLE posts ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
}

if (!$problem_statement_exists) {
    echo "<p>Renaming description to problem_statement...</p>";
    $conn->query("ALTER TABLE posts CHANGE COLUMN description problem_statement LONGTEXT");
    $conn->query("ALTER TABLE posts CHANGE COLUMN goals objectives LONGTEXT");
}

if (!$investor_reviews_exists) {
    echo "<p>Creating investor_reviews table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS investor_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        idea_id INT NOT NULL,
        investor_id INT NOT NULL,
        expected_return VARCHAR(255),
        cost_estimate VARCHAR(255),
        decision ENUM('approved', 'rejected') NOT NULL,
        comment TEXT,
        created_at DATETIME DEFAULT NOW(),
        FOREIGN KEY (idea_id) REFERENCES posts(post_id) ON DELETE CASCADE,
        FOREIGN KEY (investor_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

if (!$mentor_feedback_exists) {
    echo "<p>Creating mentor_feedback table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS mentor_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        idea_id INT NOT NULL,
        mentor_id INT NOT NULL,
        feedback_text TEXT NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        FOREIGN KEY (idea_id) REFERENCES posts(post_id) ON DELETE CASCADE,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

echo "<p>Migration completed!</p>";
?>