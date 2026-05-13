<?php
// simple logout script: destroy session and redirect to login
session_start();          // resume session so we can destroy it
session_destroy();        // clear all session data
header("Location: login.php"); // send user back to login
exit();
