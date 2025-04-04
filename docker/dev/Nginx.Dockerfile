FROM nginx

ADD docker/dev/config/nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/onboarding
