#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Database Seeder Script
 *
 * Re-seeds the database by executing the schema.sql file which:
 * - Drops existing tables
 * - Creates fresh table structure
 * - Inserts sample data
 *
 * Usage: php database/seed.php
 * Or via composer: composer db:seed
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Load database configuration
$config = require __DIR__ . '/../config/database.php';

// Database connection DSN
$dsn = sprintf(
    'mysql:host=%s;port=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['charset']
);

try {
    echo "Connecting to database server...\n";

    // Connect to MySQL server (without selecting database)
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Create database if it doesn't exist
    echo "Creating database '{$config['database']}' if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Select the database
    $pdo->exec("USE `{$config['database']}`");

    echo "Reading schema file...\n";

    // Read the schema file
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("Schema file not found: {$schemaFile}");
    }

    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new RuntimeException("Failed to read schema file");
    }

    echo "Executing schema statements...\n";

    // Disable foreign key checks before dropping tables
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    // Remove comments and split by semicolon while preserving multi-line statements
    $lines = explode("\n", $schema);
    $cleanedLines = array_filter(
        array_map('trim', $lines),
        fn (string $line): bool => !empty($line) && !str_starts_with($line, '--')
    );
    $cleanedSchema = implode("\n", $cleanedLines);

    // Split by semicolon and filter out FOREIGN_KEY_CHECKS statements
    $statements = array_filter(
        array_map('trim', explode(';', $cleanedSchema)),
        fn (string $stmt): bool => !empty($stmt) && !str_contains(strtoupper($stmt), 'FOREIGN_KEY_CHECKS')
    );

    // Execute each statement
    $executedCount = 0;
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            $executedCount++;
        }
    }

    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "Successfully executed {$executedCount} SQL statements\n";
    echo "Database seeded successfully!\n";

    // Display some statistics
    $bookCount = $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
    echo "\nSeeded data:\n";
    echo "  - Books: {$bookCount}\n";

    exit(0);
} catch (PDOException $e) {
    echo "Database error: {$e->getMessage()}\n";
    exit(1);
} catch (RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
    exit(1);
} catch (Throwable $e) {
    echo "Unexpected error: {$e->getMessage()}\n";
    exit(1);
}
