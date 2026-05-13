<?php
/**
 * seed_data.php
 * Run this once to populate sample users, ideas, reviews and messages.
 * Designed to demonstrate the ecosystem with entrepreneurs, investors and mentors.
 */

include 'db.php';

function execute($conn, $sql, $desc = '') {
    if ($conn->query($sql) === TRUE) {
        echo "<p>✔ $desc</p>\n";
    } else {
        echo "<p>✗ $desc: " . $conn->error . "</p>\n";
    }
}

echo "<h2>Seeding Sample Data</h2>";

// Ensure reports table exists so reports page won't error
$createReports = "CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT DEFAULT NULL,
    report_text TEXT NOT NULL,
    reported_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
execute($conn, $createReports, "Create reports table if missing");

// 1. create users if they don't exist
$users = [
    ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'role' => 'Entrepreneur', 'is_admin' => 0],
    ['name' => 'Kwame Mensah', 'email' => 'kwame@example.com', 'role' => 'Entrepreneur', 'is_admin' => 0],
    ['name' => 'Fatima Abdi', 'email' => 'fatima@example.com', 'role' => 'Entrepreneur', 'is_admin' => 0],
    ['name' => 'Sasha Braus', 'email' => 'sasha@example.com', 'role' => 'Entrepreneur', 'is_admin' => 0],
    ['name' => 'Bradley Noel', 'email' => 'bradley@example.com', 'role' => 'Entrepreneur', 'is_admin' => 0],
    ['name' => 'Gloria Patience', 'email' => 'gloria@example.com', 'role' => 'Entrepreneur', 'is_admin' => 0],
    ['name' => 'Joseph Osei', 'email' => 'joseph@example.com', 'role' => 'Investor', 'is_admin' => 0],
    ['name' => 'Lindiwe Zulu', 'email' => 'lindiwe@example.com', 'role' => 'Investor', 'is_admin' => 0],
    ['name' => 'Ngozi Okonkwo', 'email' => 'ngozi@example.com', 'role' => 'Mentor', 'is_admin' => 0],
    ['name' => 'Tunde Ade', 'email' => 'tunde@example.com', 'role' => 'Mentor', 'is_admin' => 0],
    ['name' => 'Admin One', 'email' => 'admin@example.com', 'role' => 'Administrator','is_admin'=>1],
];

// iterate through our predefined list of sample users
foreach ($users as $u) {
    // sanitize values to prevent SQL injection
    $safeName = $conn->real_escape_string($u['name']);
    $safeEmail = $conn->real_escape_string($u['email']);
    $role = $conn->real_escape_string($u['role']);
    $isAdmin = (int)$u['is_admin'];

    // check if this user already exists by email; skip if found
    $res = $conn->query("SELECT user_id FROM users WHERE email='{$safeEmail}' LIMIT 1");
    if ($res && $res->num_rows > 0) continue;

    // insert new user row and report the action
    execute($conn, "INSERT INTO users (full_name,email,role,is_admin,created_at) VALUES ('{$safeName}','{$safeEmail}','{$role}',{$isAdmin},NOW())", "Insert user {$u['name']}");
} 

// fetch user ids and build a lookup map so we can reference users by email later
$userMap = [];
$res = $conn->query("SELECT user_id, email FROM users");
while ($r = $res->fetch_assoc()) {
    $userMap[$r['email']] = $r['user_id'];
}

// 2. create posts (ideas)
$ideas = [
    ['user'=>'alice@example.com','title'=>'Solar-powered irrigation system','description'=>'A low-cost solar pump for smallholder farmers.','sector'=>'Agriculture','goals'=>'Provide reliable water to crops, reduce diesel use.'],
    ['user'=>'kwame@example.com','title'=>'Mobile money remittance app','description'=>'An app for fast cross-border payments within Africa.','sector'=>'Finance','goals'=>'Reduce transfer costs, provide secure platform.'],
    ['user'=>'fatima@example.com','title'=>'Recyclable packaging startup','description'=>'Eco-friendly packaging made from agricultural waste.','sector'=>'Retail/E-commerce','goals'=>'Cut plastic waste, partner with online retailers.'],
    ['user'=>'sasha@example.com','title'=>'Green roofing materials factory','description'=>'Manufacture of eco-friendly roofing using recycled plastics.','sector'=>'Real Estate','goals'=>'Lower housing costs, reduce landfill waste.'],
    ['user'=>'bradley@example.com','title'=>'AI tutor for rural schools','description'=>'An offline-capable educational AI for remote classrooms.','sector'=>'Technology','goals'=>'Improve literacy in underserved communities.'],
    ['user'=>'gloria@example.com','title'=>'Mobile veterinary services','description'=>'Pet and livestock care via on-demand mobile clinics.','sector'=>'Transportation & Logistics','goals'=>'Increase animal health in rural areas.'],
];

// iterate through sample idea definitions and insert if not already present
foreach ($ideas as $idea) {
    $uid = $userMap[$idea['user']];
    $title = $conn->real_escape_string($idea['title']);
    $desc = $conn->real_escape_string($idea['description']);
    $sector = $conn->real_escape_string($idea['sector']);
    $goals = $conn->real_escape_string($idea['goals']);
    
    // check if this exact idea by the same user already exists to avoid duplicates
    $res = $conn->query("SELECT post_id FROM posts WHERE title='{$title}' AND user_id={$uid} LIMIT 1");
    if ($res && $res->num_rows > 0) continue;

    // perform insertion and log
    execute($conn, "INSERT INTO posts (user_id,title,description,sector,goals,created_at) VALUES ({$uid},'{$title}','{$desc}','{$sector}','{$goals}',NOW())","Insert idea '{$idea['title']}'");
}

// fetch idea ids
$ideaMap = [];
$res = $conn->query("SELECT post_id,title FROM posts");
while ($r = $res->fetch_assoc()) {
    $ideaMap[$r['title']] = $r['post_id'];
}

// 3. create reviews with ratings
$reviews = [
    ['idea'=>'Solar-powered irrigation system','user'=>'ngozi@example.com','rating'=>5,'text'=>'Brilliant concept, high impact. Would invest.' ],
    ['idea'=>'Solar-powered irrigation system','user'=>'tunde@example.com','rating'=>4,'text'=>'Technical design looks sound. Consider scalable components.' ],
    ['idea'=>'Mobile money remittance app','user'=>'ngozi@example.com','rating'=>4,'text'=>'Great market need. Secure architecture will be key.' ],
    ['idea'=>'Recyclable packaging startup','user'=>'tunde@example.com','rating'=>5,'text'=>'Love the sustainability angle, strong traction potential.' ],
    ['idea'=>'Mobile money remittance app','user'=>'joseph@example.com','rating'=>5,'text'=>'Happy to fund initial MVP. Disruptive opportunity.' ],
    ['idea'=>'Solar-powered irrigation system','user'=>'lindiwe@example.com','rating'=>5,'text'=>'Excellent, aligns with green investment goals.' ],
    ['idea'=>'Green roofing materials factory','user'=>'ngozi@example.com','rating'=>4,'text'=>'Interesting supply chain considerations; I can introduce you to contacts.' ],
    ['idea'=>'AI tutor for rural schools','user'=>'tunde@example.com','rating'=>5,'text'=>'I can help with machine learning architecture and pilot deployment.' ],
    ['idea'=>'Mobile veterinary services','user'=>'ngozi@example.com','rating'=>4,'text'=>'Great logistic model; could partner on a health initiative.' ],
    ['idea'=>'Green roofing materials factory','user'=>'joseph@example.com','rating'=>5,'text'=>'I am willing to fund prototype manufacturing.' ],
];

// add sample review records, ensuring each reviewer doesn't review the same idea twice
foreach ($reviews as $rev) {
    $uid = $userMap[$rev['user']];
    $pid = $ideaMap[$rev['idea']];
    $rating = (int)$rev['rating'];
    $text = $conn->real_escape_string($rev['text']);
    
    // simple existence check to avoid duplicate reviews
    $check = $conn->query("SELECT review_id FROM reviews WHERE user_id={$uid} AND post_id={$pid} LIMIT 1");
    if ($check && $check->num_rows > 0) continue;
    
    execute($conn, "INSERT INTO reviews (user_id,post_id,rating,review_text,created_at) VALUES ({$uid},{$pid},{$rating},'{$text}',NOW())","Insert review on '{$rev['idea']}' by {$rev['user']}");
}

// 4. create a set of example direct messages exchanged between platform users
the messages are purely illustrative and show investor/mentor interaction
$messages = [
    ['sender'=>'alice@example.com','receiver'=>'ngozi@example.com','text'=>'Hi Ngozi, thank you for the feedback on the solar system. Could we schedule a call to discuss prototypes?'],
    ['sender'=>'ngozi@example.com','receiver'=>'alice@example.com','text'=>'Absolutely Alice, I am available tomorrow at 10am. Let me know if that works.'],
    ['sender'=>'kwame@example.com','receiver'=>'joseph@example.com','text'=>'Joseph, I would appreciate your thoughts on the remittance app’s revenue model.'],
    ['sender'=>'joseph@example.com','receiver'=>'kwame@example.com','text'=>'Kwame, I think a small transaction fee plus premium services will work. Let us chat this week.'],
    ['sender'=>'fatima@example.com','receiver'=>'tunde@example.com','text'=>'Tunde, your review was motivating. Do you know any packaging suppliers we could partner with?' ],
    ['sender'=>'sasha@example.com','receiver'=>'joseph@example.com','text'=>'Joseph, I heard you are interested in green investments. I would love to discuss funding for my roofing materials idea.'],
    ['sender'=>'bradley@example.com','receiver'=>'tunde@example.com','text'=>'Tunde, great review on the AI tutor. Could you connect me with a machine learning mentor?'],
    ['sender'=>'gloria@example.com','receiver'=>'ngozi@example.com','text'=>'Ngozi, I saw your note on veterinary logistics. Can we plan a pilot in rural areas?'],
];

foreach ($messages as $msg) {
    $sid = $userMap[$msg['sender']];
    $rid = $userMap[$msg['receiver']];
    $text = $conn->real_escape_string($msg['text']);
    // avoid duplicate by simple check
    $check = $conn->query("SELECT message_id FROM messages WHERE sender_id={$sid} AND receiver_id={$rid} AND message='{$text}' LIMIT 1");
    if ($check && $check->num_rows > 0) continue;
    execute($conn, "INSERT INTO messages (sender_id,receiver_id,message,sent_at) VALUES ({$sid},{$rid},'{$text}',NOW())","Message from {$msg['sender']} to {$msg['receiver']}");
}


// 5. optionally populate reports table if it exists
if($conn->query("SHOW TABLES LIKE 'reports'")->num_rows > 0) {
    // add a few sample report rows
    $sampleReports = [
        ['user'=>'ngozi@example.com','idea'=>'Solar-powered irrigation system','text'=>'Investor interested in prototype, awaiting follow-up.'],
        ['user'=>'joseph@example.com','idea'=>'Green roofing materials factory','text'=>'Requesting detailed business plan from entrepreneur.'],
    ];
    foreach($sampleReports as $r) {
        $uid = $userMap[$r['user']];
        $pid = $ideaMap[$r['idea']];
        $txt = $conn->real_escape_string($r['text']);
        $conn->query("INSERT INTO reports (user_id, post_id, report_text, reported_at) VALUES ({$uid}, {$pid}, '{$txt}', NOW())");
    }
}

echo "<p>✔ Seeding complete. You may delete this file afterward.</p>\n";
?>