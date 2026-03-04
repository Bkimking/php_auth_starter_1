-- =============================================================
-- Auth Starter Project — Database Schema
-- =============================================================
-- Run this file once to set up your database.
-- Usage: mysql -u root -p < sql/schema.sql
-- =============================================================

-- Create the database if it doesn't already exist
CREATE DATABASE IF NOT EXISTS auth_project_1
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Switch to our database
USE auth_project_1;

-- Drop the table if it exists so we can re-run this file safely
DROP TABLE IF EXISTS users;

-- Create the users table
-- Each column is commented so beginners know what it stores
CREATE TABLE users (
    -- Auto-incrementing primary key
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- User's full name (e.g. "Jane Smith")
    name            VARCHAR(100) NOT NULL,

    -- Email must be unique — used as the login identifier
    email           VARCHAR(150) NOT NULL UNIQUE,

    -- Bcrypt hash of the password (NEVER store plain-text passwords)
    password        VARCHAR(255) NOT NULL,

    -- Role controls which dashboard the user sees after login
    -- Only two allowed values: 'admin' or 'user'
    role            ENUM('admin', 'user') NOT NULL DEFAULT 'user',

    -- Stores a secure random token when the user requests a password reset
    -- NULL means no reset is pending
    reset_token     VARCHAR(64) DEFAULT NULL,

    -- When the reset token expires (1 hour from creation)
    -- After this datetime the token is invalid
    reset_token_expiry DATETIME DEFAULT NULL,

    -- Automatically set to the current timestamp when the row is inserted
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================
-- Sample Data (optional — comment out in production)
-- Password for both accounts is: Password123!
-- Generated with: password_hash('Password123!', PASSWORD_BCRYPT)
-- =============================================================
