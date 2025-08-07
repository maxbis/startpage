<?php
/**
 * Authentication Helper Functions
 */

/**
 * Generate a secure random token for remember me functionality
 */
function generateSecureToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Create a remember me token for a user
 */
function createRememberToken($pdo, $userId) {
    $token = generateSecureToken();
    $userAgent = truncateUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 60)); // 60 days
    
    $stmt = $pdo->prepare("
        INSERT INTO remember_tokens (user_id, token, user_agent, ip_address, expires_at) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $token, $userAgent, $ipAddress, $expiresAt]);
    
    return $token;
}

/**
 * Truncate user agent string to fit VARCHAR(200)
 */
function truncateUserAgent($userAgent, $maxLength = 200) {
    if (strlen($userAgent) <= $maxLength) {
        return $userAgent;
    }
    
    // Try to keep the most important parts
    $parts = explode(' ', $userAgent);
    $result = '';
    
    foreach ($parts as $part) {
        if (strlen($result . ' ' . $part) <= $maxLength - 3) {
            $result .= ($result ? ' ' : '') . $part;
        } else {
            break;
        }
    }
    
    return $result . '...';
}

/**
 * Validate a remember me token
 */
function validateRememberToken($pdo, $token) {
    // Clean up expired tokens first
    $pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()")->execute();
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username 
        FROM users u 
        JOIN remember_tokens rt ON u.id = rt.user_id 
        WHERE rt.token = ? AND rt.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result;
}

/**
 * Delete a remember me token
 */
function deleteRememberToken($pdo, $token) {
    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
    $stmt->execute([$token]);
}

/**
 * Delete all remember me tokens for a user
 */
function deleteAllUserTokens($pdo, $userId) {
    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $stmt->execute([$userId]);
}

/**
 * Get all active devices for a user
 */
function getUserDevices($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT id, token, user_agent, ip_address, created_at, expires_at 
        FROM remember_tokens 
        WHERE user_id = ? AND expires_at > NOW()
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get device info from user agent string
 */
function parseUserAgent($userAgent) {
    // Simple parsing - you could use a library like "jenssegers/agent"
    if (strpos($userAgent, 'Chrome') !== false) {
        return 'Chrome Browser';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        return 'Firefox Browser';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        return 'Safari Browser';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        return 'Edge Browser';
    } elseif (strpos($userAgent, 'Mobile') !== false) {
        return 'Mobile Device';
    } else {
        return 'Unknown Browser';
    }
}

/**
 * Set remember me cookie
 */
function setRememberCookie($token) {
    setcookie('remember_token', $token, [
        'expires' => time() + (60 * 60 * 24 * 60), // 60 days
        'path' => '/',
        'domain' => '', // Allow all subdomains
        'secure' => false, // Allow both HTTP and HTTPS
        'httponly' => true,
        'samesite' => 'Lax' // Changed from 'Strict' to 'Lax' for better popup compatibility
    ]);
}

/**
 * Delete remember me cookie
 */
function deleteRememberCookie() {
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

/**
 * Check if user is authenticated
 */
function isAuthenticated($pdo) {
    // Check if user has active session
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        try {
            $user = validateRememberToken($pdo, $_COOKIE['remember_token']);
            if ($user) {
                // Create new session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return true;
            } else {
                // Token is invalid, clean it up
                deleteRememberCookie();
            }
        } catch (Exception $e) {
            // Log error and clean up invalid cookie
            error_log("Token validation error: " . $e->getMessage());
            deleteRememberCookie();
        }
    }
    
    return false;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth($pdo) {
    if (!isAuthenticated($pdo)) {
        // Add debugging for bookmarklet issues
        if (isset($_GET['add']) && $_GET['add'] == '1') {
            $sessionExists = isset($_SESSION['user_id']) ? 'yes' : 'no';
            $cookieExists = isset($_COOKIE['remember_token']) ? 'yes' : 'no';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $referer = $_SERVER['HTTP_REFERER'] ?? 'none';
            
            error_log("Bookmarklet authentication failed - Session: {$sessionExists}, Cookie: {$cookieExists}, User-Agent: {$userAgent}, Referer: {$referer}");
        }
        header('Location: login.php');
        exit;
    }
}
?> 