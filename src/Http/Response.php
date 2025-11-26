<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

/**
 * HTTP Response helper class
 *
 * Provides convenient methods for creating JSON responses with proper
 * status codes and headers. Ensures consistent response format across
 * the application.
 */
class Response
{
    /**
     * Create a JSON response
     *
     * @param array<string, mixed> $data Response data
     * @param int $statusCode HTTP status code
     * @return string JSON encoded response
     * @throws JsonException If JSON encoding fails
     */
    public static function json(
        array $data,
        int $statusCode = 200
    ): string {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create an error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code (defaults to 400)
     * @return string JSON encoded error response
     * @throws JsonException If JSON encoding fails
     */
    public static function error(
        string $message,
        int $statusCode = 400
    ): string {
        return self::json([
            'error' => $message,
            'status' => $statusCode,
        ], $statusCode);
    }

    /**
     * Create a success response with data
     *
     * @param array<string, mixed> $data Response data
     * @param int $statusCode HTTP status code (defaults to 200)
     * @return string JSON encoded success response
     * @throws JsonException If JSON encoding fails
     */
    public static function success(
        array $data,
        int $statusCode = 200
    ): string {
        return self::json([
            'data' => $data,
            'status' => $statusCode,
        ], $statusCode);
    }

    /**
     * Create a 404 Not Found response
     *
     * @param string $message Error message
     * @return string JSON encoded error response
     * @throws JsonException If JSON encoding fails
     */
    public static function notFound(string $message = 'Resource not found'): string
    {
        return self::error($message, 404);
    }

    /**
     * Create a 422 Unprocessable Entity response for validation errors
     *
     * @param array<string, string> $errors Validation errors
     * @return string JSON encoded error response
     * @throws JsonException If JSON encoding fails
     */
    public static function validationError(array $errors): string
    {
        return self::json([
            'error' => 'Validation failed',
            'errors' => $errors,
            'status' => 422,
        ], 422);
    }

    /**
     * Create a 500 Internal Server Error response
     *
     * @param string $message Error message
     * @return string JSON encoded error response
     * @throws JsonException If JSON encoding fails
     */
    public static function internalError(string $message = 'Internal server error'): string
    {
        return self::error($message, 500);
    }
}
