<?php
/**
 * Session Configuration
 * 
 * IMPORTANT: This file MUST be included BEFORE session_start() in all files
 * that use sessions. This extends session lifetime to match remember me tokens.
 */

// Configure session to last longer (30 days) to match remember me functionality
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie to expire in 30 days
    ini_set('session.cookie_lifetime', 60 * 60 * 24 * 30); // 30 days
    // Set session garbage collection max lifetime to 30 days
    ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30); // 30 days
    // Use cookies to store session ID
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    // Set cookie parameters
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30, // 30 days
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

