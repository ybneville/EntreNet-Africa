<?php
// one-time bootstrap script to create an administrator account
// this should be removed from the server after use for security reasons
require_once "db.php";

// check if an admin user already exists to avoid creating duplicates
$check = $conn->query("SELECT user_id FROM users WHERE is_admin = 1 LIMIT 1");
if ($check && $check->num_rows > 0) {
    die(" Admin already exists. Delete this file.");
}

// ADMIN DETAILS (WRITE THEM DOWN)
$full_name = "Willy Neville Wanjala";
$email     = "willy8@gmail.com";
$password  = "Willy123"; // remember this
$role      = "admin";
$is_admin  = 1;

// HASH PASSWORD (VERY IMPORTANT)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users (full_name, email, password, role, is_admin, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param(
    "ssssi",
    $full_name,
    $email,
    $hashed_password,
    $role,
    $is_admin
);

if ($stmt->execute()) {
    echo "✅ Admin created successfully.<br>";
    echo "📧 Email: $email<br>";
    echo "🔑 Password: $password<br>";
    echo "<strong>DELETE admin_setup.php NOW</strong>";
} else {
    echo " Error: " . $stmt->error;
}
