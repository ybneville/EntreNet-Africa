<style>
/* sidebar styling: fixed on the left, full height minus topbar */
.sidebar {
    width:220px;
    background:#203a43;
    position:fixed;
    top:45px; /* sits beneath the top navigation bar */
    bottom:0; /* extends to bottom of viewport */
    padding-top:15px;
    box-sizing:border-box;
    overflow-y:auto; /* scroll if links overflow */
}
/* links inside sidebar */
.sidebar a { 
    display:block; 
    color:white; 
    padding:12px 20px; 
    text-decoration:none; 
    font-size:14px;
}
.sidebar a:hover { 
    background:#2c5364; 
    color:white; 
}

/* main page content adjustments when sidebar is present */
.main {
    margin-left:220px; /* leave room for sidebar */
    margin-top:45px;   /* below the fixed topbar */
    padding:20px;
    min-height: calc(100vh - 45px); /* ensure content area stretches */
    box-sizing:border-box;
    background:#f8f9fa; /* content background for readability */
}
</style>

<div class="sidebar">
    <!-- navigation links for the main application sections -->
    <a href="dashboard.php">Dashboard</a>
    <a href="post_idea.php">Submit Idea</a>
    <a href="reviews.php">Reviews</a>
    <a href="reports.php">Reports</a>
    <a href="users.php">Users</a>
    <a href="messages.php">Messages</a>
</div>
<div class="main">
<!-- open "main" container - individual pages will close this later -->
  