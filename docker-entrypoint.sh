#!/bin/bash
set -e

# If cloud hosting sets dynamic $PORT (Render, Railway, Heroku), configure Apache to listen on that port
if [ -n "$PORT" ]; then
    echo "Configuring Apache to listen on port $PORT..."
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf
fi

exec "$@"
