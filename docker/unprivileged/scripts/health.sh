#!/usr/bin/env bash

if [ $MOSPARO_RUN_NGINX -eq 1 ]; then
  /usr/bin/curl -s --fail-with-body http://localhost:8080/api/v1/health/check || exit 1
elif [ $MOSPARO_RUN_NGINX -eq 0 ]; then
  /usr/local/bin/php /mosparo/bin/console mosparo:health || exit 1
fi