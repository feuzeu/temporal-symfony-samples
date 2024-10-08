ARG ALPINE_VERSION=3.19
ARG UNIT_VERSION=1.33.0
ARG PHP_VERSION=8.3

FROM alpine:${ALPINE_VERSION}
FROM unit:${UNIT_VERSION}-php${PHP_VERSION}

# https://github.com/mlocati/docker-php-extension-installer
# https://github.com/docker-library/docs/tree/0fbef0e8b8c403f581b794030f9180a68935af9d/php#how-to-install-more-php-extensions
RUN --mount=type=bind,from=mlocati/php-extension-installer:2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
  install-php-extensions @composer-2 opcache zip intl sockets protobuf grpc

# Create project root
RUN mkdir -p /var/www

WORKDIR /var/www

# Install main packages and remove default server definition
RUN apk add --no-cache bash curl wget zip vim git

RUN set -xe && apk add --no-cache --virtual .build-deps \
  libzip-dev freetype-dev icu-dev libmcrypt-dev libxslt-dev patch openssh-client

# Install PHP and its extensions packages and remove default server definition
ENV PHP_V="php83"

RUN apk add --no-cache \
  ${PHP_V} \
  ${PHP_V}-cli \
  ${PHP_V}-ctype \
  ${PHP_V}-curl \
  ${PHP_V}-dom \
  ${PHP_V}-fileinfo \
  ${PHP_V}-gd \
  ${PHP_V}-intl \
  ${PHP_V}-mbstring \
  ${PHP_V}-opcache \
  ${PHP_V}-openssl \
  ${PHP_V}-phar \
  ${PHP_V}-session \
  ${PHP_V}-tokenizer \
  ${PHP_V}-soap \
  ${PHP_V}-xml \
  ${PHP_V}-xmlreader \
  ${PHP_V}-xmlwriter \
  ${PHP_V}-simplexml \
  ${PHP_V}-zip \
  # Database
  ${PHP_V}-pdo \
  ${PHP_V}-pdo_sqlite \
  ${PHP_V}-sqlite3 \
  ${PHP_V}-pdo_mysql \
  ${PHP_V}-mysqlnd \
  ${PHP_V}-mysqli \
  ${PHP_V}-pdo_pgsql \
  ${PHP_V}-pgsql \
  ${PHP_V}-mongodb \
  ${PHP_V}-redis \
  # PHP PECL extensions
  ${PHP_V}-pecl-amqp \
  ${PHP_V}-pecl-xdebug

# PHP Docker
RUN docker-php-ext-install pdo pdo_mysql gd \
  && docker-php-ext-enable protobuf grpc

# PHP Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Make sure files/folders needed by the processes are accessible
RUN chown -R www-data.www-data /var/www

# Switch to use a non-root user from here on
USER www-data
