<?php
/**
 * Database Migration - Add new columns for enhanced features
 * Run this once to update your database schema
 */

include 'db.php';

echo "<h2>EntreneT Africa - Database Migration</h2>";

// Array of migrations to run
$migrations = [
    // Ensure a reports table exists for the reporting workflow
    "CREATE TABLE IF NOT EXISTS reports (
        report_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT DEFAULT NULL,
        report_text TEXT NOT NULL,
        reported_at DATETIME DEFAULT NOW(),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" => "Creating reports table if missing",

    // Add 'sector' column to posts if it doesn't exist
    "ALTER TABLE posts ADD COLUMN sector VARCHAR(100) DEFAULT NULL" => "Adding 'sector' column to posts table",
    
    // Add 'goals' column to posts if it doesn't exist
    "ALTER TABLE posts ADD COLUMN goals LONGTEXT DEFAULT NULL" => "Adding 'goals' column to posts table",

    // Add an active flag to easily disable user accounts without deleting them
    "ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1" => "Adding 'is_active' flag to users table",

    // Enhance reports workflow with status tracking
    "ALTER TABLE reports ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Open'" => "Adding 'status' column to reports table",
    "ALTER TABLE reports ADD COLUMN resolved_at DATETIME NULL" => "Adding 'resolved_at' column to reports table",

    // Create a notifications table for future platform features
    "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT NOW(),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" => "Creating notifications table",

    // rename role values from Innovator to Mentor if any exist
    "UPDATE users SET role='Mentor' WHERE role='Innovator'" => "Renaming 'Innovator' role values to 'Mentor'",

    // Add status column to posts (ideas) table
    "ALTER TABLE posts ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'" => "Adding 'status' column to posts table",

    // Rename description to problem_statement and goals to objectives
    "ALTER TABLE posts CHANGE COLUMN description problem_statement LONGTEXT" => "Renaming 'description' to 'problem_statement'",
    "ALTER TABLE posts CHANGE COLUMN goals objectives LONGTEXT" => "Renaming 'goals' to 'objectives'",

    // Create investor_reviews table
    "CREATE TABLE IF NOT EXISTS investor_reviews (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" => "Creating investor_reviews table",

    // Create mentor_feedback table
    "CREATE TABLE IF NOT EXISTS mentor_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        idea_id INT NOT NULL,
        mentor_id INT NOT NULL,
        feedback_text TEXT NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        FOREIGN KEY (idea_id) REFERENCES posts(post_id) ON DELETE CASCADE,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" => "Creating mentor_feedback table",
];

$succeeded = 0;
$failed = 0;

foreach($migrations as $sql => $description) {
    echo "<p><strong>$description...</strong> ";
    
    // Check if column already exists before adding
    if(stripos($sql, "ALTER TABLE") === 0 && stripos($sql, "ADD COLUMN") !== false) {
        $table = trim(str_replace("ALTER TABLE", "", substr($sql, 0, strpos($sql, "ADD"))));
        $columnPart = trim(substr($sql, strpos($sql, "ADD COLUMN") + 10));
        $columnName = trim(substr($columnPart, 0, strpos($columnPart, " ")));
        
        // Check if column exists
        $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$columnName' AND TABLE_SCHEMA = DATABASE()";
        $checkResult = $conn->query($checkSql);
        
        if($checkResult && $checkResult->num_rows > 0) {
            echo "✓ Column already exists</p>";
            $succeeded++;
            continue;
        }
    }
    
    // Check for CHANGE COLUMN (rename)
    if(stripos($sql, "ALTER TABLE") === 0 && stripos($sql, "CHANGE COLUMN") !== false) {
        $table = trim(str_replace("ALTER TABLE", "", substr($sql, 0, strpos($sql, "CHANGE"))));
        $changePart = trim(substr($sql, strpos($sql, "CHANGE COLUMN") + 13));
        $oldColumn = trim(substr($changePart, 0, strpos($changePart, " ")));
        
        // Check if old column exists and new column doesn't
        $checkOldSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$oldColumn' AND TABLE_SCHEMA = DATABASE()";
        $checkOldResult = $conn->query($checkOldSql);
        
        if($checkOldResult && $checkOldResult->num_rows === 0) {
            echo "✓ Column already renamed</p>";
            $succeeded++;
            continue;
        }
    }
    
    if($conn->query($sql)) {
        echo "✓ Success</p>";
        $succeeded++;
    } else {
        echo "✗ Failed: " . $conn->error . "</p>";
        $failed++;
    }
}

echo "<hr>";
echo "<p><strong>Migration Summary:</strong> $succeeded succeeded, $failed failed</p>";

if($failed === 0) {
    echo "<p style='color: green; font-weight: bold;'>✓ All migrations completed successfully!</p>";
    echo "<p>You can now use the enhanced features:</p>";
    echo "<ul>";
    echo "<li>Submit ideas with business sector and goals</li>";
    echo "<li>View professional idea showcases with descriptions</li>";
    echo "<li>See investor and mentor reviews with ratings</li>";
    echo "<li>Track platform engagement and activity</li>";
    echo "<li>Browse community member profiles</li>";
    echo "</ul>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠ Some migrations had issues, but the platform may still work with existing columns.</p>";
}

echo "<p><strong>Note:</strong> You can safely delete this file after running the migration.</p>";
?>
