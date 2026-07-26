<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/email_verification.php';

$message = '';
$error = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $verification = new EmailVerification($pdo);
    $result = $verification->verifyToken($token);
    
    if ($result) {
        $message = "Email verified successfully! You can now log in.";
    } else {
        $error = "Invalid or expired verification link.";
    }
} else {
    $error = "No verification token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Startpage</title>
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
                <h1 class="wp-page-title">Email Verification</h1>
                <p class="wp-page-lead">Verify your email address</p>
            </div>
            
            <?php if ($message): ?>
                <div class="wp-alert wp-alert--success" role="status">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="wp-alert wp-alert--error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="wp-button-row wp-button-row--end">
                <a href="login.php" class="wp-button wp-button--primary">
                    Go to Login
                </a>
            </div>
        </div>
    </main>
</body>
</html>
