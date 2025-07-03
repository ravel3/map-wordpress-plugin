FROM wordpress:6.4-apache

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
