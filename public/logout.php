<?php

// Start the session so we can access and destroy it
session_start();
// Wipe all session data and end the session
session_destroy();

// Send the user back to the homepage
header('Location: index.php');
exit;
