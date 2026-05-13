<?php
// profile.php allows a logged-in user to view/update their profile details

// start session and include database helper
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
include 'db.php';

// redirect unauthorized visitors back to login
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// process form when user submits new profile info
if(isset($_POST['update_profile'])){
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    // optional avatar image upload
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0){
        $allowed = ['jpg','jpeg','png','gif'];
        $filename = $_FILES['avatar']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(in_array($file_ext, $allowed)){
            // move uploaded file to uploads/ with unique name
            $new_name = "uploads/avatar_".$user_id."_".time().".".$file_ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $new_name);
        } else {
            $error = "Invalid file type. Only jpg, jpeg, png, gif allowed.";
        }
    } else {
        $new_name = null; // no file chosen by user
    }

    if(empty($error)){
        // prepare update statement depending on whether avatar changed
        if($new_name){
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, avatar=? WHERE user_id=?");
            $stmt->bind_param("sssi", $full_name, $email, $new_name, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");
            $stmt->bind_param("ssi", $full_name, $email, $user_id);
        }

        if($stmt->execute()){
            $success = "Profile updated successfully!";
            // update the session variable so header shows new name
            $_SESSION['full_name'] = $full_name;
        } else {
            $error = "Failed to update profile.";
        }
        $stmt->close();
    }
}

// fetch current values to populate the form fields
$stmt = $conn->prepare("SELECT full_name, email, avatar FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $avatar);
$stmt->fetch();
$stmt->close();
?>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<h2>My Profile</h2>

<?php if($success) echo "<p style='color:green;'>$success</p>"; ?>
<?php if($error) echo "<p style='color:red;'>$error</p>"; ?>

<form method="post" enctype="multipart/form-data" style="max-width:500px;">
    <label>Full Name:</label><br>
    <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required style="width:100%; padding:8px; margin-bottom:10px;"><br>

    <label>Email:</label><br>
    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required style="width:100%; padding:8px; margin-bottom:10px;"><br>

    <label>Avatar:</label><br>
    <?php if($avatar): ?>
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width:100px; height:100px; display:block; margin-bottom:10px;">
    <?php endif; ?>
    <input type="file" name="avatar" accept="image/*" style="margin-bottom:10px;"><br>

    <input type="submit" name="update_profile" value="Update Profile" style="padding:10px 15px; background:#007BFF; color:white; border:none; cursor:pointer;">
</form>

<?php include 'footer.php'; ?>
