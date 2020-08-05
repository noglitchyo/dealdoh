FROM php:7.3-fpm

RUN buildDeps=" \
        default-libmysqlclient-dev \
        libbz2-dev \
        libsasl2-dev \
    " \
    runtimeDeps=" \
        curl \
        git \
        libicu-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
    " \
    && apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y $buildDeps $runtimeDeps \
    && docker-php-ext-install bcmath bz2 iconv intl mbstring opcache pdo_mysql soap zip \
    && docker-php-ext-install sockets \
    && apt-get purge -y --auto-remove $buildDeps \
    && rm -r /var/lib/apt/lists/*

# Install Composer.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && ln -s $(composer config --global home) /root/composer
ENV PATH=$PATH:/root/composer/vendor/bin COMPOSER_ALLOW_SUPERUSER=1
