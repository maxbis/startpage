<?php
require_once '../includes/session_config.php';
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

// Redirect if already logged in
if (isAuthenticated($pdo)) {
    header('Location: index.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Handle remember me
            if ($rememberMe) {
                // Create new remember token (allow multiple tokens per user)
                $token = createRememberToken($pdo, $user['id']);
                setRememberCookie($token);
            }
            
            // Redirect to start page
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - My Start Page</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../public/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-300 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-500 mb-2">📌 My Start Page</h1>
            <p class="text-gray-600">Sign in to access your bookmarks</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"
                    required
                    autofocus
                >
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"
                    required
                >
            </div>
            
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="remember_me" 
                    name="remember_me" 
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    checked
                >
                <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                    Remember me
                </label>
            </div>
            
            <button 
                type="submit" 
                class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 transition font-medium"
            >
                Sign In
            </button>
        </form>
        
        <!-- <div class="mt-6 text-center text-sm text-gray-500">
            <p>Default credentials:</p>
            <p class="font-mono text-xs mt-1">Username: admin</p>
            <p class="font-mono text-xs">Password: admin</p>
            <p class="mt-2 text-xs">(Change these in the database after first login)</p>
            
            <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="text-blue-500 hover:text-blue-600 underline">
                        Create one here
                    </a>
                </p>
            </div>
        </div> -->

    </div>
</body>
</html> 