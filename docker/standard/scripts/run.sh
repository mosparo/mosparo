#!/usr/bin/env bash

set -Eeuxo pipefail
set -o history -o histexpand

[ -d /mosparo/var ] && rm -rf /mosparo/var
[ -d /mosparo-data/resources ] || mkdir /mosparo-data/resources
[ -d /mosparo-data/var ] || mkdir /mosparo-data/var
[ -d /mosparo-data/var/data ] || mkdir /mosparo-data/var/data
[ -f /mosparo-data/env.mosparo.php ] || echo "<?php return [];" > /mosparo-data/env.mosparo.php
chown -R www-data: /mosparo-data/
[ -L /mosparo/public/resources ] || ln -s /mosparo-data/resources/ /mosparo/public/resources
[ -L /mosparo/var ] || ln -s /mosparo-data/var/ /mosparo/var
[ -L /mosparo/config/env.mosparo.php ] || ln -s /mosparo-data/env.mosparo.php /mosparo/config/env.mosparo.php

rm -rf /mosparo-data/var/cache/prod

if [ $MOSPARO_ENABLE_CRON -eq 1 ]; then
  if [ $MOSPARO_ENABLE_WEBSERVER -eq 1 ]; then
    cron -f &
  else
    cron -f
  fi
fi

if [ $MOSPARO_ENABLE_WEBSERVER -eq 1 ]; then
  php-fpm -D -R
  nginx -g "daemon off;"
fi