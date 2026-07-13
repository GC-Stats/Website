#!/bin/bash
set -e

apache2ctl start

php /var/www/html/artisan config:clear
php /var/www/html/artisan route:clear
php /var/www/html/artisan view:clear
php /var/www/html/artisan sitemap:generate &

shutdown() {
    echo "Container is stopping, syncing page views..."
    php /var/www/html/artisan app:sync-page-views
    apache2ctl stop

    kill -TERM "$child" 2>/dev/null
    wait "$child" 2>/dev/null
}

trap shutdown SIGTERM SIGINT

"$@" &
child=$!
wait "$child"
