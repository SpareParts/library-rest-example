<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\Connection;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Library REST API
 *
 * Tests complete API workflows including database interactions
 */
class ApiTest extends TestCase
{
    private PDO $pdo;
    private string $baseUrl = 'http://localhost:8000';

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize database connection for test setup/cleanup
        $this->pdo = Connection::getInstance();

        // Clean up and prepare test data
        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data after each test
        $this->cleanupTestDatabase();
    }

    private function setupTestDatabase(): void
    {
        // Clear existing data
        $this->pdo->exec('DELETE FROM book_borrows');
        $this->pdo->exec('DELETE FROM books');

        // Insert test books
        $stmt = $this->pdo->prepare(
            'INSERT INTO books (id, title, author) VALUES (?, ?, ?)'
        );

        $stmt->execute([1, 'Clean Code', 'Robert C. Martin']);
        $stmt->execute([2, 'Design Patterns', 'Gang of Four']);
        $stmt->execute([3, 'The PHP Manual', 'PHP Documentation Team']);
    }

    private function cleanupTestDatabase(): void
    {
        // Clean up test data
        $this->pdo->exec('DELETE FROM book_borrows');
        $this->pdo->exec('DELETE FROM books');
    }

    /**
     * Make HTTP request to API
     *
     * @param array<string, mixed> $options
     * @return array{status: int, body: string, data: array<string, mixed>}
     */
    private function makeRequest(string $method, string $path, array $options = []): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);

        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize curl');
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (isset($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['body']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Failed to execute curl request');
        }

        // Ensure response is a string (curl_exec can return true for some protocols)
        if (!is_string($response)) {
            $response = '';
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($response, true) ?? [];

        return [
            'status' => $statusCode,
            'body' => $response,
            'data' => $data,
        ];
    }

    #[Test]
    public function testGetBooksReturnsJsonResponse(): void
    {
        $response = $this->makeRequest('GET', '/books');

        $this->assertSame(200, $response['status']);
        $this->assertArrayHasKey('data', $response['data']);
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $data */
        $data = $response['data']['data'];
        $this->assertArrayHasKey('books', $data);
        $this->assertIsArray($data['books']);
        $this->assertCount(3, $data['books']);
    }

    #[Test]
    public function testGetBookByIdReturnsBook(): void
    {
        $response = $this->makeRequest('GET', '/books/1');

        $this->assertSame(200, $response['status']);
        $this->assertArrayHasKey('data', $response['data']);
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $data */
        $data = $response['data']['data'];
        $this->assertArrayHasKey('book', $data);
        $this->assertIsArray($data['book']);
        /** @var array<string, mixed> $book */
        $book = $data['book'];

        $this->assertSame(1, $book['id']);
        $this->assertSame('Clean Code', $book['title']);
        $this->assertSame('Robert C. Martin', $book['author']);
        $this->assertTrue($book['available']);
        $this->assertNull($book['borrowed_at']);
    }

    #[Test]
    public function testGetBookByIdReturns404WhenNotFound(): void
    {
        $response = $this->makeRequest('GET', '/books/999');

        $this->assertSame(404, $response['status']);
        $this->assertArrayHasKey('error', $response['data']);
        $this->assertIsString($response['data']['error']);
        /** @var string $error */
        $error = $response['data']['error'];
        $this->assertStringContainsString('not found', $error);
    }

    #[Test]
    public function testReturnBookSucceeds(): void
    {
        // Borrow the book first
        $response = $this->makeRequest('POST', '/books/1/borrow');
        $this->assertSame(200, $response['status']);
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $borrowData */
        $borrowData = $response['data']['data'];
        $this->assertIsArray($borrowData['book']);
        /** @var array<string, mixed> $borrowedBook */
        $borrowedBook = $borrowData['book'];
        $this->assertFalse($borrowedBook['available']);

        // Return the book
        $response = $this->makeRequest('POST', '/books/1/return');

        $this->assertSame(200, $response['status']);
        $this->assertArrayHasKey('data', $response['data']);
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $returnData */
        $returnData = $response['data']['data'];
        $this->assertArrayHasKey('book', $returnData);

        $this->assertIsArray($returnData['book']);
        /** @var array<string, mixed> $book */
        $book = $returnData['book'];
        $this->assertSame(1, $book['id']);
        $this->assertTrue($book['available']);
        $this->assertNull($book['borrowed_at']);

        // Verify book is now available
        $response = $this->makeRequest('GET', '/books/1');
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $verifyData */
        $verifyData = $response['data']['data'];
        $this->assertIsArray($verifyData['book']);
        /** @var array<string, mixed> $verifyBook */
        $verifyBook = $verifyData['book'];
        $this->assertTrue($verifyBook['available']);
    }

    #[Test]
    public function testReturnBookFailsWhenNotBorrowed(): void
    {
        // Try to return a book that hasn't been borrowed
        $response = $this->makeRequest('POST', '/books/1/return');

        $this->assertSame(400, $response['status']);
        $this->assertArrayHasKey('error', $response['data']);
        $this->assertIsString($response['data']['error']);
        /** @var string $error */
        $error = $response['data']['error'];
        $this->assertStringContainsString('not currently borrowed', $error);
    }

    #[Test]
    public function testReturnBookFailsWhenNotFound(): void
    {
        $response = $this->makeRequest('POST', '/books/999/return');

        $this->assertSame(404, $response['status']);
        $this->assertArrayHasKey('error', $response['data']);
        $this->assertIsString($response['data']['error']);
        /** @var string $error */
        $error = $response['data']['error'];
        $this->assertStringContainsString('not found', $error);
    }

    #[Test]
    public function testBorrowAndReturnWorkflow(): void
    {
        // Initial state: all books available
        $response = $this->makeRequest('GET', '/books');
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $data */
        $data = $response['data']['data'];
        $this->assertIsArray($data['books']);
        /** @var array<int, array<string, mixed>> $books */
        $books = $data['books'];

        foreach ($books as $book) {
            $this->assertIsArray($book);
            $this->assertArrayHasKey('available', $book);
            $this->assertArrayHasKey('id', $book);
            $this->assertIsInt($book['id']);
            /** @var int $bookId */
            $bookId = $book['id'];
            $this->assertTrue($book['available'], "Book {$bookId} should be available initially");
        }

        // Borrow book 1
        $response = $this->makeRequest('POST', '/books/1/borrow');
        $this->assertSame(200, $response['status']);
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $borrowData1 */
        $borrowData1 = $response['data']['data'];
        $this->assertIsArray($borrowData1['book']);
        /** @var array<string, mixed> $book1 */
        $book1 = $borrowData1['book'];
        $this->assertFalse($book1['available']);

        // Borrow book 2
        $response = $this->makeRequest('POST', '/books/2/borrow');
        $this->assertSame(200, $response['status']);
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $borrowData2 */
        $borrowData2 = $response['data']['data'];
        $this->assertIsArray($borrowData2['book']);
        /** @var array<string, mixed> $book2 */
        $book2 = $borrowData2['book'];
        $this->assertFalse($book2['available']);

        // Verify books list shows correct availability
        $response = $this->makeRequest('GET', '/books');
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $responseData */
        $responseData = $response['data']['data'];
        $this->assertIsArray($responseData['books']);
        /** @var array<int, array<string, mixed>> $books */
        $books = $responseData['books'];
        $this->assertIsArray($books[0]);
        $this->assertIsArray($books[1]);
        $this->assertIsArray($books[2]);
        $this->assertFalse($books[0]['available'], 'Book 1 should not be available');
        $this->assertFalse($books[1]['available'], 'Book 2 should not be available');
        $this->assertTrue($books[2]['available'], 'Book 3 should still be available');

        // Return book 1
        $response = $this->makeRequest('POST', '/books/1/return');
        $this->assertSame(200, $response['status']);
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $returnData */
        $returnData = $response['data']['data'];
        $this->assertIsArray($returnData['book']);
        /** @var array<string, mixed> $book */
        $book = $returnData['book'];
        $this->assertTrue($book['available']);

        // Verify book 1 is available again, book 2 still borrowed
        $response = $this->makeRequest('GET', '/books');
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $responseData2 */
        $responseData2 = $response['data']['data'];
        $this->assertIsArray($responseData2['books']);
        /** @var array<int, array<string, mixed>> $books2 */
        $books2 = $responseData2['books'];
        $this->assertIsArray($books2[0]);
        $this->assertIsArray($books2[1]);
        $this->assertIsArray($books2[2]);
        $this->assertTrue($books2[0]['available'], 'Book 1 should be available again');
        $this->assertFalse($books2[1]['available'], 'Book 2 should still not be available');
        $this->assertTrue($books2[2]['available'], 'Book 3 should still be available');

        // Return book 2
        $response = $this->makeRequest('POST', '/books/2/return');
        $this->assertSame(200, $response['status']);

        // Verify all books available again
        $response = $this->makeRequest('GET', '/books');
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $responseData3 */
        $responseData3 = $response['data']['data'];
        $this->assertIsArray($responseData3['books']);
        /** @var array<int, array<string, mixed>> $books3 */
        $books3 = $responseData3['books'];

        foreach ($books3 as $book) {
            $this->assertIsArray($book);
            $this->assertArrayHasKey('available', $book);
            $this->assertArrayHasKey('id', $book);
            $this->assertIsInt($book['id']);
            /** @var int $bookIdValue */
            $bookIdValue = $book['id'];
            $this->assertTrue($book['available'], "Book {$bookIdValue} should be available at the end");
        }
    }

    #[Test]
    public function testMultipleBorrowReturnCycles(): void
    {
        // Borrow and return the same book multiple times
        for ($i = 0; $i < 3; $i++) {
            // Borrow
            $response = $this->makeRequest('POST', '/books/1/borrow');
            $this->assertSame(200, $response['status'], "Borrow cycle {$i}: borrow should succeed");
            $this->assertIsArray($response['data']['data']);
            /** @var array<string, mixed> $borrowCycleData */
            $borrowCycleData = $response['data']['data'];
            $this->assertIsArray($borrowCycleData['book']);
            /** @var array<string, mixed> $borrowCycleBook */
            $borrowCycleBook = $borrowCycleData['book'];
            $this->assertFalse($borrowCycleBook['available']);

            // Return
            $response = $this->makeRequest('POST', '/books/1/return');
            $this->assertSame(200, $response['status'], "Borrow cycle {$i}: return should succeed");
            $this->assertIsArray($response['data']['data']);
            /** @var array<string, mixed> $returnCycleData */
            $returnCycleData = $response['data']['data'];
            $this->assertIsArray($returnCycleData['book']);
            /** @var array<string, mixed> $returnCycleBook */
            $returnCycleBook = $returnCycleData['book'];
            $this->assertTrue($returnCycleBook['available']);
        }

        // Verify book is available at the end
        $response = $this->makeRequest('GET', '/books/1');
        $this->assertIsArray($response['data']['data']);
        /** @var array<string, mixed> $finalData */
        $finalData = $response['data']['data'];
        $this->assertIsArray($finalData['book']);
        /** @var array<string, mixed> $finalBook */
        $finalBook = $finalData['book'];
        $this->assertTrue($finalBook['available']);
    }
}
