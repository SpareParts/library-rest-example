<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Book;
use PDO;
use PDOException;

/**
 * Repository for book database operations
 *
 * Handles all database queries related to books and borrowing operations.
 * Uses prepared statements exclusively for security.
 */
class BookRepository
{
    /**
     * Create new BookRepository instance
     *
     * @param PDO $pdo PDO database connection
     */
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * Find all books with their availability status
     *
     * @return array<Book>
     * @throws PDOException If database query fails
     */
    public function findAll(): array
    {
        $sql = <<<'SQL'
            SELECT 
                books.id,
                books.title,
                books.author,
                CASE WHEN bb.id IS NULL THEN 1 ELSE 0 END as is_available,
                bb.borrowed_at
            FROM books
            LEFT JOIN book_borrows bb ON books.id = bb.book_id 
                AND bb.returned_at IS NULL
            ORDER BY books.id ASC
        SQL;

        $stmt = $this->pdo->query($sql);

        if ($stmt === false) {
            throw new PDOException('Failed to execute query');
        }

        /** @var array<array{id: int|string, title: string, author: string, is_available: int|bool, borrowed_at: string|null}> $rows */
        $rows = $stmt->fetchAll();

        return array_map(
            fn (array $row): Book => Book::fromArray($row),
            $rows
        );
    }

    /**
     * Find a book by ID with its availability status
     *
     * @param int $id Book ID
     * @return Book|null Book instance or null if not found
     * @throws PDOException If database query fails
     */
    public function findById(int $id): ?Book
    {
        $sql = <<<'SQL'
            SELECT 
                books.id,
                books.title,
                books.author,
                CASE WHEN bb.id IS NULL THEN 1 ELSE 0 END as is_available,
                bb.borrowed_at
            FROM books
            LEFT JOIN book_borrows bb ON books.id = bb.book_id 
                AND bb.returned_at IS NULL
            WHERE books.id = ?
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        /** @var array{id: int|string, title: string, author: string, is_available: int|bool, borrowed_at: string|null} $row */
        return Book::fromArray($row);
    }

    /**
     * Check if a book is available for borrowing
     *
     * @param int $bookId Book ID
     * @return bool True if available, false otherwise
     * @throws PDOException If database query fails
     */
    public function isAvailable(int $bookId): bool
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) as active_borrows
            FROM book_borrows
            WHERE book_id = ? AND returned_at IS NULL
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bookId]);

        $result = $stmt->fetch();

        if ($result === false) {
            throw new PDOException('Failed to fetch result');
        }

        /** @var array{active_borrows: int|string} $result */
        return ((int) $result['active_borrows']) === 0;
    }

    /**
     * Borrow a book (create a new borrow record)
     *
     * @param int $bookId Book ID to borrow
     * @return bool True if successfully borrowed, false if already borrowed
     * @throws PDOException If database query fails
     */
    public function borrow(int $bookId): bool
    {
        // Check if already borrowed
        if (!$this->isAvailable($bookId)) {
            return false;
        }

        $sql = <<<'SQL'
            INSERT INTO book_borrows (book_id, borrowed_at)
            VALUES (?, CURRENT_TIMESTAMP)
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bookId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Return a borrowed book (update the borrow record)
     *
     * @param int $bookId Book ID to return
     * @return bool True if successfully returned, false if not currently borrowed
     * @throws PDOException If database query fails
     */
    public function return(int $bookId): bool
    {
        // Check if currently borrowed
        if ($this->isAvailable($bookId)) {
            return false;
        }

        $sql = <<<'SQL'
            UPDATE book_borrows 
            SET returned_at = CURRENT_TIMESTAMP
            WHERE book_id = ? AND returned_at IS NULL
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bookId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Check if a book exists in the database
     *
     * @param int $bookId Book ID
     * @return bool True if exists, false otherwise
     * @throws PDOException If database query fails
     */
    public function exists(int $bookId): bool
    {
        $sql = 'SELECT COUNT(*) as count FROM books WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bookId]);

        $result = $stmt->fetch();

        if ($result === false) {
            throw new PDOException('Failed to fetch result');
        }

        /** @var array{count: int|string} $result */
        return ((int) $result['count']) > 0;
    }
}
