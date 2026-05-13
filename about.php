<?php
// about.php simply displays static information about the platform
// start session in case header needs session variables
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
// include database connection out of habit, though not strictly needed here
include 'db.php';

// (we could verify login here if we wanted to restrict access)
?>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">
    <h2>About EntreNet Africa</h2>
    <p>
        Welcome to <strong>EntreNet Africa</strong>, a platform designed to connect African entrepreneurs,
        investors, and mentors in order to foster business growth and innovation across the continent.
    </p>
    <p>
        Our platform enables users to submit business ideas, receive feedback, connect with mentors,
        and access potential investment opportunities. We aim to empower entrepreneurs and strengthen
        the startup ecosystem in Africa.
    </p>
    <h3>Features:</h3>
    <ul>
        <li>Submit and track your business ideas</li>
        <li>Connect with experienced mentors in various sectors</li>
        <li>Interact with investors interested in African startups</li>
        <li>Leave and receive reviews on projects</li>
        <li>Access reports and analytics on user engagement</li>
    </ul>
    <p>
        Our vision is to create a thriving network that bridges the gap between ideas and funding,
        mentorship and execution. We believe that African entrepreneurs have the potential to
        transform the continent’s economy, and EntreNet Africa is here to support that journey.
    </p>
</div>

<?php include 'footer.php'; ?>
