# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install required PHP extensions + curl for healthcheck
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli pdo pdo_mysql

# PHP production settings (upload limits, timezone)
RUN echo "upload_max_filesize = 64M\n\
post_max_size = 64M\n\
max_execution_time = 120\n\
memory_limit = 256M\n\
date.timezone = Asia/Kolkata" > /usr/local/etc/php/conf.d/spark.ini

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create uploads directory if it doesn't exist
RUN mkdir -p /var/www/html/assets/uploads

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/assets/uploads

# Expose the default Apache port
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --retries=3 --start-period=30s \
    CMD curl -f http://localhost/login.php || exit 1

# Start Apache
CMD ["apache2-foreground"]
