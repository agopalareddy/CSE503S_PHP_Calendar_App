<?php
// site/public/logout.php
require_once '../includes/auth.php';

// Log out the user
logoutUser();

// Redirect to the login page or the homepage
header("Location: index.php");
exit();
