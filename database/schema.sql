-- Library REST API Database Schema
-- Created: November 26, 2025

-- Disable foreign key checks to allow dropping tables
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS book_borrows;
DROP TABLE IF EXISTS books;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Books table: stores core book information
CREATE TABLE books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Book borrows table: tracks borrowing history and current status
CREATE TABLE book_borrows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    borrowed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    returned_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    INDEX idx_book_id (book_id),
    INDEX idx_borrowed_at (borrowed_at),
    INDEX idx_active_borrows (book_id, returned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
INSERT INTO books (title, author) VALUES
    ('The PHP Manual', 'PHP Documentation Team'),
    ('Clean Code', 'Robert C. Martin'),
    ('Design Patterns', 'Gang of Four'),
    ('Refactoring', 'Martin Fowler'),
    ('The Pragmatic Programmer', 'Andrew Hunt and David Thomas');
