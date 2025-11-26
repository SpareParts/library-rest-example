<?php

declare(strict_types=1);

/**
 * Database configuration
 *
 * Returns database connection parameters from environment variables
 * with sensible defaults for development.
 *
 * @return array{host: string, port: string, database: string, username: string, password: string, charset: string}
 */
return [
    'host' => $_ENV['DB_HOST'] ?? 'mysql',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'library',
    'username' => $_ENV['DB_USERNAME'] ?? 'library_user',
    'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
    'charset' => 'utf8mb4',
];
