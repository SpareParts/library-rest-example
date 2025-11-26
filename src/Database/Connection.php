<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

/**
 * Database connection class using Singleton pattern
 *
 * Provides a single PDO instance for the application with proper error handling
 * and configuration for MySQL connections.
 */
class Connection
{
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get PDO instance (creates connection on first call)
     *
     * @throws PDOException If connection fails
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /**
     * Create new PDO connection with proper configuration
     *
     * @throws PDOException If connection fails
     */
    private static function createConnection(): PDO
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );

            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Reset connection instance (useful for testing)
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
