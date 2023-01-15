#!/usr/bin/env bash

set -Eeuxo pipefail
set -o history -o histexpand

[ -d /mosparo/var ] && rm -rf /mosparo/var
[ -d /mosparo-data/resources ] || mkdir /mosparo-data/resources
[ -d /mosparo-data/var ] || mkdir /mosparo-data/var
[ -f /mosparo-data/env.mosparo.php ] || echo "<?php return [];" > /mosparo-data/env.mosparo.php
chown -R www-data: /mosparo-data/
ln -s /mosparo-data/resources/ /mosparo/public/resources
ln -s /mosparo-data/var/ /mosparo/var
ln -s /mosparo-data/env.mosparo.php /mosparo/config/env.mosparo.php

php-fpm -D -R

nginx -g "daemon off;"