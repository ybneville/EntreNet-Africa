<?php
// db.php - database connection settings and helper
// this file is included wherever we need to talk to MySQL

// these variables should match your local environment
$host = "localhost";          // database server location
$user = "root";               // database username
$pass = "";                   // database password
$db   = "entrenet_africa";    // database name

// create a new mysqli object using the credentials above
$conn = new mysqli($host, $user, $pass, $db);

// immediately check if the connection failed and abort if so
if($conn->connect_error){
    // in development we can just die with an error message
    die("Connection failed: " . $conn->connect_error);
}
?>
