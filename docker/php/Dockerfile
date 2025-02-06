# Base image
FROM php:8.1-apache

# Install required packages and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Suppress the FQDN warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set correct permissions at build time
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
