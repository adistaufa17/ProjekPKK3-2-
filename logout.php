<?php
session_start();

// Check if user is logged in first
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle the logout confirmation
if (isset($_POST['confirm_logout'])) {
    // Store the logout success message in a temporary variable
    $logout_message = true;
    
    // Destroy the session completely
    session_unset();
    session_destroy();
    
    // Start a new session just to store the notification
    session_start();
    $_SESSION['logout_success'] = $logout_message;
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// If cancel logout
if (isset($_POST['cancel_logout'])) {
    // Go back to the previous page or default to beranda.php
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'beranda.php';
    
    // Make sure we're not redirecting back to the logout confirmation page
    if (strpos($redirect, 'logout_confirmation.php') !== false) {
        $redirect = 'beranda.php';
    }
    
    header("Location: $redirect");
    exit();
}

// If someone accesses this page directly without form submission
header("Location: logout_confirmation.php");
exit();
?>