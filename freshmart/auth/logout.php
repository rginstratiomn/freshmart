<?php
require_once '../includes/functions.php';

if(isLoggedIn()) {
    // Log activity before destroying session
    logActivity($_SESSION['user_id'], 'logout', 'User', $_SESSION['user_id'], 'User logged out');
}

// Destroy all session data
session_destroy();

// Redirect to login page
redirect('login.php');
?>