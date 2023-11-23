FROM php:7.3-fpm

# Add vars for xdebug
ENV XDEBUG_CONFIG idekey=PHPSTORM remote_host=host.docker.internal
ENV PHP_IDE_CONFIG serverName=localhost

# always run apt update when start and after add new source list, then clean up at end.
RUN apt-get update -yqq && \
    apt-get install -y apt-utils && \
    pecl channel-update pecl.php.net

RUN apt-get install -y \
        libzip-dev \
        zip

RUN pecl install apcu && \
    pecl install apcu_bc-1.0.3


# Enabling APCu and a compatibility layer for APC
RUN docker-php-ext-enable apcu --ini-name 10-apcu.ini && \
    echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/10-apcu.ini && \
    docker-php-ext-enable apc --ini-name 20-apcu_bc.ini

# Enabling AMQP
RUN apt-get install -y librabbitmq-dev && \
    pecl install amqp && \
    docker-php-ext-enable amqp --ini-name 20-amqp.ini

# Enabling memcache
RUN apt-get update \
    && apt-get install -y libmemcached-dev \
    && pecl install memcached \
    && docker-php-ext-enable memcached

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Increase memory limit for cli - phpunit
RUN echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Clean up
RUN apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    rm /var/log/lastlog /var/log/faillog

COPY . /var/www/html/
COPY . /var/www/app

RUN chsh -s /bin/bash www-data && \
    chown -R www-data:www-data /var/www/html

RUN chsh -s /bin/bash www-data && \
     chown -R www-data:www-data /var/www/app

ADD entrypoint.sh /docker-entrypoint.d/10-entrypoint.sh

WORKDIR /var/www/html

EXPOSE 80