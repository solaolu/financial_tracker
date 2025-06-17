-- Add currency column to users table
ALTER TABLE users ADD COLUMN currency TEXT DEFAULT 'USD';

-- Add dark_mode_enabled column to users table
ALTER TABLE users ADD COLUMN dark_mode_enabled INTEGER DEFAULT 0;