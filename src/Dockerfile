FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/
RUN mkdir /var/www/html/item_images /var/www/html/cache /var/www/html/configs
RUN chmod 777 /var/www/html/item_images /var/www/html/cache /var/www/html/configs
