FROM php:fpm

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions memcache \
    && install-php-extensions memcached \
    && install-php-extensions sockets \
    && install-php-extensions gd xdebug

RUN apt-get update && \
    apt-get -y --no-install-recommends install \
        libicu-dev \
        unzip \
        mc && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN \
    docker-php-ext-install \
        pdo_mysql \
        intl
