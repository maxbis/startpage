<?php
/**
 * Simple rate limiter to prevent spam registrations
 */
class RateLimiter {
    private $pdo;
    private $tableName = 'rate_limits';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTable();
    }
    
    /**
     * Create rate limiting table if it doesn't exist
     */
    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action VARCHAR(50) NOT NULL,
            attempts INT DEFAULT 1,
            first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ip_action (ip_address, action),
            INDEX idx_last_attempt (last_attempt)
        )";
        $this->pdo->exec($sql);
    }
    
    /**
     * Check if an action is allowed for an IP
     */
    public function isAllowed($ipAddress, $action, $maxAttempts = 5, $timeWindow = 3600) {
        // Clean up old records
        $this->cleanup($timeWindow);
        
        // Get current attempts for this IP and action
        $stmt = $this->pdo->prepare("
            SELECT attempts, first_attempt 
            FROM {$this->tableName} 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$ipAddress, $action]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            // First attempt
            $this->recordAttempt($ipAddress, $action);
            return true;
        }
        
        // Check if within time window
        $firstAttempt = strtotime($record['first_attempt']);
        $timeElapsed = time() - $firstAttempt;
        
        if ($timeElapsed > $timeWindow) {
            // Reset after time window
            $this->resetAttempts($ipAddress, $action);
            return true;
        }
        
        // Check if under limit
        if ($record['attempts'] < $maxAttempts) {
            $this->incrementAttempts($ipAddress, $action);
            return true;
        }
        
        return false;
    }
    
    /**
     * Record a new attempt
     */
    private function recordAttempt($ipAddress, $action) {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->tableName} (ip_address, action) 
            VALUES (?, ?)
        ");
        $stmt->execute([$ipAddress, $action]);
    }
    
    /**
     * Increment attempt count
     */
    private function incrementAttempts($ipAddress, $action) {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->tableName} 
            SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$ipAddress, $action]);
    }
    
    /**
     * Reset attempts after time window
     */
    private function resetAttempts($ipAddress, $action) {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->tableName} 
            SET attempts = 1, first_attempt = CURRENT_TIMESTAMP, last_attempt = CURRENT_TIMESTAMP 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$ipAddress, $action]);
    }
    
    /**
     * Clean up old records
     */
    private function cleanup($timeWindow) {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->tableName} 
            WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$timeWindow]);
    }
    
    /**
     * Get remaining attempts for an IP
     */
    public function getRemainingAttempts($ipAddress, $action, $maxAttempts = 5) {
        $stmt = $this->pdo->prepare("
            SELECT attempts FROM {$this->tableName} 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$ipAddress, $action]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            return $maxAttempts;
        }
        
        return max(0, $maxAttempts - $record['attempts']);
    }
}
?> 