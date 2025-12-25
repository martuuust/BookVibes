-- Migration to add AI song generation fields
-- Run this to update existing database

-- Add new columns to songs table for AI-generated songs
ALTER TABLE songs 
ADD COLUMN IF NOT EXISTS is_ai_generated TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS lyrics TEXT NULL,
ADD COLUMN IF NOT EXISTS melody_description TEXT NULL,
ADD COLUMN IF NOT EXISTS duration INT DEFAULT 0 COMMENT 'Duration in seconds',
ADD COLUMN IF NOT EXISTS voice_gender VARCHAR(20) NULL COMMENT 'male or female',
ADD COLUMN IF NOT EXISTS music_style VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS generation_id VARCHAR(255) NULL COMMENT 'Suno API generation ID',
ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'active' COMMENT 'active, pending_generation, failed';

-- Update existing database schema
ALTER TABLE songs MODIFY COLUMN url VARCHAR(500) NULL COMMENT 'YouTube, Spotify, or AI-generated audio URL';

-- Index for faster queries on AI-generated songs
CREATE INDEX IF NOT EXISTS idx_ai_generated ON songs(is_ai_generated);
CREATE INDEX IF NOT EXISTS idx_status ON songs(status);
