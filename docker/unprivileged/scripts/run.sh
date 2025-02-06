#!/usr/bin/env bash

set -Eeuxo pipefail
set -o history -o histexpand

rm -rf /mosparo/var/cache/prod

[ -d /mosparo/var/data ] || mkdir /mosparo/var/data

if [ $MOSPARO_RUN_PHP_FPM -eq 1 ]; then
  php-fpm -D -R
fi

if [ $MOSPARO_RUN_NGINX -eq 1 ]; then
  nginx -g "daemon off;"
fi