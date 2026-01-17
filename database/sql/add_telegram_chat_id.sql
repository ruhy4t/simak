-- Add telegram_chat_id column to users table
-- Run this SQL on your database

ALTER TABLE users ADD COLUMN telegram_chat_id VARCHAR(50) NULL AFTER no_kontak;

-- Create index for faster lookup when sending notifications to Dinas users
CREATE INDEX idx_users_role_telegram ON users(role, telegram_chat_id);