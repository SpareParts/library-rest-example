<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Exception\BookAlreadyReturnedException;
use App\Exception\BookNotAvailableException;
use App\Exception\BookNotFoundException;
use App\Model\Book;
use App\Repository\BookRepository;
use App\Service\BookService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BookService
 *
 * Tests business logic in isolation using mocked dependencies
 */
class BookServiceTest extends TestCase
{
    /** @var BookRepository&\PHPUnit\Framework\MockObject\MockObject */
    private BookRepository $bookRepository;
    private BookService $bookService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock repository
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->bookService = new BookService($this->bookRepository);
    }

    #[Test]
    public function testGetAllBooksReturnsArrayOfBooks(): void
    {
        $books = [
            new Book(1, 'Clean Code', 'Robert C. Martin', true, null),
            new Book(2, 'Design Patterns', 'Gang of Four', false, '2025-11-26 10:00:00'),
        ];

        $this->bookRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($books);

        $result = $this->bookService->getAllBooks();

        $this->assertCount(2, $result);
        $this->assertSame($books, $result);
    }

    #[Test]
    public function testGetBookByIdReturnsBook(): void
    {
        $book = new Book(1, 'Clean Code', 'Robert C. Martin', true, null);

        $this->bookRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($book);

        $result = $this->bookService->getBookById(1);

        $this->assertSame($book, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame('Clean Code', $result->title);
    }

    #[Test]
    public function testGetBookByIdThrowsExceptionWhenNotFound(): void
    {
        $this->bookRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(BookNotFoundException::class);
        $this->expectExceptionMessage('Book with ID 999 not found');

        $this->bookService->getBookById(999);
    }

    #[Test]
    public function testBorrowBookSucceeds(): void
    {
        $availableBook = new Book(1, 'Clean Code', 'Robert C. Martin', true, null);
        $borrowedBook = new Book(1, 'Clean Code', 'Robert C. Martin', false, '2025-11-26 10:00:00');

        $this->bookRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with(1)
            ->willReturnOnConsecutiveCalls($availableBook, $borrowedBook);

        $this->bookRepository
            ->expects($this->once())
            ->method('borrow')
            ->with(1)
            ->willReturn(true);

        $result = $this->bookService->borrowBook(1);

        $this->assertSame($borrowedBook, $result);
        $this->assertFalse($result->isAvailable);
        $this->assertNotNull($result->borrowedAt);
    }

    #[Test]
    public function testBorrowBookThrowsExceptionWhenNotFound(): void
    {
        $this->bookRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(BookNotFoundException::class);
        $this->expectExceptionMessage('Book with ID 999 not found');

        $this->bookService->borrowBook(999);
    }

    #[Test]
    public function testBorrowBookThrowsExceptionWhenNotAvailable(): void
    {
        $borrowedBook = new Book(1, 'Clean Code', 'Robert C. Martin', false, '2025-11-26 10:00:00');

        $this->bookRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($borrowedBook);

        $this->expectException(BookNotAvailableException::class);
        $this->expectExceptionMessage('Book with ID 1 is not available for borrowing');

        $this->bookService->borrowBook(1);
    }

    #[Test]
    public function testBorrowBookThrowsExceptionWhenRepositoryFails(): void
    {
        $availableBook = new Book(1, 'Clean Code', 'Robert C. Martin', true, null);

        $this->bookRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($availableBook);

        $this->bookRepository
            ->expects($this->once())
            ->method('borrow')
            ->with(1)
            ->willReturn(false);

        $this->expectException(BookNotAvailableException::class);

        $this->bookService->borrowBook(1);
    }

    #[Test]
    public function testReturnBookSucceeds(): void
    {
        $borrowedBook = new Book(1, 'Clean Code', 'Robert C. Martin', false, '2025-11-26 10:00:00');
        $returnedBook = new Book(1, 'Clean Code', 'Robert C. Martin', true, null);

        $this->bookRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with(1)
            ->willReturnOnConsecutiveCalls($borrowedBook, $returnedBook);

        $this->bookRepository
            ->expects($this->once())
            ->method('return')
            ->with(1)
            ->willReturn(true);

        $result = $this->bookService->returnBook(1);

        $this->assertSame($returnedBook, $result);
        $this->assertTrue($result->isAvailable);
        $this->assertNull($result->borrowedAt);
    }

    #[Test]
    public function testReturnBookThrowsExceptionWhenNotFound(): void
    {
        $this->bookRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(BookNotFoundException::class);
        $this->expectExceptionMessage('Book with ID 999 not found');

        $this->bookService->returnBook(999);
    }

    #[Test]
    public function testReturnBookThrowsExceptionWhenNotBorrowed(): void
    {
        $availableBook = new Book(1, 'Clean Code', 'Robert C. Martin', true, null);

        $this->bookRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($availableBook);

        $this->expectException(BookAlreadyReturnedException::class);
        $this->expectExceptionMessage('Book with ID 1 is not currently borrowed');

        $this->bookService->returnBook(1);
    }

    #[Test]
    public function testReturnBookThrowsExceptionWhenRepositoryFails(): void
    {
        $borrowedBook = new Book(1, 'Clean Code', 'Robert C. Martin', false, '2025-11-26 10:00:00');

        $this->bookRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($borrowedBook);

        $this->bookRepository
            ->expects($this->once())
            ->method('return')
            ->with(1)
            ->willReturn(false);

        $this->expectException(BookAlreadyReturnedException::class);

        $this->bookService->returnBook(1);
    }
}
