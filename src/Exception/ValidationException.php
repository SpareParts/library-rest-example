<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * Exception thrown when validation fails
 *
 * Returns HTTP 422 status code
 */
class ValidationException extends Exception
{
    /**
     * Create new ValidationException
     *
     * @param array<string, string> $errors Validation errors keyed by field name
     */
    public function __construct(
        public readonly array $errors
    ) {
        parent::__construct('Validation failed', 422);
    }
}
