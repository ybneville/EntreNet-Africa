<?php
// Start the PHP session if one has not already been started.
// Sessions are used for login state and user information.
if (session_status() == PHP_SESSION_NONE) {
    // Secure session cookie defaults (set secure=true in production HTTPS)
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// include the database connection in case any header logic needs it
require_once 'db.php';

// Basic helper functions (safe output + CSRF)
if (!function_exists('esc')) {
    function esc($value) {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('ensure_csrf_token')) {
    function ensure_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    function csrf_input() {
        ensure_csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . esc($_SESSION['csrf_token']) . '">';
    }

    function verify_csrf_token($token) {
        ensure_csrf_token();
        return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// read user information from session if available.
// default to empty strings to prevent warnings later.
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>EntreNet Africa</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
    <!-- site logo/title on the left -->
    <div class="logo">EntreNet Africa</div>
    <!-- navigation links appear on the right -->
    <div class="nav-links">
        <?php if(isset($_SESSION['user_id'])): ?>
            <!-- when logged in, show user name and role plus profile/logout links -->
            <span><?php echo htmlspecialchars($full_name)." - ".($role == 'admin' ? 'Admin' : ucfirst($role)); ?></span>
            <?php
            $dashboard_link = 'dashboard.php'; // default
            if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1){
                $dashboard_link = 'admin_dashboard.php';
            } elseif(strtolower($role) === 'entrepreneur'){
                $dashboard_link = 'user_dashboard.php';
            } elseif(strtolower($role) === 'investor'){
                $dashboard_link = 'investor_dashboard.php';
            } elseif(strtolower($role) === 'mentor'){
                $dashboard_link = 'mentor_dashboard.php';
            }
            ?>
            <a href="<?php echo $dashboard_link; ?>">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <!-- for guests, offer login/register options -->
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
        <!-- common link available to everyone -->
        <a href="about.php">About</a>
    </div>
</div>
