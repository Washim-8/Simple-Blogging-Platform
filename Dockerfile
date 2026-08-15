# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Install PostgreSQL PDO extension and curl for health check
RUN apt-get update && apt-get install -y \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files to the container
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Docker health check using the healthz endpoint
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/healthz || exit 1

# Apache will start automatically via the base image
