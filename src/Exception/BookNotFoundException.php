<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * Exception thrown when a book is not found
 *
 * Returns HTTP 404 status code
 */
class BookNotFoundException extends Exception
{
    /**
     * Create new BookNotFoundException
     *
     * @param int $bookId ID of the book that was not found
     */
    public function __construct(int $bookId)
    {
        parent::__construct("Book with ID {$bookId} not found", 404);
    }
}
