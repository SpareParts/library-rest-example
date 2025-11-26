# Library REST API - Developer Documentation

Welcome! This is an onboarding guide to help you get started with the Library REST API project.

## Project Overview

This is a simple **Library Management REST API** built with PHP 8.4+ that allows users to:
- View all available books in the library
- Check details of a specific book
- Borrow books
- Return borrowed books

The API tracks book availability and borrowing history in a MySQL database.

### Technology Stack

- **PHP 8.4+** with strict typing and modern features
- **MySQL 8.0** for data persistence
- **Docker & Docker Compose** for containerized development
- **PHPUnit** for testing
- **PHPStan** for static analysis
- **PHP CS Fixer** for code formatting

## Getting Started

### Prerequisites

- Docker Desktop installed and running
- Basic understanding of REST APIs and PHP

### Step 1: Start the Application

All commands must be run inside the Docker container. Start the environment:

```bash
docker-compose up -d --build
```

### Step 2: Access the Container Shell

To run commands, you need to be inside the PHP container:

```bash
docker-compose exec php bash
```

### Step 3: Set Up the Database

Inside the container, run:

```bash
composer db:seed
```

This will create the database schema and insert sample books.

**Note:** The PHP development server starts automatically when the container starts on `http://localhost:8000`. You don't need to start it manually!

### Step 4: Use the GUI Test Page

Open your browser and navigate to:

```
http://localhost:8000/test-ui.html
```

**This is your primary tool for testing the API during development!**

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/books` | List all books with availability status |
| GET | `/books/{id}` | Get details of a specific book |
| POST | `/books/{id}/borrow` | Borrow a book (marks as unavailable) |
| POST | `/books/{id}/return` | Return a borrowed book (marks as available) |

## Development Commands

All commands can be found by running `composer` inside the container. Most important are listed below:
- `composer test:unit`
- `composer test:integration`
- `composer lint`
- `composer analyse`
- `composer db:seed`

## Your Tasks

You have **two types of tasks** to complete:

### 1. Bug Investigation

There are two reported bugs that need investigation.

#### Bug #1: Book Not Available Despite Successful Borrow
**Report:** Multiple users successfully borrowed books through the API, but when they arrived at the library to pick them up, the books were not there.

#### Bug #2: Path Traversal in Book ID
**Report:** A user tried to borrow book "1/2" and instead borrowed book with ID 1.

### 2. Feature Implementation

Implement these two new features:

#### Feature #1: Add Filtering to GET /books
Add an optional query parameter `?available=true/false` to filter books by availability.

**Requirements:**
- `GET /books?available=true` - Show only available books
- `GET /books?available=false` - Show only borrowed books
- `GET /books` - Show all books (existing behavior)

#### Feature #2: Book Borrowing History
Add a new endpoint to view the complete borrowing history of a book.

**Endpoint:** `GET /books/{id}/history`

**Expected response:**
```json
{
  "book_id": 1,
  "title": "The PHP Manual",
  "history": [
    {
      "borrowed_at": "2025-11-20 10:00:00",
      "returned_at": "2025-11-22 15:30:00"
    },
    {
      "borrowed_at": "2025-11-25 09:00:00",
      "returned_at": null
    }
  ]
}
```

Good luck!
