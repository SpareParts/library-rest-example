<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\BookController;
use App\Database\Connection;
use App\Http\Response;
use App\Http\Router;
use App\Repository\BookRepository;
use App\Service\BookService;
use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Initialize dependencies (manual dependency injection)
try {
    $pdo = Connection::getInstance();
    $bookRepository = new BookRepository($pdo);
    $bookService = new BookService($bookRepository);
    $bookController = new BookController($bookService);
} catch (\Throwable $e) {
    echo Response::internalError('Failed to initialize application: ' . $e->getMessage());
    exit(1);
}

// Set up router
$router = new Router();

// Register routes
$router->get('/books', [$bookController, 'index']);
$router->get('/books/{id}', [$bookController, 'show']);
$router->post('/books/{id}/borrow', [$bookController, 'borrow']);
$router->post('/books/{id}/return', [$bookController, 'return']);

// Dispatch request
try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if ($uri === false || $uri === null) {
        echo Response::error('Invalid request URI', 400);
        exit(1);
    }

    echo $router->dispatch($method, $uri);
} catch (\Throwable $e) {
    echo Response::internalError('Internal server error: ' . $e->getMessage());
}
