FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /src

# Copy application files (for local dev, this will be overridden by volume mount)
COPY . /src

# Install dependencies
# no reason to run composer install at build time for local development, skipped
# RUN if [ -f composer.json ]; then composer install --no-interaction --optimize-autoloader; fi

# Expose port 8000 for PHP built-in server
EXPOSE 8000

# Default command - can be overridden in docker-compose
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
