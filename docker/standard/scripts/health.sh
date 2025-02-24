#!/usr/bin/env bash

if [ $MOSPARO_ENABLE_WEBSERVER -eq 1 ]; then
  /usr/bin/curl -s --fail-with-body http://localhost/api/v1/health/check || exit 1
elif [ $MOSPARO_ENABLE_CRON -eq 1 ]; then
  sudo -u www-data /usr/local/bin/php /mosparo/bin/console mosparo:health || exit 1
fi