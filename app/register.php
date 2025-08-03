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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Create Account</h1>
                <p class="text-gray-600 mt-2">Join the startpage community</p>
                
                <!-- Rate limiting info -->
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-700">
                        <strong>Rate Limit:</strong> <?= $remainingAttempts ?> registration attempts remaining this hour
                    </p>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($message) ?>
                    <div class="mt-2">
                        <a href="login.php" class="text-green-800 underline">Click here to log in</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?= htmlspecialchars($username ?? '') ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Choose a username (letters, numbers, _ -)"
                        pattern="[a-zA-Z0-9_-]+"
                        title="Only letters, numbers, underscores, and hyphens allowed"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">3-50 characters, letters, numbers, underscores, hyphens only</p>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Choose a password (min 8 characters)"
                        minlength="8"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm Password
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md transition-colors"
                    <?= $remainingAttempts <= 0 ? 'disabled' : '' ?>
                >
                    <?= $remainingAttempts <= 0 ? 'Rate Limit Exceeded' : 'Create Account' ?>
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-blue-500 hover:text-blue-600 underline">
                        Log in here
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 