-- PostgreSQL Database Schema for Simple Blog Platform
-- This schema creates the necessary tables for blog post storage

-- Create posts table
CREATE TABLE IF NOT EXISTS posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at DESC);

-- Comments for documentation
COMMENT ON TABLE posts IS 'Stores blog posts with title, content, and creation timestamp';
COMMENT ON COLUMN posts.id IS 'Auto-incrementing primary key';
COMMENT ON COLUMN posts.title IS 'Blog post title (max 255 characters)';
COMMENT ON COLUMN posts.content IS 'Blog post content (unlimited text)';
COMMENT ON COLUMN posts.created_at IS 'Timestamp when the post was created';
