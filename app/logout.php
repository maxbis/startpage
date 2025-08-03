<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Delete remember me token if exists
if (isset($_COOKIE['remember_token'])) {
    deleteRememberToken($pdo, $_COOKIE['remember_token']);
    deleteRememberCookie();
}

// Delete all tokens for current user if logged in
if (isset($_SESSION['user_id'])) {
    deleteAllUserTokens($pdo, $_SESSION['user_id']);
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?> 