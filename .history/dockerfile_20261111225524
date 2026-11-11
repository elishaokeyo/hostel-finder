FROM php:8.2-apache

# Install MySQL extensions for PHP
RUN docker-php-ext-install pdo pdo_mysql

# Copy all project files into the container's web root
COPY . /var/www/html/

# Enable Apache rewrite module for clean URLs
RUN a2enmod rewrite

EXPOSE 80