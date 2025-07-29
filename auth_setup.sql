-- Authentication tables for start page
-- Run this after your existing setup.sql

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Remember Me tokens table
CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (token),
    INDEX (expires_at)
);

-- Insert default user (password: 'admin' - change this!)
INSERT INTO users (username, password_hash) VALUES 
('admin', '$2y$10$B5IrFflIRCRVO6EMo/XT9OBouo/p5Huy4HhhO1jGuhw5QUriv.3QS');

-- Clean up expired tokens (run this periodically)
-- DELETE FROM remember_tokens WHERE expires_at < NOW(); 