<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BookAlreadyReturnedException;
use App\Exception\BookNotAvailableException;
use App\Exception\BookNotFoundException;
use App\Model\Book;
use App\Repository\BookRepository;

/**
 * Book service for business logic operations
 *
 * Orchestrates book-related business operations, validates business rules,
 * and coordinates between controllers and repositories.
 */
class BookService
{
    /**
     * Create new BookService instance
     *
     * @param BookRepository $bookRepository Repository for data access
     */
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    /**
     * Get all books with their availability status
     *
     * @return array<Book>
     */
    public function getAllBooks(): array
    {
        return $this->bookRepository->findAll();
    }

    /**
     * Get a single book by ID
     *
     * @param int $id Book ID
     * @return Book Book instance
     * @throws BookNotFoundException If book with given ID does not exist
     */
    public function getBookById(int $id): Book
    {
        $book = $this->bookRepository->findById($id);

        if ($book === null) {
            throw new BookNotFoundException($id);
        }

        return $book;
    }

    /**
     * Borrow a book
     *
     * Business rules:
     * - Book must exist
     * - Book must be available (not currently borrowed)
     *
     * @param int $id Book ID
     * @return Book Updated book instance
     * @throws BookNotFoundException If book does not exist
     * @throws BookNotAvailableException If book is already borrowed
     */
    public function borrowBook(int $id): Book
    {
        // Check if book exists
        $book = $this->bookRepository->findById($id);

        if ($book === null) {
            throw new BookNotFoundException($id);
        }

        // Check if available
        if (!$book->isAvailable) {
            throw new BookNotAvailableException($id);
        }

        // Perform borrow operation
        $success = $this->bookRepository->borrow($id);

        if (!$success) {
            throw new BookNotAvailableException($id);
        }

        // Return updated book
        $updatedBook = $this->bookRepository->findById($id);

        if ($updatedBook === null) {
            throw new BookNotFoundException($id);
        }

        return $updatedBook;
    }

    /**
     * Return a borrowed book
     *
     * Business rules:
     * - Book must exist
     * - Book must be currently borrowed
     *
     * @param int $id Book ID
     * @return Book Updated book instance
     * @throws BookNotFoundException If book does not exist
     * @throws BookAlreadyReturnedException If book is not currently borrowed
     */
    public function returnBook(int $id): Book
    {
        // Check if book exists
        $book = $this->bookRepository->findById($id);

        if ($book === null) {
            throw new BookNotFoundException($id);
        }

        // Check if actually borrowed
        if ($book->isAvailable) {
            throw new BookAlreadyReturnedException($id);
        }

        // Perform return operation
        $success = $this->bookRepository->return($id);

        if (!$success) {
            throw new BookAlreadyReturnedException($id);
        }

        // Return updated book
        $updatedBook = $this->bookRepository->findById($id);

        if ($updatedBook === null) {
            throw new BookNotFoundException($id);
        }

        return $updatedBook;
    }
}
