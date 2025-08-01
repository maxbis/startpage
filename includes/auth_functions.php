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
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 60)); // 60 days
    
    $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $token, $expiresAt]);
    
    return $token;
}

/**
 * Validate a remember me token
 */
function validateRememberToken($pdo, $token, $silent = false) {
    if (!$silent) {
        echo "<script>console.log('🔐 Token: Validating remember token: " . substr($token, 0, 10) . "...');</script>";
    }
    
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
    
    if ($result) {
        if (!$silent) {
            echo "<script>console.log('🔐 Token: Token found in database for user: " . $result['username'] . "');</script>";
        }
    } else {
        if (!$silent) {
            echo "<script>console.log('🔐 Token: Token not found in database or expired');</script>";
        }
    }
    
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
 * Set remember me cookie
 */
function setRememberCookie($token) {
    echo "<script>console.log('🔐 Cookie: Setting remember token cookie: " . substr($token, 0, 10) . "...');</script>";
    echo "<script>console.log('🔐 Cookie: Cookie settings - expires: 60 days, secure: " . (isset($_SERVER['HTTPS']) ? 'true' : 'false') . ", samesite: Strict');</script>";
    
    setcookie('remember_token', $token, [
        'expires' => time() + (60 * 60 * 24 * 60), // 60 days
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']), // Secure if HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
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
function isAuthenticated($pdo, $silent = false) {
    // Check if user has active session
    if (isset($_SESSION['user_id'])) {
        if (!$silent) {
            echo "<script>console.log('🔐 Auth: Session found - user_id: " . $_SESSION['user_id'] . "');</script>";
        }
        return true;
    }
    
    if (!$silent) {
        echo "<script>console.log('🔐 Auth: No active session found');</script>";
    }
    
    // Check remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        if (!$silent) {
            echo "<script>console.log('🔐 Auth: Remember token cookie found: " . substr($_COOKIE['remember_token'], 0, 10) . "...');</script>";
        }
        $user = validateRememberToken($pdo, $_COOKIE['remember_token'], $silent);
        if ($user) {
            if (!$silent) {
                echo "<script>console.log('🔐 Auth: Remember token validated successfully for user: " . $user['username'] . "');</script>";
            }
            // Create new session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        } else {
            if (!$silent) {
                echo "<script>console.log('🔐 Auth: Remember token validation failed');</script>";
            }
        }
    } else {
        if (!$silent) {
            echo "<script>console.log('🔐 Auth: No remember token cookie found');</script>";
        }
    }
    
    if (!$silent) {
        echo "<script>console.log('🔐 Auth: Authentication failed - redirecting to login');</script>";
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
        header('Location: login.php');
        exit;
    }
}
?> 