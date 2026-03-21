<?php

// Start the session so we can clear the admin variables
session_start();

// Remove the admin auth markers from the session
unset($_SESSION['admin_id'], $_SESSION['admin_username']);

// Send the user back to the admin login page
header('Location: admin_login.php');
exit;
