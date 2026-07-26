<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/rate_limiter.php';
require_once '../includes/email_verification.php';

// Redirect if already logged in
if (isAuthenticated($pdo)) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Initialize rate limiter
$rateLimiter = new RateLimiter($pdo);
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limiting (5 attempts per hour)
    if (!$rateLimiter->isAllowed($ipAddress, 'register', 5, 3600)) {
        $error = 'Too many registration attempts. Please try again in 1 hour.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $email = trim($_POST['email'] ?? ''); // Optional email for verification
        $honeypot = $_POST['website'] ?? ''; // Honeypot field
        $timestamp = (int)($_POST['timestamp'] ?? 0);
        
        // Honeypot validation (if filled, it's a bot)
        if (!empty($honeypot)) {
            $error = 'Invalid submission';
        }
        // Time-based protection (form must be submitted within reasonable time)
        elseif (time() - $timestamp > 3600) { // 1 hour
            $error = 'Form expired. Please try again.';
        }
        // Enhanced validation
        elseif (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be between 3 and 50 characters';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $error = 'Username can only contain letters, numbers, underscores, and hyphens';
        } elseif (preg_match('/^(admin|root|administrator|test|guest)$/i', $username)) {
            $error = 'Username is not allowed';
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Username already exists';
                } else {
                    // Create new user
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                    $stmt->execute([$username, $passwordHash]);
                    
                    $newUserId = $pdo->lastInsertId();
                    
                    // Create default page for the new user
                    $stmt = $pdo->prepare("INSERT INTO pages (user_id, name, sort_order) VALUES (?, ?, ?)");
                    $stmt->execute([$newUserId, 'My Startpage', 0]);
                    $defaultPageId = $pdo->lastInsertId();
                    
                    // Create default categories for the new user
                    $defaultCategories = [
                        ['Work', 0],
                        ['Personal', 1],
                        ['Tools', 2]
                    ];
                    
                    foreach ($defaultCategories as $category) {
                        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, page_id, sort_order) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$newUserId, $category[0], $defaultPageId, $category[1]]);
                    }
                    
                    $message = "Account created successfully! You can now log in.";
                    
                    // Clear form
                    $username = '';
                    $password = '';
                    $confirmPassword = '';
                    $email = '';
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get remaining attempts for display
$remainingAttempts = $rateLimiter->getRemainingAttempts($ipAddress, 'register', 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Startpage</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../public/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link href="../warm-paper/warm-paper.css?v=<?= filemtime(__DIR__ . '/../warm-paper/warm-paper.css') ?>" rel="stylesheet">
    <link href="../assets/css/warm-paper.css?v=<?= filemtime(__DIR__ . '/../assets/css/warm-paper.css') ?>" rel="stylesheet">
</head>
<body class="wp-theme warm-paper-page">
    <main class="wp-page-shell wp-page-shell--narrow wp-page-shell--centered">
        <div class="wp-panel wp-auth-card">
            <!-- Header -->
            <div class="wp-page-header wp-page-header--centered">
                <h1 class="wp-page-title">Create Account</h1>
                <p class="wp-page-lead">Join the startpage community</p>
                
                <!-- Rate limiting info -->
                <div class="wp-alert wp-alert--info" role="status">
                    <p>
                        <strong>Rate Limit:</strong> <?= $remainingAttempts ?> registration attempts remaining this hour
                    </p>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="wp-alert wp-alert--success wp-alert--stacked" role="status">
                    <?= htmlspecialchars($message) ?>
                    <div>
                        <a href="login.php" class="wp-inline-link">Click here to log in</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="wp-alert wp-alert--error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="wp-stack">
                <div class="wp-field">
                    <label for="username" class="wp-label">
                        Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?= htmlspecialchars($username ?? '') ?>"
                        class="wp-input"
                        placeholder="Choose a username (letters, numbers, _ -)"
                        pattern="[a-zA-Z0-9_-]+"
                        title="Only letters, numbers, underscores, and hyphens allowed"
                        required
                    >
                    <p class="wp-help">3-50 characters, letters, numbers, underscores, hyphens only</p>
                </div>
                
                <div class="wp-field">
                    <label for="password" class="wp-label">
                        Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="wp-input"
                        placeholder="Choose a password (min 8 characters)"
                        minlength="8"
                        required
                    >
                    <p class="wp-help">Minimum 8 characters</p>
                </div>
                
                <div class="wp-field">
                    <label for="confirm_password" class="wp-label">
                        Confirm Password
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="wp-input"
                        placeholder="Confirm your password"
                        required
                    >
                </div>
                
                <!-- Simple honeypot field -->
                <div style="display: none;">
                    <input type="text" name="website" value="">
                </div>
                
                <!-- Time-based protection -->
                <input type="hidden" name="timestamp" value="<?= time() ?>">
                
                <button 
                    type="submit" 
                    class="wp-button wp-button--primary wp-button--block"
                    <?= $remainingAttempts <= 0 ? 'disabled' : '' ?>
                >
                    <?= $remainingAttempts <= 0 ? 'Rate Limit Exceeded' : 'Create Account' ?>
                </button>
            </form>
            
            <div class="wp-auth-card__footer">
                <p>
                    Already have an account? 
                    <a href="login.php" class="wp-inline-link">
                        Log in here
                    </a>
                </p>
            </div>
        </div>
    </main>
</body>
</html>
