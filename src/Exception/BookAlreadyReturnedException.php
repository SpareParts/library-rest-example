<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * Exception thrown when attempting to return a book that is not currently borrowed
 *
 * Returns HTTP 400 status code
 */
class BookAlreadyReturnedException extends Exception
{
    /**
     * Create new BookAlreadyReturnedException
     *
     * @param int $bookId ID of the book that is not currently borrowed
     */
    public function __construct(int $bookId)
    {
        parent::__construct("Book with ID {$bookId} is not currently borrowed", 400);
    }
}
