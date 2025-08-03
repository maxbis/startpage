<?php
/**
 * Email verification system for user registration
 */
class EmailVerification {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTable();
    }
    
    /**
     * Create verification table
     */
    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (token),
            INDEX (expires_at)
        )";
        $this->pdo->exec($sql);
    }
    
    /**
     * Create verification token for user
     */
    public function createVerification($userId, $email) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 3600)); // 24 hours
        
        $stmt = $this->pdo->prepare("
            INSERT INTO email_verifications (user_id, email, token, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $email, $token, $expiresAt]);
        
        return $token;
    }
    
    /**
     * Verify email token
     */
    public function verifyToken($token) {
        // Clean up expired tokens
        $this->cleanup();
        
        $stmt = $this->pdo->prepare("
            SELECT user_id, email FROM email_verifications 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($verification) {
            // Mark user as verified (you could add a verified column to users table)
            $this->deleteVerification($token);
            return $verification;
        }
        
        return false;
    }
    
    /**
     * Delete verification token
     */
    private function deleteVerification($token) {
        $stmt = $this->pdo->prepare("DELETE FROM email_verifications WHERE token = ?");
        $stmt->execute([$token]);
    }
    
    /**
     * Clean up expired tokens
     */
    private function cleanup() {
        $stmt = $this->pdo->prepare("DELETE FROM email_verifications WHERE expires_at < NOW()");
        $stmt->execute();
    }
    
    /**
     * Send verification email (placeholder - implement your email sending)
     */
    public function sendVerificationEmail($email, $token, $username) {
        $verificationUrl = "https://yourdomain.com/verify.php?token=" . $token;
        
        $subject = "Verify your Startpage account";
        $message = "
        Hello $username,
        
        Please verify your email address by clicking this link:
        $verificationUrl
        
        This link will expire in 24 hours.
        
        If you didn't create this account, please ignore this email.
        ";
        
        $headers = "From: noreply@yourdomain.com";
        
        // For now, just log the email (implement proper email sending)
        error_log("Verification email to $email: $verificationUrl");
        
        return mail($email, $subject, $message, $headers);
    }
}
?> 