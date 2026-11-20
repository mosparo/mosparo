#!/bin/sh

set -eux

rm -rf /mosparo/var/cache/prod

[ -d /mosparo/var/data ] || mkdir /mosparo/var/data

if [ $MOSPARO_RUN_PHP_FPM -eq 1 ]; then
  if [ $MOSPARO_RUN_NGINX -eq 1 ]; then
    php-fpm -D
  else
    php-fpm -F
  fi
fi

if [ $MOSPARO_RUN_NGINX -eq 1 ]; then
  # -e sets the error log before the configuration is read. Without it nginx first opens its
  # compiled in default, which an arbitrary user cannot write, and prints an alert about it.
  nginx -e /dev/stdout -g "daemon off;"
fi