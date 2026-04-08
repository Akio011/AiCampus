FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Set permissions for uploads
RUN chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 755 /var/www/html/assets/uploads
