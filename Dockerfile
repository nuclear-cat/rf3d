FROM php:7.2-apache

RUN apt-get update && apt-get install -y libpq-dev git libpng-dev libjpeg62-turbo-dev libfreetype6-dev wget libxrender1 libfontconfig1
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
        docker-php-ext-install -j$(nproc) gd && \
        docker-php-ext-install pdo_mysql && \
        docker-php-ext-install exif && \
        docker-php-ext-install zip && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/*

RUN a2enmod headers
RUN a2enmod rewrite

RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

ADD . /var/www/html/