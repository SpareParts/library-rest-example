<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BookAlreadyReturnedException;
use App\Exception\BookNotAvailableException;
use App\Exception\BookNotFoundException;
use App\Http\Response;
use App\Service\BookService;

/**
 * Book controller for handling HTTP requests
 *
 * Handles all book-related HTTP endpoints, parses requests,
 * calls service methods, and formats responses.
 */
class BookController
{
    /**
     * Create new BookController instance
     *
     * @param BookService $bookService Service for business logic
     */
    public function __construct(
        private readonly BookService $bookService
    ) {
    }

    /**
     * List all books
     *
     * GET /books
     *
     * @return string JSON response
     */
    public function index(): string
    {
        try {
            $books = $this->bookService->getAllBooks();

            return Response::success([
                'books' => array_map(
                    fn ($book) => [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'available' => $book->isAvailable,
                    ],
                    $books
                ),
            ]);
        } catch (\Throwable $e) {
            return Response::internalError($e->getMessage());
        }
    }

    /**
     * Get a single book by ID
     *
     * GET /books/{id}
     *
     * @param array<string, string> $params Route parameters
     * @return string JSON response
     */
    public function show(array $params): string
    {
        try {
            $id = (int) $params['id'];
            $book = $this->bookService->getBookById($id);

            return Response::success([
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'available' => $book->isAvailable,
                    'borrowed_at' => $book->borrowedAt,
                ],
            ]);
        } catch (BookNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (\Throwable $e) {
            return Response::internalError($e->getMessage());
        }
    }

    /**
     * Borrow a book
     *
     * POST /books/{id}/borrow
     *
     * @param array<string, string> $params Route parameters
     * @return string JSON response
     */
    public function borrow(array $params): string
    {
        try {
            $id = (int) $params['id'];
            $book = $this->bookService->borrowBook($id);

            return Response::success([
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'available' => $book->isAvailable,
                    'borrowed_at' => $book->borrowedAt,
                ],
                'message' => 'Book borrowed successfully',
            ]);
        } catch (BookNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (BookNotAvailableException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return Response::internalError($e->getMessage());
        }
    }

    /**
     * Return a borrowed book
     *
     * POST /books/{id}/return
     *
     * @param array<string, string> $params Route parameters
     * @return string JSON response
     */
    public function return(array $params): string
    {
        try {
            $id = (int) $params['id'];
            $book = $this->bookService->returnBook($id);

            return Response::success([
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'available' => $book->isAvailable,
                    'borrowed_at' => $book->borrowedAt,
                ],
                'message' => 'Book returned successfully',
            ]);
        } catch (BookNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (BookAlreadyReturnedException $e) {
            return Response::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return Response::internalError($e->getMessage());
        }
    }
}
