<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Book entity representing a library book
 *
 * Immutable value object with readonly properties.
 */
final readonly class Book
{
    /**
     * Create a new Book instance
     *
     * @param int $id Book ID
     * @param string $title Book title
     * @param string $author Book author
     * @param bool $isAvailable Whether the book is currently available for borrowing
     * @param string|null $borrowedAt Timestamp when currently borrowed (if not available)
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $author,
        public bool $isAvailable,
        public ?string $borrowedAt = null,
    ) {
    }

    /**
     * Create Book instance from database row
     *
     * @param array{id: int|string, title: string, author: string, is_available: int|bool, borrowed_at: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            title: $data['title'],
            author: $data['author'],
            isAvailable: (bool) $data['is_available'],
            borrowedAt: $data['borrowed_at'] ?? null,
        );
    }

    /**
     * Convert Book instance to array
     *
     * @return array{id: int, title: string, author: string, available: bool, borrowed_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'available' => $this->isAvailable,
            'borrowed_at' => $this->borrowedAt,
        ];
    }
}
