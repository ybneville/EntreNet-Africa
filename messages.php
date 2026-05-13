<?php
// messages.php - allows logged in users to send and view direct messages

// session is required to know who is sending/receiving
session_start();
include 'db.php';

// if not authenticated, redirect to login
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// include the common header/sidebar UI
include 'header.php';
include 'sidebar.php';

// initialize feedback variables
$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// when the form is submitted, insert a new message row
if(isset($_POST['send'])){
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    
    if($receiver_id == '' || $message == ''){
        $error = "Please select a user and type a message.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $receiver_id, $message);
        if($stmt->execute()){
            $success = "Message sent successfully!";
        } else {
            $error = "Failed to send message: " . $conn->error;
        }
    }
}

// query list of other users to populate the dropdown
$users_sql = "SELECT user_id, full_name FROM users WHERE user_id != $user_id ORDER BY full_name ASC";
$users_result = $conn->query($users_sql);

// retrieve all messages where current user is sender or recipient
$messages_sql = "SELECT m.message_id, m.sender_id, m.receiver_id, m.message, m.sent_at, 
                        u1.full_name AS sender_name, u2.full_name AS receiver_name
                 FROM messages m
                 JOIN users u1 ON m.sender_id = u1.user_id
                 JOIN users u2 ON m.receiver_id = u2.user_id
                 WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
                 ORDER BY m.sent_at ASC";
$messages_result = $conn->query($messages_sql);
?>

<style>
/* Messaging styles */
form { margin-top:20px; font-family:'Times New Roman', serif; }
select, textarea { width:100%; padding:10px; margin:10px 0; }
button { padding:10px 20px; background:#007BFF; color:white; border:none; cursor:pointer; }
button:hover { background:#0056b3; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
table, th, td { border:1px solid #ddd; }
th, td { padding:12px; text-align:left; }
th { background:#007BFF; color:white; }
tr:nth-child(even) { background:#f2f2f2; }
.error { color:red; }
.success { color:green; }
</style>

<h1>Messages</h1>

<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>

<!-- Send Message Form -->
<form method="POST">
    <label>Send Message To:</label>
    <select name="receiver_id" required>
        <option value="">Select User</option>
        <?php while($row = $users_result->fetch_assoc()): ?>
            <option value="<?php echo $row['user_id']; ?>"><?php echo htmlspecialchars($row['full_name']); ?></option>
        <?php endwhile; ?>
    </select>

    <label>Message:</label>
    <textarea name="message" rows="4" placeholder="Type your message" required></textarea>

    <button type="submit" name="send">Send Message</button>
</form>

<!-- Display Messages -->
<h2>Your Conversations</h2>

<?php if($messages_result && $messages_result->num_rows > 0): ?>
<table>
    <tr>
        <th>ID</th>
        <th>Sender</th>
        <th>Receiver</th>
        <th>Message</th>
        <th>Sent At</th>
    </tr>
    <?php while($msg = $messages_result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $msg['message_id']; ?></td>
        <td><?php echo htmlspecialchars($msg['sender_name']); ?></td>
        <td><?php echo htmlspecialchars($msg['receiver_name']); ?></td>
        <td><?php echo htmlspecialchars($msg['message']); ?></td>
        <td><?php echo $msg['sent_at']; ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>No messages found.</p>
<?php endif; ?>

</div> <!-- Close main -->
</div> <!-- Close container -->
</body>
</html>
<?php include 'footer.php'; ?>