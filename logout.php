<?php
// Logout script
require_once "includes/auth.php";

// Log out the user
logout_user();

// Redirect to login page
$_SESSION['success'] = "You have been successfully logged out.";
header("Location: login.php");
exit;
?>
