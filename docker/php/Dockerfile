
FROM php:8.1-apache


RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean


RUN a2enmod rewrite


RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf


RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html
EXPOSE 80


CMD php -S 0.0.0.0:9000 






