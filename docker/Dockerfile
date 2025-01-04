FROM php:8.3-fpm

ARG MOSPARO_VERSION

ENV TERM xterm

# Docker related
ENV MOSPARO_ENABLE_WEBSERVER 1
ENV MOSPARO_ENABLE_CRON 1

# mosparo related
ENV MOSPARO_UPDATES_ENABLED 0
ENV TRUSTED_PROXIES '127.0.0.1,REMOTE_ADDR'

# OCI annotations to image
LABEL org.opencontainers.image.authors="mosparo Core Developers" \
      org.opencontainers.image.title="mosparo" \
      org.opencontainers.image.description="The Modern Spam Protection for your form." \
      org.opencontainers.image.documentation="https://documentation.mosparo.io/" \
      org.opencontainers.image.base.name="php:8.3-fpm" \
      org.opencontainers.image.licenses="MIT" \
      org.opencontainers.image.source="https://github.com/mosparo/mosparo" \
      org.opencontainers.image.vendor="mosparo Core Developers" \
      org.opencontainers.image.version="${MOSPARO_VERSION}" \
      org.opencontainers.image.url="https://github.com/mosparo/mosparo"

SHELL ["/bin/bash", "-c"]

WORKDIR /mosparo

RUN apt-get update && \
    apt-get install -y \
        ${PHPIZE_DEPS} \
        apt-utils  \
        debconf-utils \
        apt-transport-https \
        build-essential \
        libicu-dev \
        locales \
        zip \
        libzip-dev \
        libpng-dev \
        libpq-dev \
        unzip \
        autoconf \
        cron \
        wget \
        nginx \
        libsqlite3-dev \
        libmemcached-dev \
        libssl-dev \
        zlib1g-dev && \
    docker-php-ext-install -j$(nproc) gd intl opcache pdo_mysql pdo_pgsql pdo_sqlite zip && \
    pecl install redis-6.1.0 && \
    pecl install memcached-3.3.0 && \
    docker-php-ext-enable redis memcached && \
    echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && locale-gen && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    apt-get remove ${PHPIZE_DEPS} -y

RUN wget "https://github.com/mosparo/mosparo/releases/download/${MOSPARO_VERSION}/mosparo-stable-${MOSPARO_VERSION}.zip" && \
    unzip "mosparo-stable-${MOSPARO_VERSION}.zip" && \
    rm "mosparo-stable-${MOSPARO_VERSION}.zip"

ADD config/nginx.conf /etc/nginx/sites-enabled/default
ADD config/mosparo.cron /etc/cron.d/mosparo
ADD --chmod=0755 scripts/run.sh /usr/bin/run

HEALTHCHECK --interval=1m --timeout=5s CMD curl -s --fail-with-body http://localhost/api/v1/health/check || exit 1

RUN crontab /etc/cron.d/mosparo

VOLUME /mosparo-data

EXPOSE 80

ENTRYPOINT []

CMD ["/usr/bin/run"]
