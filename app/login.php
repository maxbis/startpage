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
    <link href="../warm-paper/warm-paper.css?v=<?= filemtime(__DIR__ . '/../warm-paper/warm-paper.css') ?>" rel="stylesheet">
    <link href="../assets/css/warm-paper.css?v=<?= filemtime(__DIR__ . '/../assets/css/warm-paper.css') ?>" rel="stylesheet">
</head>
<body class="wp-theme warm-paper-page">
    <main class="wp-page-shell wp-page-shell--narrow wp-page-shell--centered">
    <div class="wp-panel wp-auth-card">
        <div class="wp-page-header wp-page-header--centered">
            <h1 class="wp-page-title">📌 My Start Page</h1>
            <p class="wp-page-lead">Sign in to access your bookmarks</p>
        </div>
        
        <?php if ($error): ?>
            <div class="wp-alert wp-alert--error" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="wp-stack">
            <div class="wp-field">
                <label for="username" class="wp-label">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    class="wp-input"
                    required
                    autofocus
                >
            </div>
            
            <div class="wp-field">
                <label for="password" class="wp-label">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="wp-input"
                    required
                >
            </div>
            
            <div class="wp-check">
                <input 
                    type="checkbox" 
                    id="remember_me" 
                    name="remember_me" 
                    checked
                >
                <label for="remember_me">
                    Remember me
                </label>
            </div>
            
            <button 
                type="submit" 
                class="wp-button wp-button--primary wp-button--block"
            >
                Sign In
            </button>
        </form>
    </div>
    </main>
</body>
</html>
