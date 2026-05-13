<?php
// registration form processing: inserts new user to database
require_once "db.php"; // need connection
$error = "";    // store error message
$success = "";  // store success message

// handle form submission (POST request)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // collect and sanitize inputs
    $full_name = trim($_POST["full_name"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $password  = $_POST["password"] ?? "";
    $role      = trim($_POST["role"] ?? "entrepreneur");

    if ($full_name === '' || $email === '' || $password === '') {
        // all fields are mandatory
        $error = "All fields are required.";
    } else {

        // enforce allowed role values
        $allowed_roles = ['entrepreneur','investor','mentor'];
        if (!in_array($role, $allowed_roles, true)) {
            $role = 'entrepreneur';
        }

        // ensure full name is unique
        $checkName = $conn->prepare("SELECT user_id FROM users WHERE full_name = ? LIMIT 1");
        $checkName->bind_param("s", $full_name);
        $checkName->execute();
        $checkName->store_result();

        if ($checkName->num_rows > 0) {
            $error = "Full name already in use. Please choose a different name.";
        } else {
            // ensure email is unique
            $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                // everything looks good: hash password and insert new user//HASHES PASSWORD AND INSERTS NEW USER TO THE DATABASE
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $is_admin = 0;

                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_admin, created_at) VALUES (?, ?, ?, ?, ?, NOW())"); //parameter query//used to encrypt the system
                $stmt->bind_param("ssssi", $full_name, $email, $hashed, $role, $is_admin);

                if ($stmt->execute()) {
                    $success = "Registration successful. You can now login.";
                } else {
                    $error = "Registration failed.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | EntreNet Africa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-box">
    <h2>EntreNet Africa</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Full name</label>
        <input type="text" name="full_name" placeholder="Enter your full name" required>

        <label>Email</label>
        <input type="email" name="email" placeholder="Enter your email" required>

        <label>Role</label>
        <select name="role" required>
            <option value="entrepreneur">Entrepreneur</option>
            <option value="investor">Investor</option>
            <option value="mentor">Mentor</option>
        </select>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>

        <button type="submit">Register</button>
    </form>

    <?php if (!empty($success)): ?>
    <script>
        // Save registered full name to localStorage recentUsers list
        (function(){
            try {
                var name = <?php echo json_encode($full_name); ?>;
                if (!name) return;
                var key = 'recentUsers';
                var list = JSON.parse(localStorage.getItem(key) || '[]');
                // remove existing
                list = list.filter(function(n){ return n !== name; });
                list.unshift(name);
                // limit to 10
                list = list.slice(0,10);
                localStorage.setItem(key, JSON.stringify(list));
            } catch(e) {
                console.error(e);
            }
        })();
    </script>
    <?php endif; ?>

    <div class="footer-text">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

</body>
</html>
