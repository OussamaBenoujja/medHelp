# Base image
FROM php:8.1-cli

# Install PostgreSQL client and development libraries
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql


RUN apt-get update && apt-get install -y apache2
RUN a2enmod rewrite

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Suppress the FQDN warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy application files
COPY ./public /var/www/html/public
COPY ./app /var/www/html/app

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80