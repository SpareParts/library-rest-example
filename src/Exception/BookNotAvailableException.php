<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * Exception thrown when attempting to borrow an unavailable book
 *
 * Returns HTTP 400 status code
 */
class BookNotAvailableException extends Exception
{
    /**
     * Create new BookNotAvailableException
     *
     * @param int $bookId ID of the book that is not available
     */
    public function __construct(int $bookId)
    {
        parent::__construct("Book with ID {$bookId} is not available for borrowing", 400);
    }
}
