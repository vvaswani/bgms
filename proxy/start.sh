#!/bin/sh
envsubst '${NGINX_FASTCGI_PASS} ${FRONTEND_ORIGIN}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
exec nginx -g 'daemon off;'
