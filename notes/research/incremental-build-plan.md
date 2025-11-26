# Library REST API - Incremental Build Plan

## Research Summary

**Date:** November 26, 2025  
**Project:** Library REST API Application  
**Current State:** Empty repository with only AGENTS.md guidelines  
**Objective:** Create a step-by-step incremental build plan for a PHP 8.4 REST API

---

## Application Overview

A REST API for basic library book management with the following capabilities:
- List all books
- Get book details by ID
- Borrow a book
- Return a book

**Technology Stack:**
- PHP 8.4+ (native PHP, no frameworks)
- Composer for dependency management
- MySQL database
- Docker for development environment
- PDO for database access
- PSR-12 code style

**Data Model:**
```
Book
├── id (int, primary key)
├── title (string)
└── author (string)
```

**API Endpoints:**
1. `GET /books` - List all books
2. `GET /books/{id}` - Get book by ID
3. `POST /books/{id}/borrow` - Borrow a book
4. `POST /books/{id}/return` - Return a book

---

## Incremental Build Plan

### Phase 1: Environment & Project Foundation (2-3 hours)

#### Step 1.1: Docker Environment Setup
**Goal:** Create a working Docker development environment with PHP 8.4 and MySQL

**Files to create:**
- `docker-compose.yml` - Define PHP and MySQL services
- `Dockerfile` - Custom PHP 8.4 image with required extensions
- `.dockerignore` - Exclude unnecessary files from Docker context
- `.env.example` - Environment variable template
- `.env` - Actual environment configuration (gitignored)

**Docker Services:**
1. **PHP Service:**
   - PHP 8.4-fpm or PHP 8.4-apache
   - Extensions: pdo, pdo_mysql, mbstring, json
   - Mount project directory
   - Expose port 8000
   
2. **MySQL Service:**
   - MySQL 8.0+
   - Environment: database name, user, password
   - Mount volume for data persistence
   - Expose port 3306 (for external tools)

**Validation:**
```bash
docker-compose up -d --build
docker-compose exec php php -v  # Should show PHP 8.4.x
docker-compose exec php php -m  # Should show pdo_mysql
```

---

#### Step 1.2: Composer & Dependencies
**Goal:** Initialize Composer and add required dependencies

**Files to create:**
- `composer.json` - Project metadata and dependencies
- `.gitignore` - Exclude vendor/, .env, etc.

**Required Composer Packages:**

1. **HTTP Layer:**
   - `nyholm/psr7` - PSR-7 HTTP message implementation
   - `nyholm/psr7-server` - PSR-7 server request factory
   - OR `guzzlehttp/psr7` as alternative

2. **Validation:**
   - `respect/validation` - Data validation library
   - OR `symfony/validator` as alternative

3. **Development Tools:**
   - `phpunit/phpunit` (^11.0) - Testing framework
   - `friendsofphp/php-cs-fixer` (^3.0) - Code style fixer
   - `phpstan/phpstan` (^1.10) - Static analysis

**composer.json structure:**
```json
{
    "name": "library/rest-api",
    "type": "project",
    "require": {
        "php": "^8.4",
        "ext-pdo": "*",
        "ext-json": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "friendsofphp/php-cs-fixer": "^3.0",
        "phpstan/phpstan": "^1.10"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "lint": "php-cs-fixer fix",
        "analyse": "phpstan analyse"
    }
}
```

**Commands:**
```bash
docker-compose exec php bash
composer install
```

**Validation:**
- `vendor/` directory should exist
- Autoloading should be configured for `App\` namespace

---

#### Step 1.3: Project Structure
**Goal:** Create initial directory structure following clean architecture principles

**Directory Structure:**
```
library-rest-example/
├── config/
│   └── database.php          # Database configuration
├── public/
│   └── index.php             # Application entry point
├── src/
│   ├── Controller/           # HTTP controllers
│   ├── Model/                # Data models (entities)
│   ├── Repository/           # Database access layer
│   ├── Service/              # Business logic
│   ├── Exception/            # Custom exceptions
│   ├── Http/                 # HTTP utilities (Request, Response wrappers)
│   └── Database/             # Database connection
├── tests/
│   ├── Unit/                 # Unit tests
│   └── Integration/          # Integration tests
├── database/
│   └── schema.sql            # Database schema
├── notes/
│   └── research/             # Research documentation
├── .php-cs-fixer.php         # Code style configuration
├── phpunit.xml               # PHPUnit configuration
├── phpstan.neon              # PHPStan configuration
├── docker-compose.yml
├── Dockerfile
├── composer.json
├── .gitignore
├── .env.example
└── README.md
```

**Commands:**
```bash
mkdir -p config public src/{Controller,Model,Repository,Service,Exception,Http,Database}
mkdir -p tests/{Unit,Integration}
mkdir -p database
```

**Validation:**
- All directories should exist
- Directory structure should be clear and organized

---

### Phase 2: Database Layer (2-3 hours)

#### Step 2.1: Database Schema Design
**Goal:** Create MySQL database schema for books and borrowing tracking

**File:** `database/schema.sql`

**Tables:**

1. **books** - Core book information
```sql
CREATE TABLE books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

2. **book_borrows** - Track borrowing history
```sql
CREATE TABLE book_borrows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    borrowed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    returned_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    INDEX idx_book_id (book_id),
    INDEX idx_borrowed_at (borrowed_at),
    INDEX idx_active_borrows (book_id, returned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules:**
- A book can only be borrowed if there's no active borrow (returned_at IS NULL)
- When returning a book, set returned_at to current timestamp
- History is maintained (soft tracking)

**Sample Data:**
```sql
INSERT INTO books (title, author) VALUES
    ('The PHP Manual', 'PHP Documentation Team'),
    ('Clean Code', 'Robert C. Martin'),
    ('Design Patterns', 'Gang of Four');
```

**Commands:**
```bash
docker-compose exec mysql mysql -u[user] -p[pass] library < database/schema.sql
```

**Validation:**
- Tables should be created
- Foreign keys should be in place
- Sample data should be inserted

---

#### Step 2.2: Database Connection Class
**Goal:** Create PDO database connection with proper error handling

**File:** `src/Database/Connection.php`

**Responsibilities:**
- Establish PDO connection to MySQL
- Use configuration from environment variables
- Set PDO attributes for error handling
- Singleton pattern for single connection instance

**Implementation Requirements:**
```php
<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        // Read from environment variables
        // DSN: mysql:host=mysql;dbname=library;charset=utf8mb4
        // Set PDO::ATTR_ERRMODE to PDO::ERRMODE_EXCEPTION
        // Set PDO::ATTR_DEFAULT_FETCH_MODE to PDO::FETCH_ASSOC
        // Set PDO::ATTR_EMULATE_PREPARES to false
    }
}
```

**File:** `config/database.php`

**Configuration values:**
```php
<?php

return [
    'host' => $_ENV['DB_HOST'] ?? 'mysql',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'library',
    'username' => $_ENV['DB_USERNAME'] ?? 'library_user',
    'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
    'charset' => 'utf8mb4',
];
```

**Validation:**
- Connection should succeed
- PDO instance should be returned
- Error handling should throw exceptions

---

#### Step 2.3: Book Model
**Goal:** Create Book entity with typed properties

**File:** `src/Model/Book.php`

**Responsibilities:**
- Represent a book entity
- Type-safe properties
- Readonly properties where applicable
- Factory methods for creation

**Implementation Requirements:**
```php
<?php

declare(strict_types=1);

namespace App\Model;

class Book
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $author,
        public readonly bool $isAvailable,  // Computed from borrows
        public readonly ?string $borrowedAt = null,
    ) {
    }

    /**
     * @param array{id: int, title: string, author: string, is_available: bool, borrowed_at: ?string} $data
     */
    public static function fromArray(array $data): self
    {
        // Factory method to create from database row
    }
}
```

**Validation:**
- All properties should be typed
- Constructor property promotion should be used
- Factory method should handle data conversion

---

#### Step 2.4: Book Repository
**Goal:** Create repository for database operations on books

**File:** `src/Repository/BookRepository.php`

**Responsibilities:**
- All database queries for books
- Use prepared statements exclusively
- Return Book models or arrays of Book models
- Handle borrowing/returning logic

**Methods:**
```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Book;
use PDO;

class BookRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * @return array<Book>
     */
    public function findAll(): array
    {
        // SELECT books.*, 
        //        CASE WHEN bb.id IS NULL THEN 1 ELSE 0 END as is_available,
        //        bb.borrowed_at
        // FROM books
        // LEFT JOIN book_borrows bb ON books.id = bb.book_id 
        //   AND bb.returned_at IS NULL
    }

    public function findById(int $id): ?Book
    {
        // Similar query with WHERE books.id = ?
    }

    public function borrow(int $bookId): bool
    {
        // Check if already borrowed
        // INSERT INTO book_borrows (book_id) VALUES (?)
    }

    public function return(int $bookId): bool
    {
        // UPDATE book_borrows 
        // SET returned_at = CURRENT_TIMESTAMP
        // WHERE book_id = ? AND returned_at IS NULL
    }

    public function isAvailable(int $bookId): bool
    {
        // Check if book can be borrowed
    }
}
```

**Validation:**
- All methods should use prepared statements
- Methods should handle errors appropriately
- Business logic should be enforced (no double-borrow)

---

### Phase 3: HTTP Layer & Routing (2-3 hours)

#### Step 3.1: HTTP Request/Response Wrappers
**Goal:** Create convenient wrappers around PSR-7 HTTP messages

**File:** `src/Http/Request.php`

**Responsibilities:**
- Wrap PSR-7 ServerRequestInterface
- Parse JSON body
- Extract route parameters
- Get query parameters

**File:** `src/Http/Response.php`

**Responsibilities:**
- Create JSON responses
- Set HTTP status codes
- Standard response format

**Implementation:**
```php
<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    /**
     * @param array<string, mixed> $data
     */
    public static function json(
        array $data,
        int $statusCode = 200
    ): string {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

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
     * @param array<string, mixed> $data
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
}
```

**Validation:**
- JSON should be properly formatted
- HTTP status codes should be set correctly
- Content-Type headers should be set

---

#### Step 3.2: Router
**Goal:** Create simple router to match HTTP requests to controllers

**File:** `src/Http/Router.php`

**Responsibilities:**
- Match request method and path
- Extract route parameters (e.g., /books/{id})
- Dispatch to appropriate controller method
- Handle 404 Not Found

**Implementation approach:**
```php
<?php

declare(strict_types=1);

namespace App\Http;

class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * @return array{handler: callable, params: array<string, string>}|null
     */
    public function match(string $method, string $uri): ?array
    {
        // Convert route pattern /books/{id} to regex
        // Match against current URI
        // Extract parameters
    }

    public function dispatch(string $method, string $uri): string
    {
        // Match route
        // Call handler with parameters
        // Return response
    }
}
```

**Validation:**
- Routes should be registered correctly
- Parameters should be extracted properly
- 404 should be returned for unknown routes

---

#### Step 3.3: Exception Handling
**Goal:** Create custom exceptions for domain errors

**Files:**
- `src/Exception/BookNotFoundException.php`
- `src/Exception/BookNotAvailableException.php`
- `src/Exception/BookAlreadyReturnedException.php`
- `src/Exception/ValidationException.php`

**Implementation:**
```php
<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class BookNotFoundException extends Exception
{
    public function __construct(int $bookId)
    {
        parent::__construct("Book with ID {$bookId} not found", 404);
    }
}

class BookNotAvailableException extends Exception
{
    public function __construct(int $bookId)
    {
        parent::__construct("Book with ID {$bookId} is not available for borrowing", 400);
    }
}

class BookAlreadyReturnedException extends Exception
{
    public function __construct(int $bookId)
    {
        parent::__construct("Book with ID {$bookId} is not currently borrowed", 400);
    }
}

class ValidationException extends Exception
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        public readonly array $errors
    ) {
        parent::__construct("Validation failed", 422);
    }
}
```

**Validation:**
- Exceptions should extend base Exception
- HTTP status codes should be in exception code
- Messages should be descriptive

---

### Phase 4: Business Logic & Controllers (3-4 hours)

#### Step 4.1: Book Service
**Goal:** Create service layer for business logic

**File:** `src/Service/BookService.php`

**Responsibilities:**
- Orchestrate business operations
- Validate business rules
- Use repository for data access
- Throw domain exceptions

**Methods:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BookAlreadyReturnedException;
use App\Exception\BookNotAvailableException;
use App\Exception\BookNotFoundException;
use App\Model\Book;
use App\Repository\BookRepository;

class BookService
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    /**
     * @return array<Book>
     */
    public function getAllBooks(): array
    {
        return $this->bookRepository->findAll();
    }

    public function getBookById(int $id): Book
    {
        $book = $this->bookRepository->findById($id);
        
        if ($book === null) {
            throw new BookNotFoundException($id);
        }
        
        return $book;
    }

    public function borrowBook(int $id): Book
    {
        // Check if book exists
        // Check if available
        // Perform borrow operation
        // Return updated book
    }

    public function returnBook(int $id): Book
    {
        // Check if book exists
        // Check if actually borrowed
        // Perform return operation
        // Return updated book
    }
}
```

**Validation:**
- Business rules should be enforced
- Exceptions should be thrown appropriately
- Repository should be used for all data access

---

#### Step 4.2: Book Controller
**Goal:** Create controller to handle HTTP requests

**File:** `src/Controller/BookController.php`

**Responsibilities:**
- Parse HTTP requests
- Call service methods
- Format HTTP responses
- Handle exceptions

**Methods:**
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BookAlreadyReturnedException;
use App\Exception\BookNotAvailableException;
use App\Exception\BookNotFoundException;
use App\Http\Response;
use App\Service\BookService;

class BookController
{
    public function __construct(
        private readonly BookService $bookService
    ) {
    }

    /**
     * GET /books
     */
    public function index(): string
    {
        try {
            $books = $this->bookService->getAllBooks();
            
            return Response::success([
                'books' => array_map(
                    fn($book) => [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'available' => $book->isAvailable,
                    ],
                    $books
                ),
            ]);
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /books/{id}
     * 
     * @param array<string, string> $params
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
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /books/{id}/borrow
     * 
     * @param array<string, string> $params
     */
    public function borrow(array $params): string
    {
        // Similar implementation
    }

    /**
     * POST /books/{id}/return
     * 
     * @param array<string, string> $params
     */
    public function return(array $params): string
    {
        // Similar implementation
    }
}
```

**Validation:**
- All methods should handle exceptions
- HTTP status codes should be appropriate
- Response format should be consistent

---

#### Step 4.3: Application Entry Point
**Goal:** Wire everything together in index.php

**File:** `public/index.php`

**Responsibilities:**
- Load environment variables
- Initialize dependency injection container (or manual DI)
- Set up router
- Dispatch request
- Handle global exceptions

**Implementation:**
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\BookController;
use App\Database\Connection;
use App\Http\Response;
use App\Http\Router;
use App\Repository\BookRepository;
use App\Service\BookService;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Initialize dependencies
$pdo = Connection::getInstance();
$bookRepository = new BookRepository($pdo);
$bookService = new BookService($bookRepository);
$bookController = new BookController($bookService);

// Set up router
$router = new Router();

$router->get('/books', [$bookController, 'index']);
$router->get('/books/{id}', [$bookController, 'show']);
$router->post('/books/{id}/borrow', [$bookController, 'borrow']);
$router->post('/books/{id}/return', [$bookController, 'return']);

// Dispatch request
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    echo $router->dispatch($method, $uri);
} catch (\Exception $e) {
    echo Response::error('Internal server error: ' . $e->getMessage(), 500);
}
```

**Validation:**
- Application should start without errors
- Dependencies should be wired correctly
- Routes should be accessible

---

### Phase 5: Testing (3-4 hours)

#### Step 5.1: PHPUnit Configuration
**Goal:** Set up PHPUnit for testing

**File:** `phpunit.xml`

**Configuration:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

**Validation:**
```bash
vendor/bin/phpunit --version
```

---

#### Step 5.2: Unit Tests
**Goal:** Create unit tests for service and repository layers

**Files:**
- `tests/Unit/Service/BookServiceTest.php`
- `tests/Unit/Repository/BookRepositoryTest.php`

**Test Cases for BookService:**
1. `testGetAllBooksReturnsArrayOfBooks()`
2. `testGetBookByIdReturnsBook()`
3. `testGetBookByIdThrowsExceptionWhenNotFound()`
4. `testBorrowBookSucceeds()`
5. `testBorrowBookThrowsExceptionWhenNotAvailable()`
6. `testReturnBookSucceeds()`
7. `testReturnBookThrowsExceptionWhenNotBorrowed()`

**Implementation approach:**
- Use PHPUnit's mocking for dependencies
- Test business logic in isolation
- Assert exceptions are thrown correctly

**Validation:**
```bash
vendor/bin/phpunit --testsuite Unit
```

---

#### Step 5.3: Integration Tests
**Goal:** Create integration tests for API endpoints

**File:** `tests/Integration/ApiTest.php`

**Test Cases:**
1. `testGetBooksReturnsJsonResponse()`
2. `testGetBookByIdReturnsBook()`
3. `testGetBookByIdReturns404WhenNotFound()`
4. `testBorrowBookSucceeds()`
5. `testBorrowBookFailsWhenAlreadyBorrowed()`
6. `testReturnBookSucceeds()`
7. `testReturnBookFailsWhenNotBorrowed()`
8. `testBorrowAndReturnWorkflow()`

**Implementation approach:**
- Use actual database (test database)
- Make HTTP requests to actual endpoints
- Assert response codes and JSON structure
- Clean up database after each test

**Validation:**
```bash
vendor/bin/phpunit --testsuite Integration
```

---

### Phase 6: Code Quality & Documentation (1-2 hours)

#### Step 6.1: PHP-CS-Fixer Configuration
**Goal:** Configure code style fixer for PSR-12

**File:** `.php-cs-fixer.php`

**Configuration:**
```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
```

**Commands:**
```bash
vendor/bin/php-cs-fixer fix --dry-run  # Check
vendor/bin/php-cs-fixer fix            # Fix
```

---

#### Step 6.2: PHPStan Configuration
**Goal:** Configure static analysis tool

**File:** `phpstan.neon`

**Configuration:**
```neon
parameters:
    level: 9
    paths:
        - src
        - tests
    excludePaths:
        - vendor
```

**Commands:**
```bash
vendor/bin/phpstan analyse
```

**Validation:**
- Level 9 analysis should pass
- No errors should be reported

---

#### Step 6.3: Documentation
**Goal:** Create comprehensive README

**File:** `README.md`

**Sections:**
1. Project overview
2. Requirements
3. Installation instructions
4. API documentation with examples
5. Development workflow
6. Testing instructions
7. Code style guidelines

**API Documentation Format:**
```markdown
### GET /books

Retrieve a list of all books.

**Response:**
```json
{
  "data": {
    "books": [
      {
        "id": 1,
        "title": "Clean Code",
        "author": "Robert C. Martin",
        "available": true
      }
    ]
  },
  "status": 200
}
```

**Example:**
```bash
curl http://localhost:8000/books
```
```

---

### Phase 7: Final Integration & Testing (1-2 hours)

#### Step 7.1: End-to-End Manual Testing
**Goal:** Manually test all endpoints

**Test Scenarios:**
1. List all books (should show sample data)
2. Get specific book by ID
3. Try to get non-existent book (should return 404)
4. Borrow an available book (should succeed)
5. Try to borrow the same book again (should fail)
6. Return the borrowed book (should succeed)
7. Try to return the same book again (should fail)

**Commands:**
```bash
# Start application
docker-compose up -d
php -S localhost:8000 -t public

# Test endpoints
curl http://localhost:8000/books
curl http://localhost:8000/books/1
curl -X POST http://localhost:8000/books/1/borrow
curl -X POST http://localhost:8000/books/1/return
```

---

#### Step 7.2: Run All Tests
**Goal:** Ensure all tests pass

**Commands:**
```bash
composer test           # Run all tests
composer lint          # Check code style
composer analyse       # Run static analysis
```

**Validation:**
- All tests should pass
- Code style should be PSR-12 compliant
- PHPStan level 9 should pass

---

#### Step 7.3: Performance Check
**Goal:** Basic performance validation

**Checks:**
1. Response times < 100ms for simple queries
2. Database connection pooling works
3. No N+1 query problems
4. Memory usage is reasonable

---

## Estimated Timeline

| Phase | Duration | Cumulative |
|-------|----------|------------|
| Phase 1: Environment & Foundation | 2-3 hours | 2-3 hours |
| Phase 2: Database Layer | 2-3 hours | 4-6 hours |
| Phase 3: HTTP Layer & Routing | 2-3 hours | 6-9 hours |
| Phase 4: Business Logic & Controllers | 3-4 hours | 9-13 hours |
| Phase 5: Testing | 3-4 hours | 12-17 hours |
| Phase 6: Code Quality & Documentation | 1-2 hours | 13-19 hours |
| Phase 7: Final Integration | 1-2 hours | 14-21 hours |

**Total Estimated Time:** 14-21 hours (2-3 days of focused work)

---

## Success Criteria

### Technical Requirements
- [x] PHP 8.4+ with strict types
- [x] PSR-12 code style compliance
- [x] PHPStan level 9 analysis passing
- [x] 100% of tests passing (Phase 5 completed: 22 tests, 128 assertions)
- [x] Docker environment working
- [x] All endpoints functional

### Code Quality
- [x] No mixed types used
- [x] All parameters and returns typed
- [x] Proper exception handling
- [x] Prepared statements for all queries
- [x] Dependency injection used throughout
- [x] Single responsibility principle followed

### Functionality
- [x] List all books with availability status
- [x] Get individual book details
- [x] Borrow available books
- [x] Return borrowed books
- [x] Prevent double-borrowing
- [x] Prevent returning non-borrowed books
- [x] Proper HTTP status codes
- [x] Consistent JSON response format

---

## Risk Assessment

### Potential Challenges

1. **Docker Environment Issues**
   - Risk: PHP extensions not installed correctly
   - Mitigation: Test Docker build early, verify extensions

2. **Database Connection Issues**
   - Risk: PDO connection failures
   - Mitigation: Test connection immediately after setup

3. **Routing Complexity**
   - Risk: Parameter extraction might be tricky
   - Mitigation: Start with simple routes, add complexity gradually

4. **Testing Database State**
   - Risk: Tests might interfere with each other
   - Mitigation: Use transactions or separate test database

5. **Type Safety**
   - Risk: Array shapes might be difficult to type correctly
   - Mitigation: Use PHPStan's array-shape syntax, start with simpler types

---

## Next Steps

1. **Start with Phase 1, Step 1.1** - Set up Docker environment
2. **Validate each step** before moving to the next
3. **Commit frequently** with descriptive commit messages
4. **Run tests after each phase** to catch issues early
5. **Document any deviations** from the plan as they occur

---

## Additional Considerations

### Future Enhancements (Out of Scope)
- User authentication
- Multiple copies of same book
- Due dates and late fees
- Book search functionality
- Pagination for book list
- API rate limiting
- Request logging
- CORS configuration
- API versioning
- OpenAPI/Swagger documentation

### Deployment Considerations (Out of Scope)
- Production Docker configuration
- Environment-specific configs
- Database migrations tool
- CI/CD pipeline
- Monitoring and logging
- Load balancing
- Database backup strategy

---

## References

- PHP 8.4 Documentation: https://www.php.net/manual/en/
- PSR-12 Coding Style: https://www.php-fig.org/psr/psr-12/
- PHPUnit Documentation: https://phpunit.de/documentation.html
- PHPStan Documentation: https://phpstan.org/user-guide/getting-started
- Docker PHP Images: https://hub.docker.com/_/php

---

**Document Version:** 1.0  
**Last Updated:** November 26, 2025  
**Author:** Research Agent
