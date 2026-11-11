FROM php:8.2-apache

# MySQL driver for PHP
RUN docker-php-ext-install pdo pdo_mysql

# Let the project's .htaccess files work, only inside the web folder
RUN a2enmod rewrite \
    && printf '<Directory /var/www/html>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Listen on the port Render provides (default 10000)
ENV PORT=10000
RUN sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Project files
COPY . /var/www/html/

# Photo uploads must be writable by Apache
RUN mkdir -p /var/www/html/uploads/hostels \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 10000