<?php
// logins require a PHP session to persist user state
session_start();
// load the database connection so we can query the users table
require_once "db.php";

// variable to hold any error message shown back to user
$error = "";

// only process data if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // fetch the submitted fields safely, trim whitespace from username
    $full_name = isset($_POST["full_name"]) ? trim($_POST["full_name"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";

    // basic validation: both fields required
    if (empty($full_name) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        // Look up the user by full name (assuming names are unique enough)
        // If the users table has an "is_active" column, use it to prevent disabled accounts from logging in.
        $hasActive = false;
        $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $hasActive = true;
        }

        $sql = $hasActive
            ? "SELECT user_id, full_name, email, password, role, is_admin, is_active FROM users WHERE full_name = ? LIMIT 1"
            : "SELECT user_id, full_name, email, password, role, is_admin FROM users WHERE full_name = ? LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $full_name);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // found a matching account
            $user = $result->fetch_assoc();

            // verify the submitted password against the hashed value
            if (password_verify($password, $user["password"])) {

                // prevent login if account is deactivated
                if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                    $error = "This account has been deactivated. Please contact an administrator.";
                } else {
                    // store user info in session for later pages
                    $_SESSION["user_id"]   = $user["user_id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["email"]     = $user["email"];
                    $_SESSION["role"]      = $user["role"];
                    $_SESSION["is_admin"]  = (int)$user["is_admin"];
                }

                // make sure the user can proceed (not deactivated)
                if (!isset($user['is_active']) || (int)$user['is_active'] !== 0) {
                    // ensure we track which users have logged in before (adds column if missing)
                    $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'last_login'");
                    if ($colCheck && $colCheck->num_rows === 0) {
                        // best-effort: add the column if it does not exist
                        $conn->query("ALTER TABLE users ADD COLUMN last_login DATETIME NULL");
                    }

                    $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    if ($update) {
                        $update->bind_param('i', $user['user_id']);
                        $update->execute();
                    }

                    // redirect authenticated users to their role-specific dashboard
                    $dashboard_url = 'dashboard.php'; // fallback
                    if((int)$user["is_admin"] === 1){
                        $dashboard_url = 'admin_dashboard.php';
                    } elseif(strtolower($user["role"]) === 'entrepreneur'){
                        $dashboard_url = 'user_dashboard.php';
                    } elseif(strtolower($user["role"]) === 'investor'){
                        $dashboard_url = 'investor_dashboard.php';
                    } elseif(strtolower($user["role"]) === 'mentor'){
                        $dashboard_url = 'mentor_dashboard.php';
                    }
                    header("Location: $dashboard_url");
                    exit;
                }

            } else {
                $error = "Incorrect password.";
            }
        } else {
            // no account with that full name
            $error = "Account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | EntreNet Africa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-box">
    <h2>EntreNet Africa</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <!-- dummy input to reduce browser autofill of username -->
        <input type="text" name="fakeusernameremembered" id="fakeusernameremembered" style="display:none;">
        <label>Full name</label>
        <input type="text" name="full_name" id="full_name" placeholder="Enter your full name" autocomplete="off" list="recent-names" required>
        <datalist id="recent-names"></datalist>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>

        <button type="submit" style="color: blue;">Login</button>
    </form>

    <div class="footer-text">
        Don’t have an account? <a href="register.php">Register</a>
        <br>
        Admin? <a href="admin/login.php">Login here</a>
    </div>
</div>

<script>
    // Populate datalist from localStorage recentUsers
    (function(){
        try {
            var key = 'recentUsers';
            var list = JSON.parse(localStorage.getItem(key) || '[]');
            var dl = document.getElementById('recent-names');
            if (!dl) return;
            dl.innerHTML = '';
            list.forEach(function(name){
                var opt = document.createElement('option');
                opt.value = name;
                dl.appendChild(opt);
            });
        } catch(e){ console.error(e); }
    })();
</script>

</body>
</html>