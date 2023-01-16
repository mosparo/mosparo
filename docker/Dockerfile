FROM php:8.2.1-fpm

ARG MOSPARO_VERSION

# This second arg is only needed because the package file name does not contain the 'v' at the
# beginning of the version number. We will fix this with the next release.
ARG MOSPARO_NUMERIC_VERSION

ENV TERM xterm

# Docker related
ENV MOSPARO_ENABLE_WEBSERVER 1
ENV MOSPARO_ENABLE_CRON 1

# mosparo related
ENV MOSPARO_UPDATES_ENABLED 0

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
        unzip \
        autoconf \
        cron \
        wget \
        nginx  && \
    docker-php-ext-install -j$(nproc) intl pdo_mysql zip opcache && \
    echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && locale-gen && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    apt-get remove ${PHPIZE_DEPS} -y

RUN wget "https://github.com/mosparo/mosparo/releases/download/${MOSPARO_VERSION}/mosparo-stable-${MOSPARO_NUMERIC_VERSION}.zip" && \
    unzip "mosparo-stable-${MOSPARO_NUMERIC_VERSION}.zip" && \
    rm "mosparo-stable-${MOSPARO_NUMERIC_VERSION}.zip"

ADD config/nginx.conf /etc/nginx/sites-enabled/default
ADD config/mosparo.cron /etc/cron.d/mosparo
ADD scripts/run.sh /usr/bin/run

RUN crontab /etc/cron.d/mosparo

VOLUME /mosparo-data

EXPOSE 80

ENTRYPOINT []

CMD ["/usr/bin/run"]