FROM php:7.4-fpm

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git

## Подключаем библеотеки
RUN docker-php-ext-install pdo pdo_mysql pcntl sockets

RUN pecl install xdebug-2.9.8 && docker-php-ext-enable xdebug

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./composer.* ./

RUN composer install

COPY docker/keys/ /var/oauth/

# Конфигурирование xdebug
ADD docker/dev/config/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/onboarding