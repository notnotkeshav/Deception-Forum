FROM php:8.2-apache

# Enable required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN docker-php-ext-enable pdo pdo_mysql mysqli

# Install additional utilities
RUN apt-get update && apt-get install -y \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Set Apache document root to public folder
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Create required directories with proper permissions
RUN mkdir -p /var/www/html/logs /var/www/html/Backend/Core/cache /var/www/html/Backend/Core/email_queue
RUN chown -R www-data:www-data /var/www/html/logs /var/www/html/Backend/Core/cache /var/www/html/Backend/Core/email_queue
RUN chmod -R 700 /var/www/html/logs /var/www/html/Backend/Core/cache /var/www/html/Backend/Core/email_queue

# Expose port
EXPOSE 80

CMD ["apache2-foreground"]
