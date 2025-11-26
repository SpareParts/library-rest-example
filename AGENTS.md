# Agent Guidelines for Library REST API

## Dev environment
- Runs in docker
- Start with: `docker-compose up -d --build`
- Access container shell: `docker-compose exec php bash`
- Every command has to be run inside the container

## Commands
- Install dependencies: `composer install`
- Run tests: `vendor/bin/phpunit` or `composer test`
- Run single test: `vendor/bin/phpunit --filter TestClassName::testMethodName`
- Lint/format: `vendor/bin/php-cs-fixer fix` or `composer lint`
- Static analysis: `vendor/bin/phpstan analyse` or `composer analyse`
- Start dev server: `php -S localhost:8000 -t public`

## Code Style
- PHP 8.4+: Use typed properties, constructor property promotion, readonly, enums, null-safe operator
- PSR-12 formatting: 4 spaces, opening braces on new line for classes/methods
- Imports: Group by type (native, vendor, app), alphabetical order, one per line
- Types: Always declare strict_types=1, type all parameters/returns/properties
- Naming: PascalCase classes, camelCase methods/properties, UPPER_SNAKE constants
- Error handling: Use typed exceptions, never suppress errors, log appropriately
- Database: Use prepared statements (PDO), never concatenate SQL
- REST: Return proper HTTP status codes, use JSON responses with consistent structure
- Dependencies: Inject via constructor, use interfaces for testability
- Never use 'mixed' type, always be specific with types, use array-shapes and other phpstan features for better type safety