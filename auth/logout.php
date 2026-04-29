<?php
/**
 * GameTopUp Pro - Logout Handler
 */

session_start();

// Clear all session data
$_SESSION = [];

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
