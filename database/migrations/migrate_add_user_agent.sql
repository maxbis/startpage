-- Migration script to add user_agent and ip_address columns
-- Run this on existing databases

-- Add new columns to remember_tokens table
ALTER TABLE remember_tokens 
ADD COLUMN user_agent VARCHAR(200) AFTER token,
ADD COLUMN ip_address VARCHAR(45) AFTER user_agent;

-- Update existing tokens with default values (optional)
-- UPDATE remember_tokens SET user_agent = 'Unknown', ip_address = 'Unknown' WHERE user_agent IS NULL;

-- Add index for better performance
CREATE INDEX idx_remember_tokens_user_agent ON remember_tokens(user_agent);
CREATE INDEX idx_remember_tokens_ip_address ON remember_tokens(ip_address); 